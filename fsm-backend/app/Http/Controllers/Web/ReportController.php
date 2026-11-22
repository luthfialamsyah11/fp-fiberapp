<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $timeframe = $request->query('timeframe', 'all');
        $technicianId = $request->query('technician_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($request->query('export') === 'csv') {
            return $this->export($request);
        }

        $taskQuery = $this->applyFilters(Task::query(), $timeframe, $technicianId, $startDate, $endDate);

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
            COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority_tasks,
            AVG(CASE WHEN customer_rating IS NOT NULL THEN customer_rating END) as avg_csat_rating,
            COUNT(CASE WHEN customer_rating IS NOT NULL THEN 1 END) as rated_tasks_count
        ")->first();

        $totalTasks = (int) ($metricsRow->total_tasks ?? 0);
        $completedTasks = (int) ($metricsRow->completed_tasks ?? 0);
        $pendingTasks = (int) ($metricsRow->pending_tasks ?? 0);
        $ongoingTasks = (int) ($metricsRow->ongoing_tasks ?? 0);
        $urgentTasks = (int) ($metricsRow->urgent_tasks ?? 0);
        $ratedTasksCount = (int) ($metricsRow->rated_tasks_count ?? 0);
        $avgCsatRating = $ratedTasksCount > 0 && $metricsRow->avg_csat_rating !== null
            ? round((float) $metricsRow->avg_csat_rating, 1)
            : null;

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        $completedWithProof = (clone $taskQuery)->where('status', 'completed')->has('proofs')->count();
        $proofComplianceRate = $completedTasks > 0 ? round(($completedWithProof / $completedTasks) * 100, 1) : 100;

        // Cross-database compatible resolution duration calculation (MySQL & SQLite) using completed_at with updated_at fallback
        if (DB::getDriverName() === 'sqlite') {
            $avgMinutes = (clone $taskQuery)->where('status', 'completed')
                ->selectRaw('AVG((julianday(COALESCE(completed_at, updated_at)) - julianday(created_at)) * 1440) as avg_min')
                ->value('avg_min');
        } else {
            $avgMinutes = (clone $taskQuery)->where('status', 'completed')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, COALESCE(completed_at, updated_at))) as avg_min')
                ->value('avg_min');
        }
        $avgResolutionHours = $avgMinutes ? round(max(0, (float) $avgMinutes) / 60, 1) : 0;

        $statusCounts = [
            'pending'   => (int) ($metricsRow->pending_tasks ?? 0),
            'assigned'  => (int) ($metricsRow->assigned_tasks ?? 0),
            'accepted'  => (int) ($metricsRow->accepted_tasks ?? 0),
            'on-going'  => (int) ($metricsRow->ongoing_only_tasks ?? 0),
            'completed' => (int) ($metricsRow->completed_tasks ?? 0),
            'rejected'  => (int) ($metricsRow->rejected_tasks ?? 0),
        ];

        $priorityCounts = [
            'low'    => (int) ($metricsRow->low_priority_tasks ?? 0),
            'medium' => (int) ($metricsRow->medium_priority_tasks ?? 0),
            'high'   => (int) ($metricsRow->high_priority_tasks ?? 0),
        ];

        $trendDays = 14;
        $trendLabels = [];
        $trendCreated = [];
        $trendCompleted = [];

        $fourteenDaysAgo = Carbon::today()->subDays($trendDays - 1)->startOfDay();

        $createdTrends = Task::where('created_at', '>=', $fourteenDaysAgo)
            ->when($technicianId, fn ($q) => $q->where('technician_id', $technicianId))
            ->selectRaw('DATE(created_at) as date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $completedTrends = Task::where('status', 'completed')
            ->where(DB::raw('COALESCE(completed_at, updated_at)'), '>=', $fourteenDaysAgo)
            ->when($technicianId, fn ($q) => $q->where('technician_id', $technicianId))
            ->selectRaw('DATE(COALESCE(completed_at, updated_at)) as date, count(*) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        for ($i = $trendDays - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $trendLabels[] = $date->format('d M');
            $trendCreated[] = (int) ($createdTrends[$dateStr] ?? 0);
            $trendCompleted[] = (int) ($completedTrends[$dateStr] ?? 0);
        }

        $technicianCompletions = User::technicians()
            ->with(['latestLocation', 'tasks' => function ($q) use ($timeframe, $startDate, $endDate) {
                if ($timeframe === 'today') {
                    $q->whereDate('created_at', Carbon::today());
                } elseif ($timeframe === '7_days') {
                    $q->where('created_at', '>=', Carbon::now()->subDays(7));
                } elseif ($timeframe === '30_days') {
                    $q->where('created_at', '>=', Carbon::now()->subDays(30));
                } elseif ($timeframe === 'this_month') {
                    $q->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
                } elseif ($timeframe === 'custom' && $startDate && $endDate) {
                    try {
                        $from = Carbon::parse($startDate)->startOfDay();
                        $to = Carbon::parse($endDate)->endOfDay();
                        if ($from->lte($to)) {
                            $q->whereBetween('created_at', [$from, $to]);
                        }
                    } catch (\Throwable $e) {
                        // Ignore invalid date input safely
                    }
                }
            }, 'tasks.proofs'])
            ->get()
            ->map(function ($tech) {
                $tasks = $tech->tasks;
                $total = $tasks->count();
                $completed = $tasks->where('status', 'completed')->count();
                $active = $tasks->whereIn('status', ['assigned', 'accepted', 'on-going'])->count();
                $proofsCount = $tasks->sum(fn ($t) => $t->proofs->count());
                $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
                $ratedTasks = $tasks->filter(fn ($t) => $t->customer_rating !== null);
                $ratedCount = $ratedTasks->count();
                $avgRating = $ratedCount > 0 ? round((float) $ratedTasks->avg('customer_rating'), 1) : null;

                return [
                    'id'              => $tech->id,
                    'name'            => $tech->name,
                    'email'           => $tech->email,
                    'phone'           => $tech->phone ?? '-',
                    'is_online'       => $tech->isEffectivelyOnline(),
                    'total_assigned'  => $total,
                    'completed'       => $completed,
                    'active'          => $active,
                    'proofs_count'    => $proofsCount,
                    'completion_rate' => $rate,
                    'avg_rating'      => $avgRating,
                    'rated_count'     => $ratedCount,
                ];
            });

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
            'avgCsatRating',
            'ratedTasksCount',
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

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Task ID',
                'Work Order Title',
                'Customer Name',
                'Phone Number',
                'Address / Location',
                'Assigned Technician',
                'Priority',
                'Status',
                'Proof Photos Count',
                'Created At',
                'Last Updated'
            ]);

            $sanitizeCsvCell = function ($value) {
                $str = (string) ($value ?? '');
                if ($str !== '' && in_array($str[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                    return "'" . $str;
                }
                return $str;
            };

            $query->chunk(100, function ($tasks) use ($handle, $sanitizeCsvCell) {
                foreach ($tasks as $task) {
                    fputcsv($handle, [
                        '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT),
                        $sanitizeCsvCell($task->title),
                        $sanitizeCsvCell($task->customer_name),
                        $sanitizeCsvCell($task->customer_phone ?? '-'),
                        $sanitizeCsvCell($task->resolved_location),
                        $sanitizeCsvCell($task->technician ? $task->technician->name : 'Unassigned'),
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
                    try {
                        $from = Carbon::parse($startDate)->startOfDay();
                        $to = Carbon::parse($endDate)->endOfDay();
                        if ($from->lte($to)) {
                            $query->whereBetween('created_at', [$from, $to]);
                        }
                    } catch (\Throwable $e) {
                        // Ignore malformed date query strings safely instead of throwing HTTP 500
                    }
                }
                break;
            default:
                break;
        }

        return $query;
    }
}
