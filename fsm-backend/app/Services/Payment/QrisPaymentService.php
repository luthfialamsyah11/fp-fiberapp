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
        if ($task->payment_status === 'paid') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'payment_status' => ['This work order has already been paid. Generating a duplicate QRIS is blocked.'],
            ]);
        }

        if ($task->status === 'rejected') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => ['Cannot generate QRIS for a rejected or cancelled work order.'],
            ]);
        }

        $billAmount = $amount ?? (float) ($task->billing_amount ?? 0);
        if ($billAmount < 1000) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount' => ['Billing amount must be at least IDR 1,000 to generate a dynamic QRIS.'],
            ]);
        }

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
        $task->touch();

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

            // Reject settling superseded/expired or failed QRIS transactions
            if (in_array($tx->status, ['expired', 'failed', 'cancelled'], true) || $tx->isExpired()) {
                Log::warning("Rejected settlement webhook for expired/invalid QRIS ref: {$referenceNo}");
                return null;
            }

            // Verify paid amount against invoiced amount when gross_amount is supplied by gateway
            if (isset($payload['gross_amount']) && is_numeric($payload['gross_amount'])) {
                $paidAmount = (float) $payload['gross_amount'];
                if (abs($paidAmount - (float) $tx->amount) > 0.01) {
                    Log::warning("Payment amount mismatch for ref {$referenceNo}: expected {$tx->amount}, got {$paidAmount}");
                    return null;
                }
            }

            $tx->update([
                'status'   => 'paid',
                'paid_at'  => now(),
                'metadata' => array_merge((array) $tx->metadata, ['settlement_payload' => $payload]),
            ]);

            // Mark task as paid and touch timestamp for instant Admin/APK live sync
            if ($tx->task_id) {
                $task = Task::withoutGlobalScopes()->find($tx->task_id);
                if ($task) {
                    $task->update(['payment_status' => 'paid']);
                    $task->touch();

                    // INSTANT ASYNC AUTO-UNBLOCK TRIGGER: Dispatch queued MikroTik un-isolation after DB commit
                    if ($task->customer_id) {
                        \App\Jobs\UnIsolateCustomerOnPaymentJob::dispatch(
                            (int) ($tx->tenant_id ?? 1),
                            (int) $task->customer_id,
                            $referenceNo
                        )->afterCommit();
                    }

                    // Dispatch tenant-scoped webhook notification asynchronously after DB commit
                    \App\Jobs\DispatchWebhookJob::dispatch(
                        (int) ($tx->tenant_id ?? 1),
                        'TASK_PAYMENT_SETTLED',
                        [
                            'task_id'        => $task->id,
                            'customer_name'  => $task->customer_name,
                            'reference_no'   => $referenceNo,
                            'amount'         => $tx->amount,
                            'payment_method' => 'qris',
                            'paid_at'        => now()->toIso8601String(),
                        ],
                        (int) $task->id
                    )->afterCommit();
                }
            }

            return $tx;
        });
    }
}
