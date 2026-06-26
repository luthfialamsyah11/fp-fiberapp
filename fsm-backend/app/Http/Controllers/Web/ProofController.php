<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\ProofOfWork;
use Illuminate\Http\Request;

class ProofController extends Controller
{
    public function index(Request $request)
    {
        // Get tasks that have proof of work uploaded
        $tasksWithProof = Task::has('proofs')
            ->with(['proofs', 'technician'])
            ->orderBy('updated_at', 'desc')
            ->paginate(12);

        return view('proofs.index', compact('tasksWithProof'));
    }
}
