<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProofOfWork;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProofController extends Controller
{
    public function index(Request $request)
    {
        $proofStatus = $request->query('proof_status', 'with_proof');

        if ($proofStatus === 'missing_proof') {
            $query = Task::doesntHave('proofs')
                ->with(['proofs', 'technician', 'latestProgressUpdate']);
        } elseif ($proofStatus === 'all') {
            $query = Task::query()
                ->with(['proofs', 'technician', 'latestProgressUpdate']);
        } else {
            // Default: only tasks with proofs
            $query = Task::has('proofs')
                ->with(['proofs', 'technician', 'latestProgressUpdate']);
        }

        // Search by keyword (Task ID, title, customer name, customer phone)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $cleanId = ltrim(preg_replace('/[^0-9]/', '', $search), '0');

            $query->where(function ($q) use ($search, $cleanId) {
                if (!empty($cleanId)) {
                    $q->orWhere('id', (int) $cleanId);
                }
                $q->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Filter by Technician
        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        // Filter by Date
        if ($request->filled('date_filter')) {
            switch ($request->date_filter) {
                case 'today':
                    $query->whereDate('updated_at', Carbon::today());
                    break;
                case 'this_week':
                    $query->whereBetween('updated_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereMonth('updated_at', Carbon::now()->month)
                          ->whereYear('updated_at', Carbon::now()->year);
                    break;
            }
        }

        $tasksWithProof = $query->orderBy('updated_at', 'desc')->paginate(12)->withQueryString();

        // Calculate KPI metrics with minimal database overhead
        $proofSummary = ProofOfWork::selectRaw('COUNT(*) as total, MAX(created_at) as latest_upload_at')->first();

        $metrics = [
            'total_proofs'      => (int) ($proofSummary->total ?? 0),
            'verified_tasks'    => Task::has('proofs')->where('status', 'completed')->count(),
            'active_techs'      => User::whereHas('tasks.proofs')->count(),
            'latest_upload_at'  => $proofSummary->latest_upload_at ? Carbon::parse($proofSummary->latest_upload_at) : null,
        ];

        // List of technicians for filter dropdown
        $technicians = User::technicians()->orderBy('name')->get();

        return view('proofs.index', compact('tasksWithProof', 'metrics', 'technicians'));
    }
}
