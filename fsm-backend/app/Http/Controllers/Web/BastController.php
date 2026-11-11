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
        // Allow if user is logged in (admin/technician) OR valid tracking token provided
        $token = $request->query('token');
        $isTokenValid = $token && $task->tracking_token && hash_equals($task->tracking_token, $token);
        
        if (!auth()->check() && !$isTokenValid) {
            return redirect()->route('admin.login');
        }

        $task->load(['technician', 'materials.inventoryItem', 'proofOfWork']);

        return view('bast.print', compact('task'));
    }
}
