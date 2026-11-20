<?php

namespace App\Domain\Tasks;

use App\Models\ProgressUpdate;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SettingService;
use Illuminate\Support\Facades\DB;

/**
 * Unified Hexagonal Domain Service for manual & GPS-aware automatic technician dispatch.
 * Eliminates duplicated Haversine scoring and locking across TaskController,
 * ClientRequestController, and AssignmentController.
 */
class DispatchService
{
    public function __construct(
        private readonly TaskStateMachine $stateMachine,
        private readonly NotificationService $notifications
    ) {
    }

    /**
     * Find the optimal active technician prioritizing:
     * 1) Effectively Online within the 2-minute GPS presence window (falls back to all active if none online)
     * 2) Nearest Haversine GPS distance + lowest active workload penalty (2km per active job)
     */
    public function selectOptimalTechnician(?float $taskLat, ?float $taskLng): ?User
    {
        $candidates = User::where('role', 'technician')
            ->where('is_active', true)
            ->with('latestLocation')
            ->withCount(['tasks as active_tasks_count' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'on-going']);
            }])
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $onlinePool = $candidates->filter(fn (User $u) => $u->isEffectivelyOnline());
        $pool = $onlinePool->isNotEmpty() ? $onlinePool : $candidates;

        return $pool->sortBy(function (User $tech) use ($taskLat, $taskLng) {
            $workloadPenalty = ((int) $tech->active_tasks_count) * 2000;
            $loc = $tech->latestLocation;
            if ($taskLat !== null && $taskLng !== null && $loc && $loc->latitude !== null && $loc->longitude !== null) {
                $dist = SettingService::haversineMeters(
                    $taskLat,
                    $taskLng,
                    (float) $loc->latitude,
                    (float) $loc->longitude
                );
                return $dist + $workloadPenalty;
            }
            return 500000 + $workloadPenalty;
        })->first();
    }

    /**
     * Transactional, lock-guarded manual or automatic dispatch.
     *
     * @return array{ok: bool, unchanged?: bool, error?: string, tech_name?: string, task?: Task}
     */
    public function assignTechnician(Task $task, int $technicianId, User $actor, ?string $customReason = null): array
    {
        $outcome = DB::transaction(function () use ($task, $technicianId, $actor, $customReason) {
            /** @var Task $locked */
            $locked = Task::lockForUpdate()->findOrFail($task->id);

            if ($locked->status === 'completed') {
                return ['ok' => false, 'error' => 'Cannot assign a completed work order. Request rework first.'];
            }

            $oldTechId = (int) ($locked->technician_id ?? 0);

            if ($oldTechId === $technicianId && in_array($locked->status, ['assigned', 'accepted', 'on-going'], true)) {
                return [
                    'ok'        => true,
                    'unchanged' => true,
                    'tech_name' => $locked->technician?->name ?? 'technician',
                    'task'      => $locked,
                ];
            }

            $locked->update(['technician_id' => $technicianId]);
            $locked->refresh();
            $techName = $locked->technician?->name ?? 'technician';

            if ($locked->status !== 'assigned') {
                $reason = $customReason ?: ($oldTechId && $oldTechId !== $technicianId
                    ? "Reassigned to {$techName} by NOC dispatcher"
                    : "Dispatched to {$techName} by NOC dispatcher");
                $this->stateMachine->transition($locked, 'assigned', $actor, $reason);
            } elseif ($oldTechId !== $technicianId) {
                ProgressUpdate::create([
                    'task_id'          => $locked->id,
                    'user_id'          => $actor->id,
                    'note'             => $customReason ?: "NOC dispatcher reassigned work order to {$techName}.",
                    'progress_percent' => (int) $locked->progress_percent,
                ]);
                $locked->touch();
            }

            return [
                'ok'        => true,
                'unchanged' => false,
                'tech_name' => $techName,
                'task'      => $locked->fresh(['technician']),
            ];
        });

        if (($outcome['ok'] ?? false) && empty($outcome['unchanged']) && !empty($outcome['task'])) {
            $this->notifications->notifyTaskAssigned($outcome['task']);
        }

        return $outcome;
    }

    /**
     * 1-Click Auto-Assign selecting the optimal online nearest technician.
     */
    public function autoAssign(Task $task, User $actor): array
    {
        $bestTech = $this->selectOptimalTechnician(
            $task->latitude !== null ? (float) $task->latitude : null,
            $task->longitude !== null ? (float) $task->longitude : null
        );

        if (!$bestTech) {
            return ['ok' => false, 'error' => 'No active technicians are available in the system.'];
        }

        $modeLabel = $bestTech->isEffectivelyOnline() ? 'Online Nearest GPS' : 'Lowest Workload Fallback';
        $result = $this->assignTechnician(
            $task,
            (int) $bestTech->id,
            $actor,
            "Auto-assigned ({$modeLabel}) to {$bestTech->name}"
        );

        $result['status_tag'] = $bestTech->isEffectivelyOnline() ? 'Online' : 'Standby';
        $result['technician'] = $bestTech;

        return $result;
    }
}
