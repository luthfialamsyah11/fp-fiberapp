<?php

namespace App\Services\Payment\Drivers;

use App\Models\Task;
use App\Services\Payment\QrisGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Zero-dependency Global Stripe Checkout Session Driver.
 * Setup requires only two tenant keys in /settings:
 *  - server_key (Stripe Secret Key: sk_live_... or sk_test_...)
 *  - webhook_secret (Stripe Webhook Signing Secret: whsec_...)
 *
 * Generates a hosted Stripe Checkout URL (Cards, Apple Pay, Google Pay, Link)
 * plus a scannable QR image for on-site technician mobile collection.
 */
class StripePaymentDriver implements QrisGatewayInterface
{
    /**
     * Official Stripe zero-decimal currencies (IDR is a two-decimal currency in Stripe's API).
     */
    public const ZERO_DECIMAL_CURRENCIES = [
        'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga',
        'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
    ];

    protected string $secretKey;
    protected string $webhookSecret;
    protected string $currency;
    protected string $baseUrl = 'https://api.stripe.com/v1';

    public function __construct(array $config = [])
    {
        $this->secretKey = trim((string) ($config['server_key'] ?? config('services.stripe.secret', '')));
        $this->webhookSecret = trim((string) ($config['webhook_secret'] ?? config('services.stripe.webhook_secret', '')));
        $this->currency = strtolower((string) ($config['currency'] ?? 'usd'));
    }

    public function createDynamicQris(Task $task, float $amount, int $expiryMinutes = 30): array
    {
        $referenceNo = 'STR-' . $task->id . '-' . strtoupper(Str::random(8));
        $expiresAt = now()->addMinutes(max(30, $expiryMinutes)); // Stripe minimum checkout expiry is 30 min
        $unitAmount = in_array($this->currency, self::ZERO_DECIMAL_CURRENCIES, true)
            ? (int) round($amount)
            : (int) round($amount * 100);

        $successUrl = url('/track/' . $task->ensureTrackingToken()) . '?payment=success';
        $cancelUrl = url('/track/' . $task->ensureTrackingToken()) . '?payment=cancelled';

        if (empty($this->secretKey)) {
            throw new \RuntimeException('Stripe Secret Key is not configured for this tenant. Add your Stripe sk_test_/sk_live_ key in Platform Settings.');
        }

        $res = Http::withToken($this->secretKey)
            ->asForm()
            ->timeout(8)
            ->post("{$this->baseUrl}/checkout/sessions", [
                'mode'                                           => 'payment',
                'client_reference_id'                            => $referenceNo,
                'expires_at'                                     => $expiresAt->timestamp,
                'success_url'                                    => $successUrl,
                'cancel_url'                                     => $cancelUrl,
                'line_items[0][quantity]'                        => 1,
                'line_items[0][price_data][currency]'            => $this->currency,
                'line_items[0][price_data][unit_amount]'         => $unitAmount,
                'line_items[0][price_data][product_data][name]'  => "ISP Service Invoice #TSK-{$task->id}",
                'line_items[0][price_data][product_data][description]' => mb_substr((string) ($task->title ?: 'FTTH Service & Connectivity Restoration'), 0, 200),
                'metadata[reference_no]'                         => $referenceNo,
                'metadata[task_id]'                              => (string) $task->id,
                'metadata[tenant_id]'                            => (string) ($task->tenant_id ?? 1),
            ]);

        if ($res->failed()) {
            Log::error('Stripe Checkout Session creation failed', [
                'status' => $res->status(),
                'body'   => $res->body(),
            ]);
            throw new \RuntimeException('Stripe Checkout creation failed: ' . ($res->json('error.message') ?? 'HTTP ' . $res->status()));
        }

        $data = $res->json();
        $checkoutUrl = (string) ($data['url'] ?? '');
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=360x360&data=' . urlencode($checkoutUrl);

        return [
            'reference_no' => $referenceNo,
            'amount'       => $amount,
            'qr_payload'   => $checkoutUrl,
            'qr_image_url' => $qrImageUrl,
            'expires_at'   => $expiresAt->toIso8601String(),
            'provider'     => 'stripe',
            'raw_response' => [
                'session_id'   => $data['id'] ?? null,
                'checkout_url' => $checkoutUrl,
                'currency'     => strtoupper($this->currency),
            ],
        ];
    }

    /**
     * Verify Stripe-Signature header (`t=<timestamp>,v1=<hmac_sha256>`).
     * Requires raw payload passed in $payload['_raw_body'] when invoked from PaymentWebhookController.
     */
    public function verifyWebhookSignature(array $payload, ?string $signatureHeader = null): bool
    {
        if (empty($this->webhookSecret) || empty($signatureHeader)) {
            return false;
        }

        $rawBody = (string) ($payload['_raw_body'] ?? '');
        if ($rawBody === '') {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                if ($kv[0] === 't') {
                    $timestamp = $kv[1];
                } elseif ($kv[0] === 'v1') {
                    $signatures[] = $kv[1];
                }
            }
        }

        if (empty($timestamp) || empty($signatures) || !ctype_digit($timestamp)) {
            return false;
        }

        // Reject replayed webhooks older than 5 minutes
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$rawBody}", $this->webhookSecret);
        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                return true;
            }
        }

        return false;
    }
}
