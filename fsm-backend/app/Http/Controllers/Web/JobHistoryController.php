<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class JobHistoryController extends Controller
{
    public function index(Request $request)
    {
        $technicians = User::where('role', 'technician')->orderBy('name')->get();
        
        $techFilter = $request->get('technician_id');
        $statusFilter = $request->get('status');
        $search = $request->get('search');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Task::with(['technician', 'proofs'])
            ->when($techFilter, function ($q, $techId) {
                $q->where('technician_id', $techId);
            })
            ->when($statusFilter, function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($search, function ($q, $s) {
                $q->where(function($subQ) use ($s) {
                    $subQ->where('customer_name', 'like', "%{$s}%")
                         ->orWhere('title', 'like', "%{$s}%")
                         ->orWhere('customer_phone', 'like', "%{$s}%")
                         ->orWhere('address', 'like', "%{$s}%");
                });
            })
            ->when($startDate, function ($q, $start) {
                $q->whereDate('scheduled_at', '>=', $start);
            })
            ->when($endDate, function ($q, $end) {
                $q->whereDate('scheduled_at', '<=', $end);
            });

        // If user wants all records for client-side exporting, avoid pagination
        if ($request->has('export')) {
            $tasks = $query->orderBy('scheduled_at', 'desc')->get();
            return response()->json(['data' => $tasks]);
        }

        $tasks = $query->orderBy('scheduled_at', 'desc')->paginate(20);

        return view('history.index', compact('tasks', 'technicians', 'techFilter', 'statusFilter', 'search', 'startDate', 'endDate'));
    }
}
