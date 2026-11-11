<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TechnicianLocation;
use App\Services\SettingService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function index(Request $request)
    {
        // Get technicians with their latest coordinates and active task
        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->with(['latestLocation', 'tasks' => function ($q) {
                $q->whereIn('status', ['accepted', 'on-going'])->latest();
            }])
            ->get();

        // Get location history logs for all technicians (limited to last 50 points total for display)
        $historyLogs = TechnicianLocation::with('technician')
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();

        // If a specific technician is selected for route tracking
        $selectedTechId = $request->get('technician_id');
        $selectedRoute = [];
        $selectedTech = null;
        if ($selectedTechId) {
            $selectedTech = User::find($selectedTechId);
            if ($selectedTech) {
                $selectedRoute = TechnicianLocation::where('technician_id', $selectedTechId)
                    ->orderBy('created_at', 'asc')
                    ->take(50)
                    ->get();
            }
        }

        $mapStyle = SettingService::get('map_style', 'openstreetmap');
        $pollInterval = (int)SettingService::get('gps_refresh_interval', '15');

        return view('tracking.index', compact('technicians', 'historyLogs', 'selectedTech', 'selectedRoute', 'mapStyle', 'pollInterval'));
    }

    /**
     * Real-time JSON API for live tracking updates
     */
    public function liveData(Request $request)
    {
        $technicians = User::where('role', 'technician')
            ->where('is_active', true)
            ->with(['latestLocation', 'tasks' => function ($q) {
                $q->whereIn('status', ['accepted', 'on-going'])->latest();
            }])
            ->get()
            ->map(function ($tech) {
                $activeTask = $tech->tasks->first();
                $latest = $tech->latestLocation;

                // Consider online if flagged is_online and has location update within last 30 minutes
                $isFresh = $latest && $latest->created_at->gt(now()->subMinutes(30));
                $onlineStatus = $tech->is_online || $isFresh;

                return [
                    'id'                  => $tech->id,
                    'name'                => $tech->name,
                    'phone'               => $tech->phone ?? '-',
                    'is_online'           => (bool)$onlineStatus,
                    'last_seen'           => $latest ? $latest->created_at->diffForHumans() : 'Belum ada data',
                    'last_seen_time'      => $latest ? $latest->created_at->format('H:i:s') : '-',
                    'latitude'            => $latest ? (float)$latest->latitude : null,
                    'longitude'           => $latest ? (float)$latest->longitude : null,
                    'active_task'         => $activeTask ? [
                        'id'            => $activeTask->id,
                        'title'         => $activeTask->title,
                        'status'        => $activeTask->status,
                        'customer_name' => $activeTask->customer_name,
                        'location'      => $activeTask->location,
                        'priority'      => $activeTask->priority,
                    ] : null,
                ];
            });

        // Recent real location logs
        $historyLogs = TechnicianLocation::with('technician:id,name')
            ->orderBy('id', 'desc')
            ->take(30)
            ->get()
            ->map(function ($log) {
                return [
                    'id'              => $log->id,
                    'technician_id'   => $log->technician_id,
                    'technician_name' => $log->technician ? $log->technician->name : 'Teknisi',
                    'latitude'        => (float)$log->latitude,
                    'longitude'       => (float)$log->longitude,
                    'time'            => $log->created_at->format('H:i:s'),
                    'diff'            => $log->created_at->diffForHumans(),
                ];
            });

        // Route trail for selected technician
        $selectedRoute = [];
        $selectedTechId = $request->get('technician_id');
        if ($selectedTechId) {
            $selectedRoute = TechnicianLocation::where('technician_id', $selectedTechId)
                ->orderBy('created_at', 'asc')
                ->take(50)
                ->get()
                ->map(function ($pt) {
                    return [
                        'latitude'  => (float)$pt->latitude,
                        'longitude' => (float)$pt->longitude,
                        'time'      => $pt->created_at->format('H:i:s'),
                    ];
                });
        }

        return response()->json([
            'status'         => 'success',
            'server_time'    => now()->format('H:i:s'),
            'online_count'   => $technicians->where('is_online', true)->count(),
            'total_count'    => $technicians->count(),
            'map_style'      => SettingService::get('map_style', 'openstreetmap'),
            'poll_interval'  => (int)SettingService::get('gps_refresh_interval', '15'),
            'technicians'    => $technicians,
            'history_logs'   => $historyLogs,
            'selected_route' => $selectedRoute,
        ]);
    }
}
