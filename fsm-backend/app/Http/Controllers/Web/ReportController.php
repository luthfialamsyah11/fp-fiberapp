<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProofOfWork;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display comprehensive operational reports & analytics.
     */
    public function index(Request $request)
    {
        $timeframe = $request->query('timeframe', 'all');
        $technicianId = $request->query('technician_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Check if user requested direct CSV export
        if ($request->query('export') === 'csv') {
            return $this->export($request);
        }

        // Base query with timeframe and technician filters applied
        $taskQuery = $this->applyFilters(Task::query(), $timeframe, $technicianId, $startDate, $endDate);

        // Core Aggregate Metrics & Distribution (Consolidated into 1 high-efficiency query)
        $metricsRow = (clone $taskQuery)->selectRaw("
            COUNT(*) as total_tasks,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tasks,
            COUNT(CASE WHEN status IN ('accepted', 'on-going') THEN 1 END) as ongoing_tasks,
            COUNT(CASE WHEN priority = 'high' THEN 1 END) as urgent_tasks,
            COUNT(CASE WHEN status = 'assigned' THEN 1 END) as assigned_tasks,
            COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_tasks,
            COUNT(CASE WHEN status = 'on-going' THEN 1 END) as ongoing_only_tasks,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_tasks,
            COUNT(CASE WHEN priority = 'low' THEN 1 END) as low_priority_tasks,
            COUNT(CASE WHEN priority = 'medium' THEN 1 END) as medium_priority_tasks,
            COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority_tasks
        ")->first();

        $totalTasks = (int) ($metricsRow->total_tasks ?? 0);
        $completedTasks = (int) ($metricsRow->completed_tasks ?? 0);
        $pendingTasks = (int) ($metricsRow->pending_tasks ?? 0);
        $ongoingTasks = (int) ($metricsRow->ongoing_tasks ?? 0);
        $urgentTasks = (int) ($metricsRow->urgent_tasks ?? 0);

        // Completion Rate
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        // Quality Gate: Proof of Work Compliance Rate (% of completed tasks with at least 1 photo proof)
        $completedWithProof = (clone $taskQuery)->where('status', 'completed')->has('proofs')->count();
        $proofComplianceRate = $completedTasks > 0 ? round(($completedWithProof / $completedTasks) * 100, 1) : 100;

        // Average Resolution Turnaround in Hours computed directly in database engine (zero PHP memory overhead)
        $avgMinutes = (clone $taskQuery)->where('status', 'completed')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_min')
            ->value('avg_min');
        $avgResolutionHours = $avgMinutes ? round($avgMinutes / 60, 1) : 0;

        // Status Distribution
        $statusCounts = [
            'pending'   => (int) ($metricsRow->pending_tasks ?? 0),
            'assigned'  => (int) ($metricsRow->assigned_tasks ?? 0),
            'accepted'  => (int) ($metricsRow->accepted_tasks ?? 0),
            'on-going'  => (int) ($metricsRow->ongoing_only_tasks ?? 0),
            'completed' => (int) ($metricsRow->completed_tasks ?? 0),
            'rejected'  => (int) ($metricsRow->rejected_tasks ?? 0),
        ];

        // Priority Distribution
        $priorityCounts = [
            'low'    => (int) ($metricsRow->low_priority_tasks ?? 0),
            'medium' => (int) ($metricsRow->medium_priority_tasks ?? 0),
            'high'   => (int) ($metricsRow->high_priority_tasks ?? 0),
        ];

        // 14-Day Trend Data for Dynamic Line Chart (Bulk grouped query replacing 28 loop queries)
        $trendDays = 14;
        $trendLabels = [];
        $trendCreated = [];
        $trendCompleted = [];

        $fourteenDaysAgo = Carbon::today()->subDays($trendDays - 1)->startOfDay();

        $createdTrends = Task::where('created_at', '>=', $fourteenDaysAgo)
            ->when($technicianId, fn($q) => $q->where('technician_id', $technicianId))
            ->selectRaw('DATE(created_at) as date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $completedTrends = Task::where('status', 'completed')
            ->where('updated_at', '>=', $fourteenDaysAgo)
            ->when($technicianId, fn($q) => $q->where('technician_id', $technicianId))
            ->selectRaw('DATE(updated_at) as date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        for ($i = $trendDays - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $trendLabels[] = $date->format('d M');
            $trendCreated[] = (int) ($createdTrends[$dateStr] ?? 0);
            $trendCompleted[] = (int) ($completedTrends[$dateStr] ?? 0);
        }

        // Technician Performance Benchmarking Table
        $technicianCompletions = User::technicians()
            ->with(['tasks' => function ($q) use ($timeframe, $startDate, $endDate) {
                if ($timeframe === 'today') {
                    $q->whereDate('created_at', Carbon::today());
                } elseif ($timeframe === '7_days') {
                    $q->where('created_at', '>=', Carbon::now()->subDays(7));
                } elseif ($timeframe === '30_days') {
                    $q->where('created_at', '>=', Carbon::now()->subDays(30));
                } elseif ($timeframe === 'this_month') {
                    $q->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
                } elseif ($timeframe === 'custom' && $startDate && $endDate) {
                    $q->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
                }
            }, 'tasks.proofs'])
            ->get()
            ->map(function ($tech) {
                $tasks = $tech->tasks;
                $total = $tasks->count();
                $completed = $tasks->where('status', 'completed')->count();
                $active = $tasks->whereIn('status', ['assigned', 'accepted', 'on-going'])->count();
                $proofsCount = $tasks->sum(function ($t) {
                    return $t->proofs->count();
                });
                $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

                return [
                    'id'              => $tech->id,
                    'name'            => $tech->name,
                    'email'           => $tech->email,
                    'phone'           => $tech->phone ?? '-',
                    'is_online'       => (bool) $tech->is_online,
                    'total_assigned'  => $total,
                    'completed'       => $completed,
                    'active'          => $active,
                    'proofs_count'    => $proofsCount,
                    'completion_rate' => $rate,
                ];
            });

        // Technicians list for filter dropdown
        $techniciansList = User::technicians()->orderBy('name')->get();

        return view('reports.index', compact(
            'totalTasks',
            'completedTasks',
            'pendingTasks',
            'ongoingTasks',
            'urgentTasks',
            'completionRate',
            'proofComplianceRate',
            'avgResolutionHours',
            'statusCounts',
            'priorityCounts',
            'trendLabels',
            'trendCreated',
            'trendCompleted',
            'technicianCompletions',
            'techniciansList',
            'timeframe',
            'technicianId',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Export operational report matching active filters as a downloadable CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $timeframe = $request->query('timeframe', 'all');
        $technicianId = $request->query('technician_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = $this->applyFilters(
            Task::with(['technician', 'proofs']),
            $timeframe,
            $technicianId,
            $startDate,
            $endDate
        )->orderBy('id', 'asc');

        $fileName = 'fsm_operational_report_' . Carbon::now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header Row
            fputcsv($handle, [
                'Task ID',
                'Judul Tugas',
                'Nama Pelanggan',
                'No. Telepon',
                'Alamat / Lokasi',
                'Teknisi Penanggung Jawab',
                'Prioritas',
                'Status',
                'Jumlah Bukti Foto',
                'Tanggal Dibuat',
                'Pembaruan Terakhir'
            ]);

            // Stream rows chunk by chunk
            $query->chunk(100, function ($tasks) use ($handle) {
                foreach ($tasks as $task) {
                    fputcsv($handle, [
                        '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT),
                        $task->title,
                        $task->customer_name,
                        $task->customer_phone ?? '-',
                        $task->resolved_location,
                        $task->technician ? $task->technician->name : 'Belum Ditugaskan',
                        ucfirst($task->priority),
                        $task->status_label,
                        $task->proofs->count(),
                        $task->created_at ? $task->created_at->format('Y-m-d H:i') : '-',
                        $task->updated_at ? $task->updated_at->format('Y-m-d H:i') : '-'
                    ]);
                }
            });

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * Helper to apply timeframe and technician filters.
     */
    private function applyFilters($query, string $timeframe, ?string $technicianId, ?string $startDate, ?string $endDate)
    {
        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }

        switch ($timeframe) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;
            case '7_days':
                $query->where('created_at', '>=', Carbon::now()->subDays(7));
                break;
            case '30_days':
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
                break;
            case 'this_month':
                $query->whereMonth('created_at', Carbon::now()->month)
                      ->whereYear('created_at', Carbon::now()->year);
                break;
            case 'custom':
                if ($startDate && $endDate) {
                    $query->whereBetween('created_at', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay()
                    ]);
                }
                break;
            default:
                // 'all' - no date filter
                break;
        }

        return $query;
    }
}
