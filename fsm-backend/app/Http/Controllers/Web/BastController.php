<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class BastController extends Controller
{
    /**
     * Show & Print official Berita Acara Serah Terima (BAST) document
     */
    public function show(Request $request, Task $task)
    {
        // Allow if Admin OR Assigned Technician OR valid timing-safe tracking token provided
        $token = $request->query('token');
        $isTokenValid = $token && $task->tracking_token && hash_equals($task->tracking_token, $token);
        $isAdmin = auth()->check() && auth()->user()->role === 'admin';
        $isAssignedTech = auth()->check() && $task->technician_id === auth()->id();
        
        if (!$isAdmin && !$isAssignedTech && !$isTokenValid) {
            return redirect()->route('admin.login');
        }

        $task->load(['technician', 'materials.inventoryItem', 'proofOfWork']);

        return view('bast.print', compact('task'));
    }
}
