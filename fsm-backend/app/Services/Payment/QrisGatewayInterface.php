<?php

namespace App\Services\Payment;

use App\Models\Task;

interface QrisGatewayInterface
{
    /**
     * Create a dynamic QRIS transaction for a task.
     *
     * @param Task $task
     * @param float $amount
     * @param int $expiryMinutes
     * @return array{
     *     reference_no: string,
     *     amount: float,
     *     qr_payload: string,
     *     qr_image_url: string,
     *     expires_at: string,
     *     provider: string,
     *     raw_response?: array
     * }
     */
    public function createDynamicQris(Task $task, float $amount, int $expiryMinutes = 15): array;

    /**
     * Verify authenticity of incoming webhook payload.
     */
    public function verifyWebhookSignature(array $payload, ?string $signatureHeader = null): bool;
}
