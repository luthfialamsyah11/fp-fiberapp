<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProofOfWork;
use App\Models\Task;
use Illuminate\Http\Request;

class ProofController extends Controller
{
    public function store(Request $request, Task $task)
    {
        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($task->status, ['assigned', 'accepted', 'on-going'])) {
            return response()->json(['message' => 'Bukti pekerjaan tidak dapat diunggah pada tiket yang sudah selesai atau ditolak'], 422);
        }

        $request->validate([
            'image'       => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'description' => 'nullable|string|max:1000',
        ]);

        $path = $request->file('image')->store('proof', 'public');

        $proof = ProofOfWork::create([
            'task_id'     => $task->id,
            'image'       => $path,
            'description' => $request->description ? strip_tags(trim($request->description)) : null,
        ]);

        return response()->json(['data' => $proof], 201);
    }

    public function index(Task $task)
    {
        if ($task->technician_id !== auth()->id() && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $proofs = $task->proofOfWork()->get()->map(function ($proof) {
            if (\Illuminate\Support\Str::startsWith($proof->image, ['http://', 'https://'])) {
                $proof->image_url = $proof->image;
            } else {
                $proof->image_url = asset('storage/' . $proof->image);
            }
            return $proof;
        });

        return response()->json(['data' => $proofs]);
    }
}