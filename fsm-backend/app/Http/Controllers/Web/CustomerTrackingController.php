<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TechnicianLocation;
use App\Services\SettingService;
use Illuminate\Support\Str;

class CustomerTrackingController extends Controller
{
    /**
     * Show the public Uber-style tracking web page for customer with strict privacy & expiry guards.
     */
    public function show(string $token)
    {
        $task = Task::where('tracking_token', $token)
            ->with(['technician', 'progressUpdates'])
            ->firstOrFail();

        $isExpired = $this->isTrackingExpired($task);
        $latestLocation = $isExpired ? null : $this->resolveJobBoundLocation($task);

        return view('tracking.customer', compact('task', 'latestLocation', 'isExpired'));
    }

    /**
     * Public JSON polling API for live location & mission status updates.
     */
    public function apiStatus(string $token)
    {
        $task = Task::where('tracking_token', $token)
            ->with(['technician', 'progressUpdates'])
            ->first();

        if (!$task) {
            return response()->json(['message' => 'Tracking session not found.'], 404);
        }

        if ($this->isTrackingExpired($task)) {
            return response()->json([
                'message' => 'This tracking session expired 24 hours after work order completion.',
                'state'   => 'expired',
            ], 410);
        }

        $latestLocation = $this->resolveJobBoundLocation($task);

        $distanceKm = null;
        $etaMinutes = null;
        if ($latestLocation && $task->latitude && $task->longitude) {
            $meters = SettingService::haversineMeters(
                (float) $latestLocation->latitude,
                (float) $latestLocation->longitude,
                (float) $task->latitude,
                (float) $task->longitude
            );
            $distanceKm = round($meters / 1000, 2);
            $speedMps = ($latestLocation->speed_mps && $latestLocation->speed_mps > 1.5)
                ? (float) $latestLocation->speed_mps
                : 8.33; // ~30 km/h urban average
            $etaMinutes = max(1, (int) ceil(($meters / $speedMps) / 60));
        }

        // Field-level privacy redaction: expose only technician first name, never private phone number
        $techFirstName = $task->technician
            ? Str::before(trim($task->technician->name), ' ')
            : 'Assigned Specialist';

        return response()->json([
            'state' => $latestLocation ? 'tracking' : 'waiting',
            'task'  => [
                'id'               => $task->id,
                'title'            => $task->title,
                'status'           => $task->status,
                'status_label'     => $task->status_label,
                'progress_percent' => $task->progress_percent,
                'customer_name'    => $task->customer_name,
                'address'          => $task->address,
                'destination_lat'  => $task->latitude ? (float) $task->latitude : null,
                'destination_lng'  => $task->longitude ? (float) $task->longitude : null,
            ],
            'technician' => [
                'name' => $techFirstName,
            ],
            'live_location' => $latestLocation ? [
                'latitude'    => (float) $latestLocation->latitude,
                'longitude'   => (float) $latestLocation->longitude,
                'accuracy_m'  => $latestLocation->accuracy_m,
                'distance_km' => $distanceKm,
                'eta_minutes' => $etaMinutes,
                'recorded_at' => ($latestLocation->captured_at ?? $latestLocation->created_at)?->diffForHumans() ?? 'Just now',
            ] : null,
        ]);
    }

    private function isTrackingExpired(Task $task): bool
    {
        if ($task->status !== 'completed') {
            return false;
        }
        $closedAt = $task->completed_at ?? $task->updated_at;
        return $closedAt !== null && $closedAt->lt(now()->subHours(24));
    }

    /**
     * Resolve only GPS pings bound to this task or captured while this task is actively being worked.
     * Never leak technician movements on other tasks after this task is completed!
     */
    private function resolveJobBoundLocation(Task $task): ?TechnicianLocation
    {
        if (!$task->technician_id) {
            return null;
        }

        // 1. Prefer points explicitly linked to this task_id
        $taskBound = TechnicianLocation::where('technician_id', $task->technician_id)
            ->where('task_id', $task->id)
            ->latest('id')
            ->first();

        if ($taskBound) {
            return $taskBound;
        }

        // 2. If task is completed, do NOT show newer pings from subsequent jobs
        if ($task->status === 'completed') {
            $closedAt = $task->completed_at ?? $task->updated_at;
            return TechnicianLocation::where('technician_id', $task->technician_id)
                ->where('created_at', '<=', $closedAt)
                ->latest('id')
                ->first();
        }

        // 3. Only return general technician ping while task is accepted or on-going
        if (in_array($task->status, ['accepted', 'on-going'], true)) {
            return TechnicianLocation::where('technician_id', $task->technician_id)
                ->latest('id')
                ->first();
        }

        return null;
    }
}
