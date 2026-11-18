<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskSignatureController extends Controller
{
    /**
     * Store digital customer signature on task with strict binary image verification.
     */
    public function store(Request $request, Task $task)
    {
        $user = auth()->user();

        if ($task->technician_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized access to this work order.'], 403);
        }

        // Prevent signature capture on pending/rejected/completed tasks (unless admin override)
        if (!in_array($task->status, ['assigned', 'accepted', 'on-going'], true) && $user->role !== 'admin') {
            return response()->json(['message' => 'Customer signature can only be captured on an active work order.'], 409);
        }

        // Prevent overwriting an already sealed signature on a completed task
        if (!empty($task->customer_signature) && $task->status === 'completed') {
            return response()->json(['message' => 'Completed work order signature is already sealed and immutable.'], 409);
        }

        $validated = $request->validate([
            'signature'   => 'required|string',
            'signer_name' => 'required|string|max:255',
        ]);

        $rawBase64 = $validated['signature'];

        // Strip optional data URI header, but NEVER trust its MIME/extension claim
        if (str_contains($rawBase64, ',')) {
            if (!preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/i', $rawBase64)) {
                return response()->json(['message' => 'Invalid signature image format. Only PNG or JPEG images are accepted.'], 422);
            }
            $rawBase64 = substr($rawBase64, strpos($rawBase64, ',') + 1);
        }

        $imageData = base64_decode($rawBase64, true);
        if ($imageData === false || strlen($imageData) < 64) {
            return response()->json(['message' => 'Invalid base64 signature payload.'], 422);
        }

        // Enforce 300 KB maximum payload size for signature images
        if (strlen($imageData) > 300_000) {
            return response()->json(['message' => 'Signature image exceeds the 300 KB size limit.'], 422);
        }

        // Inspect actual binary magic bytes to verify it is a genuine PNG/JPEG image
        $imageInfo = @getimagesizefromstring($imageData);
        if ($imageInfo === false || !in_array($imageInfo[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            return response()->json(['message' => 'Signature payload is not a valid PNG or JPEG image.'], 422);
        }

        // Hardcode .png extension to guarantee no executable extension can ever be written
        $fileName = 'signatures/sig_task_' . $task->id . '_' . Str::uuid() . '.png';
        Storage::disk('public')->put($fileName, $imageData);

        $signatureUrl = '/storage/' . $fileName;

        $task->update([
            'customer_signature'   => $signatureUrl,
            'customer_signer_name' => strip_tags(trim($validated['signer_name'])),
            'customer_signed_at'   => now(),
        ]);

        $freshTask = $task->fresh(['materials.inventoryItem', 'proofOfWork']);

        return response()->json([
            'message'              => 'Customer digital signature verified and stored.',
            'customer_signature'   => $signatureUrl,
            'customer_signer_name' => $task->customer_signer_name,
            'customer_signed_at'   => $task->customer_signed_at->toIso8601String(),
            'task'                 => $freshTask,
            'data'                 => $freshTask,
        ]);
    }
}
