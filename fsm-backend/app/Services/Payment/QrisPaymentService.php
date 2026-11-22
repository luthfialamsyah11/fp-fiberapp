<?php

namespace App\Services\Payment;

use App\Models\PaymentTransaction;
use App\Models\Task;
use App\Models\Tenant;
use App\Services\Network\MikrotikService;
use App\Services\Payment\Drivers\MidtransQrisDriver;
use App\Services\Payment\Drivers\QrisSimulatorDriver;
use App\Services\SettingService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QrisPaymentService
{
    protected ?Tenant $tenant = null;
    protected ?QrisGatewayInterface $gateway = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant ?? TenantContext::getTenant();
    }

    public static function forTenant(?Tenant $tenant = null): self
    {
        return new self($tenant);
    }

    public function getGateway(): QrisGatewayInterface
    {
        if ($this->gateway !== null) {
            return $this->gateway;
        }

        $settings = $this->tenant?->settings['payment'] ?? [];
        $driver = $settings['driver'] ?? config('services.payment.driver', 'simulator');

        if ($driver === 'midtrans') {
            $this->gateway = new MidtransQrisDriver($settings);
        } else {
            $this->gateway = new QrisSimulatorDriver($settings);
        }

        return $this->gateway;
    }

    public function setGateway(QrisGatewayInterface $gateway): self
    {
        $this->gateway = $gateway;
        return $this;
    }

    /**
     * Generate dynamic QRIS for a field service task.
     */
    public function generateTaskQris(Task $task, ?float $amount = null, int $expiryMinutes = 15): PaymentTransaction
    {
        $billAmount = $amount ?? (float) ($task->billing_amount > 0 ? $task->billing_amount : 150000.0);
        $gateway = $this->getGateway();

        $res = $gateway->createDynamicQris($task, $billAmount, $expiryMinutes);

        // Invalidate or supersede previous pending transactions for this task
        PaymentTransaction::where('task_id', $task->id)
            ->where('status', 'pending')
            ->update(['status' => 'expired']);

        $transaction = PaymentTransaction::create([
            'tenant_id'      => $task->tenant_id ?? $this->tenant?->id ?? 1,
            'task_id'        => $task->id,
            'customer_id'    => $task->customer_id,
            'reference_no'   => $res['reference_no'],
            'amount'         => $res['amount'],
            'payment_method' => 'qris',
            'provider'       => $res['provider'] ?? 'simulator',
            'qr_payload'     => $res['qr_payload'],
            'qr_image_url'   => $res['qr_image_url'],
            'status'         => 'pending',
            'expires_at'     => $res['expires_at'],
            'metadata'       => $res['raw_response'] ?? null,
        ]);

        $task->update([
            'billing_amount' => $billAmount,
            'payment_status' => 'pending',
        ]);

        return $transaction;
    }

    /**
     * Process payment settlement atomically and trigger instant un-isolation on MikroTik.
     */
    public function processPaymentConfirmation(string $referenceNo, array $payload = [], ?string $provider = null): ?PaymentTransaction
    {
        return DB::transaction(function () use ($referenceNo, $payload, $provider) {
            $tx = PaymentTransaction::withoutGlobalScopes()
                ->where('reference_no', $referenceNo)
                ->lockForUpdate()
                ->first();

            if (!$tx) {
                Log::warning("Payment webhook received for unknown reference: {$referenceNo}");
                return null;
            }

            // Strictly idempotent: if already settled, return immediately without re-triggering
            if ($tx->status === 'paid') {
                return $tx;
            }

            $tx->update([
                'status'   => 'paid',
                'paid_at'  => now(),
                'metadata' => array_merge((array) $tx->metadata, ['settlement_payload' => $payload]),
            ]);

            // Mark task as paid
            if ($tx->task_id) {
                $task = Task::withoutGlobalScopes()->find($tx->task_id);
                if ($task) {
                    $task->update(['payment_status' => 'paid']);

                    // INSTANT AUTO-UNBLOCK TRIGGER: Restore customer internet on MikroTik!
                    if ($task->customer_id) {
                        $customer = \App\Models\Customer::withoutGlobalScopes()->find($task->customer_id);
                        if ($customer) {
                            try {
                                $mikrotik = MikrotikService::forTenant($tx->tenant);
                                $mikrotik->unIsolateCustomer($customer);
                                Log::info("Instant auto-unblock completed for customer CID: {$customer->customer_code} upon QRIS payment {$referenceNo}");
                            } catch (\Throwable $e) {
                                Log::error("Failed to auto-unblock customer on MikroTik after payment: {$e->getMessage()}");
                            }
                        }
                    }

                    // Dispatch webhook notification
                    SettingService::dispatchWebhook('TASK_PAYMENT_SETTLED', [
                        'task_id'        => $task->id,
                        'customer_name'  => $task->customer_name,
                        'reference_no'   => $referenceNo,
                        'amount'         => $tx->amount,
                        'payment_method' => 'qris',
                        'paid_at'        => now()->toIso8601String(),
                    ]);
                }
            }

            return $tx;
        });
    }
}
