<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TechnicianLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',
            'accuracy_m'  => 'nullable|numeric|min:0',
            'speed_mps'   => 'nullable|numeric|min:0',
            'heading_deg' => 'nullable|numeric|between:0,360',
            'captured_at' => 'nullable|date',
            'task_id'     => 'nullable|integer|exists:tasks,id',
            'client_uuid' => 'nullable|string|max:64',
            'source'      => 'nullable|string|in:foreground,background,heartbeat',
        ]);

        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;

        // Guard against Null Island / invalid (0,0) GPS readings
        if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
            return response()->json(['message' => 'Invalid GPS coordinates (Null Island rejected).'], 422);
        }

        if ($request->filled('accuracy_m') && (float) $request->accuracy_m > 250) {
            return response()->json(['message' => 'GPS reading accuracy is too low (> 250m).'], 422);
        }

        if ($request->filled('speed_mps') && (float) $request->speed_mps > 70) {
            return response()->json(['message' => 'Implausible GPS velocity (> 250 km/h).'], 422);
        }

        $user = auth()->user();
        $clientUuid = $request->input('client_uuid');

        // Deduplicate by client_uuid if already ingested
        if ($clientUuid) {
            $existing = TechnicianLocation::where('client_uuid', $clientUuid)->first();
            if ($existing) {
                $user->update(['is_online' => true, 'last_seen_at' => now()]);
                return response()->json([
                    'message'   => 'Location already synchronized (idempotent).',
                    'duplicate' => true,
                    'data'      => $existing,
                ]);
            }
        }

        $taskId = $request->input('task_id') ?: $this->resolveActiveTaskId($user->id);

        $capturedAt = now();
        if ($request->filled('captured_at')) {
            try {
                $parsed = Carbon::parse($request->captured_at);
                if ($parsed->between(now()->subHours(24), now()->addMinutes(5))) {
                    $capturedAt = $parsed;
                }
            } catch (\Throwable $e) {
                // Keep server time on malformed timestamp
            }
        }

        $location = TechnicianLocation::create([
            'technician_id' => $user->id,
            'task_id'       => $taskId,
            'latitude'      => $lat,
            'longitude'     => $lng,
            'accuracy_m'    => $request->input('accuracy_m'),
            'speed_mps'     => $request->input('speed_mps'),
            'heading_deg'   => $request->input('heading_deg'),
            'captured_at'   => $capturedAt,
            'client_uuid'   => $clientUuid,
            'source'        => $request->input('source', 'foreground'),
        ]);

        $user->update([
            'is_online'    => true,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'message' => 'Location updated successfully.',
            'data'    => $location,
        ]);
    }

    /**
     * Ingest a batch of buffered GPS points flushed after offline coverage recovery.
     */
    public function batchUpdate(Request $request)
    {
        $validated = $request->validate([
            'points'               => 'required|array|min:1|max:50',
            'points.*.latitude'    => 'required|numeric|between:-90,90',
            'points.*.longitude'   => 'required|numeric|between:-180,180',
            'points.*.accuracy_m'  => 'nullable|numeric|min:0',
            'points.*.speed_mps'   => 'nullable|numeric|min:0',
            'points.*.heading_deg' => 'nullable|numeric|between:0,360',
            'points.*.captured_at' => 'nullable|date',
            'points.*.task_id'     => 'nullable|integer',
            'points.*.client_uuid' => 'nullable|string|max:64',
            'points.*.source'      => 'nullable|string|in:foreground,background,heartbeat',
        ]);

        $user = auth()->user();
        $defaultTaskId = $this->resolveActiveTaskId($user->id);
        $accepted = 0;
        $skipped = 0;

        foreach ($validated['points'] as $pt) {
            $lat = (float) $pt['latitude'];
            $lng = (float) $pt['longitude'];

            if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
                $skipped++;
                continue;
            }
            if (isset($pt['accuracy_m']) && (float) $pt['accuracy_m'] > 250) {
                $skipped++;
                continue;
            }
            if (isset($pt['speed_mps']) && (float) $pt['speed_mps'] > 70) {
                $skipped++;
                continue;
            }

            $uuid = $pt['client_uuid'] ?? null;
            if ($uuid && TechnicianLocation::where('client_uuid', $uuid)->exists()) {
                $skipped++;
                continue;
            }

            $capturedAt = now();
            if (!empty($pt['captured_at'])) {
                try {
                    $parsed = Carbon::parse($pt['captured_at']);
                    if (!$parsed->between(now()->subHours(24), now()->addMinutes(5))) {
                        $skipped++;
                        continue;
                    }
                    $capturedAt = $parsed;
                } catch (\Throwable $e) {
                    $skipped++;
                    continue;
                }
            }

            $taskId = !empty($pt['task_id']) && Task::where('id', $pt['task_id'])->exists()
                ? (int) $pt['task_id']
                : $defaultTaskId;

            TechnicianLocation::create([
                'technician_id' => $user->id,
                'task_id'       => $taskId,
                'latitude'      => $lat,
                'longitude'     => $lng,
                'accuracy_m'    => $pt['accuracy_m'] ?? null,
                'speed_mps'     => $pt['speed_mps'] ?? null,
                'heading_deg'   => $pt['heading_deg'] ?? null,
                'captured_at'   => $capturedAt,
                'client_uuid'   => $uuid,
                'source'        => $pt['source'] ?? 'foreground',
            ]);
            $accepted++;
        }

        $user->update([
            'is_online'    => true,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'message'  => "Batch telemetry processed ({$accepted} stored, {$skipped} skipped).",
            'accepted' => $accepted,
            'skipped'  => $skipped,
        ]);
    }

    private function resolveActiveTaskId(int $technicianId): ?int
    {
        return Task::where('technician_id', $technicianId)
            ->whereIn('status', ['on-going', 'accepted'])
            ->orderByRaw("CASE WHEN status = 'on-going' THEN 0 ELSE 1 END")
            ->latest('updated_at')
            ->value('id');
    }
}
