<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TechnicianLocation;
use Illuminate\Http\Request;

class CustomerTrackingController extends Controller
{
    /**
     * Show the public Uber-style tracking web page for customer
     */
    public function show(string $token)
    {
        $task = Task::where('tracking_token', $token)
            ->with(['technician', 'progressUpdates'])
            ->firstOrFail();

        // Get latest technician location
        $latestLocation = null;
        if ($task->technician_id) {
            $latestLocation = TechnicianLocation::where('technician_id', $task->technician_id)
                ->latest('created_at')
                ->first();
        }

        return view('tracking.customer', compact('task', 'latestLocation'));
    }

    /**
     * Public JSON polling API for live location & mission status updates
     */
    public function apiStatus(string $token)
    {
        $task = Task::where('tracking_token', $token)
            ->with(['technician', 'progressUpdates'])
            ->first();

        if (!$task) {
            return response()->json(['message' => 'Tracking session not found'], 404);
        }

        $latestLocation = null;
        if ($task->technician_id) {
            $latestLocation = TechnicianLocation::where('technician_id', $task->technician_id)
                ->latest('created_at')
                ->first();
        }

        // Determine technician coordinates
        $techLat = $latestLocation ? (float) $latestLocation->latitude : (float) ($task->latitude ?? -6.2088);
        $techLng = $latestLocation ? (float) $latestLocation->longitude : (float) ($task->longitude ?? 106.8456);

        return response()->json([
            'task' => [
                'id'               => $task->id,
                'title'            => $task->title,
                'status'           => $task->status,
                'status_label'     => $task->status_label,
                'progress_percent' => $task->progress_percent,
                'customer_name'    => $task->customer_name,
                'address'          => $task->address,
                'destination_lat'  => (float) $task->latitude,
                'destination_lng'  => (float) $task->longitude,
            ],
            'technician' => [
                'name'  => $task->technician?->name ?? 'Teknisi Lapangan',
                'phone' => $task->technician?->phone ?? '-',
            ],
            'live_location' => [
                'latitude'    => $techLat,
                'longitude'   => $techLng,
                'recorded_at' => $latestLocation?->created_at?->diffForHumans() ?? 'Baru saja',
            ]
        ]);
    }
}
