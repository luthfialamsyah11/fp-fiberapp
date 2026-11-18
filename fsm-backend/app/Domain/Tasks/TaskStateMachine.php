<?php

namespace App\Domain\Tasks;

use App\Models\ProgressUpdate;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\TechnicianLocation;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskStateMachine
{
    /**
     * Deterministic state transition graph.
     */
    public const TRANSITIONS = [
        'pending'   => ['assigned'],
        'assigned'  => ['accepted', 'on-going', 'pending', 'rejected'],
        'accepted'  => ['on-going', 'assigned', 'pending'],
        'on-going'  => ['completed'],
        'rejected'  => ['pending', 'assigned'],
        'completed' => [],
    ];

    /**
     * Execute a validated, transactional state transition with immutable audit logging.
     *
     * @throws ValidationException
     */
    public function transition(
        Task $task,
        string $toStatus,
        User $actor,
        ?string $reason = null,
        array $context = []
    ): Task {
        return DB::transaction(function () use ($task, $toStatus, $actor, $reason, $context) {
            /** @var Task $lockedTask */
            $lockedTask = Task::lockForUpdate()->findOrFail($task->id);
            $fromStatus = (string) $lockedTask->status;

            if ($fromStatus === $toStatus) {
                return $lockedTask;
            }

            $allowedNext = self::TRANSITIONS[$fromStatus] ?? [];
            $isNormalTransition = in_array($toStatus, $allowedNext, true);
            $isAdminOverride = $actor->role === 'admin' && !empty(trim((string) $reason));

            if (!$isNormalTransition && !$isAdminOverride) {
                $msg = $actor->role === 'admin'
                    ? "Cannot transition work order directly from '{$fromStatus}' to '{$toStatus}' without an explicit override reason."
                    : "Invalid status transition from '{$fromStatus}' to '{$toStatus}'.";

                throw ValidationException::withMessages(['status' => [$msg]]);
            }

            // Quality gates on start or completion
            if ($toStatus === 'on-going' && $actor->role === 'technician') {
                $this->assertGeofenceGate($lockedTask, $actor);
            }

            if ($toStatus === 'completed') {
                $this->assertCompletionGates($lockedTask, $actor, $isAdminOverride);
            }

            $updates = ['status' => $toStatus];

            if ($toStatus === 'pending' && $fromStatus === 'assigned' && ($context['unassign'] ?? false)) {
                $updates['technician_id'] = null;
            }

            if ($toStatus === 'completed') {
                $updates['completed_at']   = now();
                $updates['bast_hash']      = $lockedTask->computeBastHash();
                $updates['bast_sealed_at'] = now();
            } elseif ($fromStatus === 'completed' && $toStatus !== 'completed') {
                $updates['completed_at']   = null;
                $updates['bast_hash']      = null;
                $updates['bast_sealed_at'] = null;
            }

            $lockedTask->update($updates);

            // Record immutable status transition audit log
            TaskStatusHistory::create([
                'task_id'     => $lockedTask->id,
                'actor_id'    => $actor->id,
                'actor_role'  => $actor->role ?? 'technician',
                'from_status' => $fromStatus,
                'to_status'   => $toStatus,
                'reason'      => $reason ? strip_tags(trim($reason)) : null,
                'meta'        => !empty($context) ? $context : null,
                'ip_address'  => request()?->ip(),
            ]);

            // Record timeline progress update
            $this->recordTimelineProgress($lockedTask, $actor, $fromStatus, $toStatus, $reason);

            return $lockedTask->fresh(['technician', 'progressUpdates', 'proofOfWork', 'materials.inventoryItem']);
        });
    }

    protected function assertGeofenceGate(Task $task, User $actor): void
    {
        $maxMeters = (int) SettingService::get('geofence_check_meters', '0');
        if ($maxMeters <= 0 || !$task->latitude || !$task->longitude) {
            return;
        }

        $latestLoc = TechnicianLocation::where('technician_id', $actor->id)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->latest('id')
            ->first();

        if (!$latestLoc) {
            return;
        }

        $distance = SettingService::haversineMeters(
            (float) $task->latitude,
            (float) $task->longitude,
            (float) $latestLoc->latitude,
            (float) $latestLoc->longitude
        );

        if ($distance > $maxMeters) {
            $rounded = round($distance);
            throw ValidationException::withMessages([
                'geofence' => ["Technician GPS position is {$rounded}m away from customer location (maximum allowed geofence radius: {$maxMeters}m)."]
            ]);
        }
    }

    protected function assertCompletionGates(Task $task, User $actor, bool $isAdminOverride): void
    {
        if ($isAdminOverride) {
            return;
        }

        $requireProof = SettingService::get('require_proof_photo', '1');
        if ($requireProof == '1' && $task->proofOfWork()->count() === 0) {
            throw ValidationException::withMessages([
                'proof' => ['At least 1 photographic proof of work must be uploaded before completing the work order.']
            ]);
        }

        $requireSignature = SettingService::get('require_customer_signature', '0');
        if ($requireSignature == '1' && empty($task->customer_signature)) {
            throw ValidationException::withMessages([
                'signature' => ['Customer digital acceptance signature (Sign-on-Glass) is required before completing the work order.']
            ]);
        }

        if ($actor->role === 'technician') {
            $this->assertGeofenceGate($task, $actor);
        }
    }

    protected function recordTimelineProgress(Task $task, User $actor, string $from, string $to, ?string $reason): void
    {
        if ($to === 'accepted') {
            ProgressUpdate::create([
                'task_id'          => $task->id,
                'user_id'          => $actor->id,
                'note'             => 'Technician accepted the work order and prepared for dispatch.',
                'progress_percent' => max(10, $task->progress_percent),
            ]);
        } elseif ($to === 'on-going') {
            if ($from === 'completed' && $actor->role === 'admin') {
                ProgressUpdate::create([
                    'task_id'          => $task->id,
                    'user_id'          => $actor->id,
                    'note'             => 'Dispatcher reopened work order for field rework' . ($reason ? ": {$reason}" : '.'),
                    'progress_percent' => 75,
                ]);
            } elseif ($task->progressUpdates()->where('progress_percent', '>=', 25)->doesntExist()) {
                ProgressUpdate::create([
                    'task_id'          => $task->id,
                    'user_id'          => $actor->id,
                    'note'             => 'Technician checked in on-site and initiated service execution.',
                    'progress_percent' => 25,
                ]);
            }
        } elseif ($to === 'completed') {
            if ($task->progressUpdates()->where('progress_percent', 100)->doesntExist()) {
                ProgressUpdate::create([
                    'task_id'          => $task->id,
                    'user_id'          => $actor->id,
                    'note'             => $reason
                        ? "Work order completed ({$reason})."
                        : 'Work order completed and verified operational.',
                    'progress_percent' => 100,
                ]);
            }
        } elseif ($to === 'pending' && $from === 'assigned') {
            ProgressUpdate::create([
                'task_id'          => $task->id,
                'user_id'          => $actor->id,
                'note'             => 'Assignment declined by technician' . ($reason ? ": {$reason}" : '.'),
                'progress_percent' => 0,
            ]);
        } elseif ($actor->role === 'admin') {
            ProgressUpdate::create([
                'task_id'          => $task->id,
                'user_id'          => $actor->id,
                'note'             => "Dispatcher updated work order status from {$from} to {$to}" . ($reason ? " ({$reason})" : '.'),
                'progress_percent' => $to === 'completed' ? 100 : $task->progress_percent,
            ]);
        }
    }
}
