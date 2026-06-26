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
        $request->validate([
            'image'       => 'required|image|max:5120',
            'description' => 'nullable|string',
        ]);

        if ($task->technician_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $path = $request->file('image')->store('proof', 'public');

        $proof = ProofOfWork::create([
            'task_id'     => $task->id,
            'image'       => $path,
            'description' => $request->description,
        ]);

        return response()->json(['data' => $proof], 201);
    }

    public function index(Task $task)
    {
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