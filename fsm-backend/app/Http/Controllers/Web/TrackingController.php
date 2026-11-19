<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TechnicianLocation;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->with(['latestLocation', 'tasks' => function ($q) {
                $q->whereIn('status', ['accepted', 'on-going'])->latest();
            }])
            ->get();

        $historyLogs = TechnicianLocation::with('technician')
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();

        $selectedTechId = $request->get('technician_id');
        $selectedRoute = [];
        $selectedTech = null;
        if ($selectedTechId) {
            $selectedTech = User::find($selectedTechId);
            if ($selectedTech) {
                // Take latest 50 points and order chronologically
                $selectedRoute = TechnicianLocation::where('technician_id', $selectedTechId)
                    ->orderBy('id', 'desc')
                    ->take(50)
                    ->get()
                    ->reverse()
                    ->values();
            }
        }

        $mapStyle = SettingService::get('map_style', 'openstreetmap');
        $pollInterval = (int) SettingService::get('gps_refresh_interval', '15');

        return view('tracking.index', compact('technicians', 'historyLogs', 'selectedTech', 'selectedRoute', 'mapStyle', 'pollInterval'));
    }

    /**
     * Real-time JSON API for live tracking updates (supports ?since_id= delta polling)
     */
    public function liveData(Request $request)
    {
        $sinceId = (int) $request->query('since_id', 0);

        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->with(['latestLocation', 'tasks' => function ($q) {
                $q->whereIn('status', ['accepted', 'on-going'])->latest();
            }])
            ->get()
            ->map(function ($tech) {
                $activeTask = $tech->tasks->first();
                $latest = $tech->latestLocation;
                $onlineStatus = $tech->isEffectivelyOnline();
                $capturedAt = $latest ? ($latest->captured_at ?? $latest->created_at) : null;
                $ageSeconds = $capturedAt ? max(0, (int) now()->diffInSeconds($capturedAt, false) * -1) : null;
                if ($capturedAt && $capturedAt->lte(now())) {
                    $ageSeconds = (int) $capturedAt->diffInSeconds(now());
                } elseif ($capturedAt) {
                    $ageSeconds = 0;
                }
                $isPositionStale = $ageSeconds !== null && $ageSeconds > User::ONLINE_WINDOW_SECONDS;
                $lowAccuracy = $latest && $latest->accuracy_m !== null && (float) $latest->accuracy_m > 250;

                return [
                    'id'                   => $tech->id,
                    'name'                 => $tech->name,
                    'phone'                => $tech->phone ?? '-',
                    'is_online'            => $onlineStatus,
                    'last_seen'            => $capturedAt ? $capturedAt->diffForHumans() : 'No telemetry yet',
                    'last_seen_time'       => $capturedAt ? $capturedAt->format('H:i:s') : '-',
                    'captured_at'          => $capturedAt ? $capturedAt->toIso8601String() : null,
                    'position_age_seconds' => $ageSeconds,
                    'is_position_stale'    => $isPositionStale,
                    'low_accuracy'         => $lowAccuracy,
                    'latitude'             => $latest ? (float) $latest->latitude : null,
                    'longitude'            => $latest ? (float) $latest->longitude : null,
                    'accuracy_m'           => $latest ? $latest->accuracy_m : null,
                    'speed_mps'            => $latest ? $latest->speed_mps : null,
                    'active_task'    => $activeTask ? [
                        'id'            => $activeTask->id,
                        'title'         => $activeTask->title,
                        'status'        => $activeTask->status,
                        'customer_name' => $activeTask->customer_name,
                        'location'      => $activeTask->location,
                        'priority'      => $activeTask->priority,
                        'latitude'      => $activeTask->latitude ? (float) $activeTask->latitude : null,
                        'longitude'     => $activeTask->longitude ? (float) $activeTask->longitude : null,
                    ] : null,
                ];
            });

        $historyQuery = TechnicianLocation::with('technician:id,name')->orderBy('id', 'desc');
        if ($sinceId > 0) {
            $historyQuery->where('id', '>', $sinceId);
        }

        $historyLogs = $historyQuery
            ->take(30)
            ->get()
            ->map(function ($log) {
                return [
                    'id'              => $log->id,
                    'technician_id'   => $log->technician_id,
                    'technician_name' => $log->technician ? $log->technician->name : 'Technician',
                    'latitude'        => (float) $log->latitude,
                    'longitude'       => (float) $log->longitude,
                    'accuracy_m'      => $log->accuracy_m,
                    'time'            => ($log->captured_at ?? $log->created_at)->format('H:i:s'),
                    'diff'            => ($log->captured_at ?? $log->created_at)->diffForHumans(),
                ];
            });

        $selectedRoute = [];
        $selectedTechId = $request->get('technician_id');
        if ($selectedTechId) {
            $selectedRoute = TechnicianLocation::where('technician_id', $selectedTechId)
                ->orderBy('id', 'desc')
                ->take(50)
                ->get()
                ->reverse()
                ->values()
                ->map(function ($pt) {
                    return [
                        'latitude'  => (float) $pt->latitude,
                        'longitude' => (float) $pt->longitude,
                        'time'      => ($pt->captured_at ?? $pt->created_at)->format('H:i:s'),
                    ];
                });
        }

        $latestLogId = (int) (TechnicianLocation::max('id') ?? 0);

        return response()->json([
            'status'         => 'success',
            'server_time'    => now()->format('H:i:s'),
            'online_count'   => $technicians->where('is_online', true)->count(),
            'total_count'    => $technicians->count(),
            'latest_log_id'  => $latestLogId,
            'map_style'      => SettingService::get('map_style', 'openstreetmap'),
            'poll_interval'  => (int) SettingService::get('gps_refresh_interval', '15'),
            'technicians'    => $technicians,
            'history_logs'   => $historyLogs,
            'selected_route' => $selectedRoute,
        ]);
    }
}
