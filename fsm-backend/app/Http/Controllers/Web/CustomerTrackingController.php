<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TechnicianLocation;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerTrackingController extends Controller
{
    /**
     * Show the public Ticket Self-Service Lookup Portal (/track).
     */
    public function lookupForm()
    {
        return view('tracking.lookup');
    }

    /**
     * Process ticket lookup with phone verification & redirect to live tracking token.
     */
    public function processLookup(Request $request)
    {
        $validated = $request->validate([
            'ticket_number' => 'required|string|max:64',
            'phone_last4'   => 'required|string|min:4|max:20',
        ]);

        $rawTicket = trim($validated['ticket_number']);
        $ticket = ltrim($rawTicket, '#');
        $digits = preg_replace('/\D/', '', $validated['phone_last4']);
        $last4 = substr($digits, -4);

        if (strlen($last4) < 4) {
            return back()->withInput()->with('error', 'Please enter at least 4 digits of the phone number used during registration.');
        }

        $task = Task::withoutGlobalScopes()
            ->where(function ($q) use ($ticket) {
                $trimmed = strtoupper($ticket);
                if (str_starts_with($trimmed, 'TSK-')) {
                    $idPart = (int) substr($trimmed, 4);
                    $q->where('id', $idPart);
                } elseif (is_numeric($ticket)) {
                    $q->where('id', (int) $ticket);
                }
                $q->orWhere('tracking_token', $ticket);
            })
            ->where(function ($q) use ($last4) {
                $q->where('customer_phone', 'LIKE', '%' . $last4)
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(customer_phone, ' ', ''), '-', ''), '+', ''), '(', '') LIKE ?", ['%' . $last4]);
            })
            ->first();

        if (!$task) {
            return back()->withInput()->with('error', 'Ticket not found or phone number verification failed. Please verify your Ticket ID (e.g. #TSK-0012) and the last 4 digits of your phone.');
        }

        $token = $task->ensureTrackingToken();
        return redirect()->route('customer.tracking', ['token' => $token]);
    }

    /**
     * Show the public Uber-style tracking web page for customer with strict privacy & expiry guards.
     */
    public function show(string $token)
    {
        $task = Task::withoutGlobalScopes()
            ->where('tracking_token', $token)
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
        $task = Task::withoutGlobalScopes()
            ->where('tracking_token', $token)
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
        if ($latestLocation && $latestLocation->latitude !== null && $latestLocation->longitude !== null && $task->latitude !== null && $task->longitude !== null) {
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
            : 'Awaiting NOC Dispatch';

        return response()->json([
            'state' => $latestLocation ? 'tracking' : 'waiting',
            'task'  => [
                'id'                => $task->id,
                'title'             => $task->title,
                'status'            => $task->status,
                'status_label'      => $task->status_label,
                'progress_percent'  => $task->progress_percent,
                'customer_name'     => $task->customer_name,
                'address'           => $task->address,
                'destination_lat'   => $task->latitude !== null ? (float) $task->latitude : null,
                'destination_lng'   => $task->longitude !== null ? (float) $task->longitude : null,
                'customer_rating'   => $task->customer_rating,
                'customer_feedback' => $task->customer_feedback,
                'customer_rated_at' => $task->customer_rated_at?->toIso8601String(),
                'bast_url'          => $task->status === 'completed'
                    ? route('admin.tasks.bast', ['task' => $task->id, 'token' => $task->tracking_token])
                    : null,
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

    /**
     * Submit a one-time 1–5 star customer CSAT rating after work order completion.
     */
    public function submitRating(Request $request, string $token)
    {
        $validated = $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $cleanFeedback = isset($validated['feedback']) ? trim(strip_tags((string) $validated['feedback'])) : null;
        if ($cleanFeedback === '') {
            $cleanFeedback = null;
        }

        $result = DB::transaction(function () use ($token, $validated, $cleanFeedback) {
            $task = Task::withoutGlobalScopes()->where('tracking_token', $token)->lockForUpdate()->first();
            if (!$task) {
                return ['status' => 404, 'body' => ['message' => 'Tracking session not found.']];
            }

            if ($task->status !== 'completed') {
                return ['status' => 422, 'body' => ['message' => 'Rating can only be submitted after the work order is completed.']];
            }

            if ($task->customer_rated_at !== null || $task->customer_rating !== null) {
                return ['status' => 409, 'body' => ['message' => 'Thank you — feedback has already been submitted for this work order.']];
            }

            $task->update([
                'customer_rating'   => (int) $validated['rating'],
                'customer_feedback' => $cleanFeedback,
                'customer_rated_at' => now(),
            ]);

            return [
                'status' => 200,
                'body'   => [
                    'message' => 'Thank you for rating our field service!',
                    'data'    => [
                        'customer_rating'   => (int) $task->customer_rating,
                        'customer_feedback' => $task->customer_feedback,
                        'customer_rated_at' => $task->customer_rated_at?->toIso8601String(),
                    ],
                ],
            ];
        });

        return response()->json($result['body'], $result['status']);
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
