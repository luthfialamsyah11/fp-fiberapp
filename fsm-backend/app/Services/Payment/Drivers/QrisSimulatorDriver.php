<?php

namespace App\Services\Payment\Drivers;

use App\Models\Task;
use App\Services\Payment\QrisGatewayInterface;
use Illuminate\Support\Str;

class QrisSimulatorDriver implements QrisGatewayInterface
{
    public function __construct(protected array $config = [])
    {
    }

    public function createDynamicQris(Task $task, float $amount, int $expiryMinutes = 15): array
    {
        $ref = 'QRIS-SIM-' . strtoupper(Str::random(10)) . '-' . $task->id;
        $expires = now()->addMinutes($expiryMinutes)->toIso8601String();

        // Standard EMVCo QRIS compliant simulated string
        $qrPayload = sprintf(
            '00020101021226680014ID.LINKAJA.WWW011893600911%05d0215ID10200234567890303UME51440014ID.CO.QRIS.WWW0215ID10200234567890303UME52045812530336054%05d5802ID5913BENTENG_SIBER6007JAKARTA62070703A016304%s',
            $task->id,
            (int) $amount,
            strtoupper(substr(md5($ref), 0, 4))
        );

        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrPayload);

        return [
            'reference_no' => $ref,
            'amount'       => $amount,
            'qr_payload'   => $qrPayload,
            'qr_image_url' => $qrImageUrl,
            'expires_at'   => $expires,
            'provider'     => 'simulator',
            'raw_response' => [
                'status_code'        => '201',
                'status_message'     => 'Simulated QRIS generated successfully',
                'transaction_status' => 'pending',
            ],
        ];
    }

    public function verifyWebhookSignature(array $payload, ?string $signatureHeader = null): bool
    {
        // For simulator, accept if signature is 'simulated_valid_sig' or in test mode
        if (!empty($signatureHeader) && $signatureHeader === 'simulated_valid_sig') {
            return true;
        }

        // Midtrans style sha512 signature check fallback for simulation
        if (!empty($payload['signature_key']) && !empty($payload['order_id']) && !empty($payload['status_code']) && !empty($payload['gross_amount'])) {
            $serverKey = $this->config['server_key'] ?? 'simulated_server_key';
            $computed = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . $serverKey);
            return hash_equals($computed, (string) $payload['signature_key']);
        }

        return true;
    }
}
