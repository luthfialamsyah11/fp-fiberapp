<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                $q->whereDate(DB::raw('COALESCE(scheduled_at, created_at)'), '>=', $start);
            })
            ->when($endDate, function ($q, $end) {
                $q->whereDate(DB::raw('COALESCE(scheduled_at, created_at)'), '<=', $end);
            });

        // If user wants all records for client-side exporting, avoid pagination
        if ($request->has('export')) {
            $tasks = $query->orderByRaw('COALESCE(scheduled_at, created_at) DESC')->get();
            return response()->json(['data' => $tasks]);
        }

        $tasks = $query->orderByRaw('COALESCE(scheduled_at, created_at) DESC')->paginate(20)->withQueryString();

        return view('history.index', compact('tasks', 'technicians', 'techFilter', 'statusFilter', 'search', 'startDate', 'endDate'));
    }
}
