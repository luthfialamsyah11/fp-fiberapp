<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\Payment\QrisPaymentService;
use Illuminate\Http\Request;

class TaskPaymentController extends Controller
{
    public function __construct(protected QrisPaymentService $paymentService)
    {
    }

    /**
     * Generate dynamic QRIS for a field technician work order.
     */
    public function generateQris(Request $request, Task $task)
    {
        $user = auth()->user();
        if ($task->technician_id !== $user->id && !$user->isPortalStaff()) {
            return response()->json(['message' => 'Unauthorized access to this work order payment.'], 403);
        }

        $currency = $this->paymentService->getTenantCurrency();
        $minAmount = $currency === 'IDR' ? 1000 : 1;

        $validated = $request->validate([
            'amount'         => "nullable|numeric|min:{$minAmount}|max:100000000",
            'expiry_minutes' => 'nullable|integer|min:5|max:120',
        ]);

        $amount = isset($validated['amount']) ? (float) $validated['amount'] : (float) ($task->billing_amount ?? 0);
        if ($amount < $minAmount) {
            $minLabel = $currency === 'IDR' ? 'IDR 1,000' : "{$currency} 1.00";
            return response()->json([
                'message' => "Cannot generate payment link/QR: work order has zero billing amount and no override amount was specified. Please specify an amount of at least {$minLabel}.",
            ], 422);
        }

        $expiry = (int) ($validated['expiry_minutes'] ?? 15);

        $transaction = $this->paymentService->generateTaskQris($task, $amount, $expiry);

        return response()->json([
            'message'     => 'Dynamic QRIS generated successfully.',
            'transaction' => $transaction,
        ], 201);
    }

    /**
     * Check real-time payment status of a task (polled by technician mobile app).
     */
    public function paymentStatus(Task $task)
    {
        $user = auth()->user();
        if ($task->technician_id !== $user->id && !$user->isPortalStaff()) {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }

        $task->load('latestPayment');
        $latest = $task->latestPayment;

        return response()->json([
            'task_id'        => $task->id,
            'billing_amount' => $task->billing_amount,
            'payment_status' => $task->payment_status,
            'is_paid'        => $task->payment_status === 'paid',
            'transaction'    => $latest ? [
                'reference_no' => $latest->reference_no,
                'amount'       => $latest->amount,
                'status'       => $latest->status,
                'qr_image_url' => $latest->qr_image_url,
                'paid_at'      => $latest->paid_at?->toIso8601String(),
                'expires_at'   => $latest->expires_at?->toIso8601String(),
            ] : null,
        ]);
    }
}
