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
     * Store digital customer signature on task
     */
    public function store(Request $request, Task $task)
    {
        // Guard authorization
        if ($task->technician_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'signature'   => 'required|string', // base64 data URL
            'signer_name' => 'required|string|max:255',
        ]);

        $base64Image = $validated['signature'];

        // Clean base64 header if present (e.g. data:image/png;base64,)
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $type = strtolower($type[1]); // png, jpg, jpeg
        } else {
            $type = 'png';
        }

        $imageData = base64_decode($base64Image);
        if ($imageData === false) {
            return response()->json(['message' => 'Format gambar tanda tangan tidak valid'], 422);
        }

        $fileName = 'signatures/sig_task_' . $task->id . '_' . time() . '_' . Str::random(6) . '.' . $type;
        Storage::disk('public')->put($fileName, $imageData);

        $signatureUrl = '/storage/' . $fileName;

        $task->update([
            'customer_signature'   => $signatureUrl,
            'customer_signer_name' => $validated['signer_name'],
            'customer_signed_at'   => now(),
        ]);

        $freshTask = $task->fresh(['materials.inventoryItem', 'proofOfWork']);

        return response()->json([
            'message'              => 'Tanda tangan pelanggan berhasil disimpan',
            'customer_signature'   => $signatureUrl,
            'customer_signer_name' => $task->customer_signer_name,
            'customer_signed_at'   => $task->customer_signed_at->toIso8601String(),
            'task'                 => $freshTask,
            'data'                 => $freshTask,
        ]);
    }
}
