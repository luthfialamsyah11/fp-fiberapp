<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class BastController extends Controller
{
    /**
     * Show & Print official Work Order Acceptance Certificate (BAST) document.
     */
    public function show(Request $request, Task $task)
    {
        $token = $request->query('token');
        $isTokenValid = $token && $task->tracking_token && hash_equals($task->tracking_token, $token);
        $isPortalStaff = auth()->check() && auth()->user()->isPortalStaff();
        $isAssignedTech = auth()->check() && $task->technician_id === auth()->id();

        if (!$isPortalStaff && !$isAssignedTech && !$isTokenValid) {
            return redirect()->route('admin.login');
        }

        $task->ensureTrackingToken();
        $task->load(['technician', 'materials.inventoryItem', 'proofOfWork']);

        // Ensure cryptographic seal is computed if task is completed
        if ($task->status === 'completed' && empty($task->bast_hash)) {
            $task->update([
                'bast_hash'      => $task->computeBastHash(),
                'bast_sealed_at' => $task->completed_at ?? $task->updated_at ?? now(),
            ]);
            $task->refresh();
        }

        $isIntegrityVerified = $task->isBastIntegrityValid();

        return view('bast.print', compact('task', 'isIntegrityVerified'));
    }

    /**
     * Verify the cryptographic SHA-256 integrity seal of a BAST certificate.
     */
    public function verify(Request $request, Task $task)
    {
        $token = $request->query('token');
        $isTokenValid = $token && $task->tracking_token && hash_equals($task->tracking_token, $token);
        $isPortalStaff = auth()->check() && auth()->user()->isPortalStaff();
        $isAssignedTech = auth()->check() && $task->technician_id === auth()->id();

        if (!$isPortalStaff && !$isAssignedTech && !$isTokenValid) {
            return response()->json(['message' => 'Unauthorized verification request.'], 403);
        }

        $computed = $task->computeBastHash();
        $stored = (string) ($task->bast_hash ?? '');
        $verified = $stored !== '' && hash_equals($stored, $computed);

        return response()->json([
            'task_id'       => $task->id,
            'certificate'   => 'BAST/FSM/' . ($task->created_at?->format('Y') ?? date('Y')) . '/' . str_pad((string) $task->id, 5, '0', STR_PAD_LEFT),
            'status'        => $task->status,
            'verified'      => $verified,
            'stored_hash'   => $stored ?: null,
            'computed_hash' => $computed,
            'sealed_at'     => $task->bast_sealed_at?->toIso8601String(),
        ]);
    }
}
