<?php

namespace App\Services\Payment\Drivers;

use App\Models\Task;
use App\Services\Payment\QrisGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MidtransQrisDriver implements QrisGatewayInterface
{
    protected string $serverKey;
    protected string $baseUrl;

    public function __construct(protected array $config = [])
    {
        $this->serverKey = $config['server_key'] ?? config('services.midtrans.server_key', '');
        $isProduction = !empty($config['is_production']);
        $this->baseUrl = $isProduction
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    public function createDynamicQris(Task $task, float $amount, int $expiryMinutes = 15): array
    {
        $orderId = 'BS-TASK-' . $task->id . '-' . strtoupper(Str::random(6));

        $payload = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $amount,
            ],
            'customer_details' => [
                'first_name' => $task->customer_name ?: 'ISP Customer',
                'phone'      => $task->customer_phone ?: '',
                'email'      => $task->customer_email ?: '',
            ],
            'qris' => [
                'acquirer' => 'gopay',
            ],
        ];

        try {
            $res = Http::withBasicAuth($this->serverKey, '')
                ->timeout(8)
                ->acceptJson()
                ->post("{$this->baseUrl}/charge", $payload);

            if ($res->successful()) {
                $data = $res->json();
                $qrString = $data['qr_string'] ?? '';
                $actions = $data['actions'] ?? [];
                $qrImageUrl = '';

                foreach ($actions as $action) {
                    if (($action['name'] ?? '') === 'generate-qr-code') {
                        $qrImageUrl = $action['url'] ?? '';
                        break;
                    }
                }

                if (empty($qrImageUrl) && !empty($qrString)) {
                    $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrString);
                }

                return [
                    'reference_no' => $orderId,
                    'amount'       => $amount,
                    'qr_payload'   => $qrString,
                    'qr_image_url' => $qrImageUrl,
                    'expires_at'   => now()->addMinutes($expiryMinutes)->toIso8601String(),
                    'provider'     => 'midtrans',
                    'raw_response' => $data,
                ];
            }

            Log::warning("Midtrans QRIS charge error: " . $res->body());
        } catch (\Throwable $e) {
            Log::error("Midtrans charge exception: " . $e->getMessage());
        }

        // Only fall back to simulator in local/testing environments; fail closed in production
        if (app()->environment(['local', 'testing'])) {
            $fallback = new QrisSimulatorDriver($this->config);
            return $fallback->createDynamicQris($task, $amount, $expiryMinutes);
        }

        throw new \RuntimeException('Midtrans QRIS gateway unreachable. Please check your Midtrans server key and network connectivity.');
    }

    public function verifyWebhookSignature(array $payload, ?string $signatureHeader = null): bool
    {
        if (empty($payload['signature_key']) || empty($payload['order_id']) || empty($payload['status_code']) || empty($payload['gross_amount'])) {
            return false;
        }

        $computed = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . $this->serverKey);
        return hash_equals($computed, (string) $payload['signature_key']);
    }
}
