<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
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
}
