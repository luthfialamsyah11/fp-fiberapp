<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use App\Services\Payment\Drivers\StripePaymentDriver;
use App\Services\Payment\QrisPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(protected QrisPaymentService $paymentService)
    {
    }

    /**
     * Public payment webhook receiver for Payment Gateway callbacks.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info("Payment webhook received", ['payload' => $payload]);

        // Support standard formats: Midtrans, Xendit, and Simulator
        $referenceNo = $payload['order_id'] ?? $payload['reference_no'] ?? $payload['external_id'] ?? null;
        $status = strtolower((string) ($payload['transaction_status'] ?? $payload['status'] ?? 'settlement'));

        if (empty($referenceNo)) {
            return response()->json(['message' => 'Missing reference or order ID.'], 400);
        }

        // Midtrans: settlement or capture with accept means paid
        $isPaid = in_array($status, ['settlement', 'capture', 'paid', 'completed', 'success'], true);
        if (!$isPaid) {
            return response()->json(['message' => 'Non-settlement webhook acknowledged.'], 200);
        }

        // Verify gateway signature if present
        $tx = PaymentTransaction::withoutGlobalScopes()->where('reference_no', $referenceNo)->first();
        if ($tx) {
            $gateway = QrisPaymentService::forTenant($tx->tenant)->getGateway();
            $sigHeader = $request->header('X-Callback-Signature') ?? $request->header('X-Signature');
            $isValid = $gateway->verifyWebhookSignature($payload, $sigHeader);

            if (!$isValid) {
                Log::warning("Payment webhook signature verification failed for ref: {$referenceNo}");
                return response()->json(['message' => 'Invalid webhook signature.'], 403);
            }
        }

        $settled = $this->paymentService->processPaymentConfirmation($referenceNo, $payload);

        if (!$settled) {
            return response()->json(['message' => 'Transaction not found or could not be processed.'], 404);
        }

        return response()->json([
            'message'      => 'Payment confirmed and customer internet restored successfully.',
            'reference_no' => $referenceNo,
            'status'       => 'paid',
        ], 200);
    }

    /**
     * Global Stripe Checkout webhook receiver (`checkout.session.completed`).
     * Verifies HMAC-SHA256 `Stripe-Signature` header using the tenant's encrypted webhook_secret.
     */
    public function handleStripeWebhook(Request $request)
    {
        $rawBody = (string) $request->getContent();
        $event = json_decode($rawBody, true);
        if (!is_array($event)) {
            return response()->json(['message' => 'Malformed Stripe webhook payload.'], 400);
        }

        $type = (string) ($event['type'] ?? '');
        if ($type !== 'checkout.session.completed') {
            return response()->json(['message' => 'Stripe event acknowledged (ignored).'], 200);
        }

        $session = (array) ($event['data']['object'] ?? []);
        $referenceNo = (string) ($session['client_reference_id'] ?? ($session['metadata']['reference_no'] ?? ''));
        $paymentStatus = strtolower((string) ($session['payment_status'] ?? ''));

        if ($referenceNo === '') {
            return response()->json(['message' => 'Missing Stripe client_reference_id.'], 400);
        }

        if ($paymentStatus !== 'paid') {
            return response()->json(['message' => 'Checkout session not paid yet.'], 200);
        }

        $tx = PaymentTransaction::withoutGlobalScopes()->with('tenant')->where('reference_no', $referenceNo)->first();
        if (!$tx || !$tx->tenant) {
            return response()->json(['message' => 'Transaction not found.'], 404);
        }

        if ($tx->provider !== 'stripe') {
            Log::warning("Stripe webhook rejected: transaction {$referenceNo} belongs to provider '{$tx->provider}'.");
            return response()->json(['message' => 'Transaction provider mismatch.'], 422);
        }

        // Always verify Stripe webhooks with StripePaymentDriver (never fall back to simulator/midtrans)
        $tenantPaymentSettings = (array) ($tx->tenant->settings['payment'] ?? []);
        $tenantPaymentSettings['currency'] = QrisPaymentService::forTenant($tx->tenant)->getTenantCurrency();
        $stripeDriver = new StripePaymentDriver($tenantPaymentSettings);

        $sigHeader = $request->header('Stripe-Signature');
        $isValid = $stripeDriver->verifyWebhookSignature(array_merge($event, ['_raw_body' => $rawBody]), $sigHeader);

        if (!$isValid) {
            Log::warning("Stripe webhook signature verification failed for ref: {$referenceNo}");
            return response()->json(['message' => 'Invalid Stripe-Signature header.'], 403);
        }

        $currency = strtolower((string) ($session['currency'] ?? 'usd'));
        $amountTotal = isset($session['amount_total']) && is_numeric($session['amount_total'])
            ? (in_array($currency, StripePaymentDriver::ZERO_DECIMAL_CURRENCIES, true) ? (float) $session['amount_total'] : ((float) $session['amount_total'] / 100))
            : null;

        $payload = ['stripe_event_id' => $event['id'] ?? null, 'currency' => strtoupper($currency)];
        if ($amountTotal !== null) {
            $payload['gross_amount'] = $amountTotal;
        }

        $settled = $this->paymentService->processPaymentConfirmation($referenceNo, $payload, 'stripe');
        if (!$settled) {
            return response()->json(['message' => 'Transaction could not be settled (amount mismatch or expired).'], 422);
        }

        return response()->json([
            'message'      => 'Stripe payment confirmed and MikroTik auto-unblock queued.',
            'reference_no' => $referenceNo,
            'status'       => 'paid',
        ], 200);
    }
}
