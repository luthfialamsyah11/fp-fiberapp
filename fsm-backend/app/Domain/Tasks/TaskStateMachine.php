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
            $isAdminOverride = $actor->isPortalStaff() && !empty(trim((string) $reason));

            if (!$isNormalTransition && !$isAdminOverride) {
                $msg = $actor->isPortalStaff()
                    ? "Cannot transition work order directly from '{$fromStatus}' to '{$toStatus}' without an explicit override reason."
                    : "Invalid status transition from '{$fromStatus}' to '{$toStatus}'.";

                throw ValidationException::withMessages(['status' => [$msg]]);
            }

            // Quality gates on start or completion
            if ($toStatus === 'on-going' && $actor->role === 'technician') {
                $this->assertGeofenceGate($lockedTask, $actor);
            }

            $hardwareTelemetry = null;
            if ($toStatus === 'completed') {
                $hardwareTelemetry = $this->assertCompletionGates($lockedTask, $actor, $isAdminOverride);
            }

            $updates = ['status' => $toStatus];

            if ($toStatus === 'pending' && $fromStatus === 'assigned' && ($context['unassign'] ?? false)) {
                $updates['technician_id'] = null;
            }

            if ($toStatus === 'completed') {
                $updates['completed_at']   = now();
                $updates['bast_hash']      = $lockedTask->computeBastHash();
                $updates['bast_sealed_at'] = now();

                // Record hardware optical telemetry if validated
                if (!empty($hardwareTelemetry)) {
                    $updates['optical_power_rx_dbm'] = $hardwareTelemetry['rx_power_dbm'] ?? null;
                    $updates['optical_power_tx_dbm'] = $hardwareTelemetry['tx_power_dbm'] ?? null;
                    $updates['hardware_verified_at'] = now();
                }

                // Auto-provision port on linked ODP fiber node for new installation tasks (row-locked to prevent over-allocation race condition)
                if ($lockedTask->fiber_node_id && ($lockedTask->service_category === 'new_install' || str_contains(strtolower((string) $lockedTask->title), 'install'))) {
                    $fiberNode = \App\Models\FiberNode::where('id', $lockedTask->fiber_node_id)->lockForUpdate()->first();
                    if ($fiberNode && $fiberNode->used_ports < $fiberNode->total_ports) {
                        $fiberNode->increment('used_ports');
                    }
                    if ($lockedTask->customer_id) {
                        $cust = \App\Models\Customer::where('id', $lockedTask->customer_id)->lockForUpdate()->first();
                        if ($cust) {
                            if (empty($cust->fiber_node_id)) {
                                $cust->update([
                                    'fiber_node_id' => $lockedTask->fiber_node_id,
                                    'status'        => 'active',
                                ]);
                            }
                            // Auto-provision MikroTik PPPoE Secret asynchronously after DB commit
                            try {
                                \App\Jobs\ProvisionCustomerPppoeJob::dispatch(
                                    (int) ($lockedTask->tenant_id ?? \App\Services\TenantContext::id() ?? 1),
                                    (int) $cust->id
                                )->afterCommit();
                            } catch (\Throwable $e) {
                                \Illuminate\Support\Facades\Log::warning("MikroTik async auto-provision dispatch failed: {$e->getMessage()}");
                            }
                        }
                    }
                }
            } elseif ($fromStatus === 'completed' && $toStatus !== 'completed') {
                $updates['completed_at']   = null;
                $updates['bast_hash']      = null;
                $updates['bast_sealed_at'] = null;

                // Roll back provisioned port on ODP fiber node with row-level lock
                if ($lockedTask->fiber_node_id && ($lockedTask->service_category === 'new_install' || str_contains(strtolower((string) $lockedTask->title), 'install'))) {
                    $fiberNode = \App\Models\FiberNode::where('id', $lockedTask->fiber_node_id)->lockForUpdate()->first();
                    if ($fiberNode && $fiberNode->used_ports > 0) {
                        $fiberNode->decrement('used_ports');
                    }
                }
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
        if ($maxMeters <= 0 || $task->latitude === null || $task->longitude === null) {
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

    protected function assertCompletionGates(Task $task, User $actor, bool $isAdminOverride): ?array
    {
        if ($isAdminOverride) {
            return null;
        }

        $requireProof = SettingService::get('require_proof_photo', '1');
        if ($requireProof == '1' && $task->proofOfWork()->count() === 0) {
            throw ValidationException::withMessages([
                'proof' => ['At least 1 photographic proof of work must be uploaded before completing the work order.']
            ]);
        }

        $requireSignature = SettingService::get('require_customer_signature', '1');
        if ($requireSignature == '1' && empty($task->customer_signature)) {
            throw ValidationException::withMessages([
                'signature' => ['Customer digital acceptance signature (Sign-on-Glass) is required before completing the work order.']
            ]);
        }

        // Optional strict FTTH Optical Power Meter (OPM -dBm) quality gate
        $requireOpm = SettingService::get('require_opm_reading', '0');
        if ($requireOpm == '1') {
            $maxAtten = (float) SettingService::get('opm_max_attenuation_dbm', '-27.0');
            $latestOpm = \App\Models\ProofOfWork::where('task_id', $task->id)
                ->whereNotNull('optical_power_dbm')
                ->orderByDesc('id')
                ->first();
            if (!$latestOpm) {
                throw ValidationException::withMessages([
                    'optical_power_dbm' => ['An OPM optical power reading (dBm) must be recorded on Proof of Work before completing this FTTH work order.'],
                ]);
            }
            if ((float) $latestOpm->optical_power_dbm < $maxAtten) {
                throw ValidationException::withMessages([
                    'optical_power_dbm' => ["Recorded optical power ({$latestOpm->optical_power_dbm} dBm) is below the required threshold ({$maxAtten} dBm)."],
                ]);
            }
        }

        // Live Carrier Hardware OLT / ONT Optical Telemetry Quality Gate
        $tenantSettings = \App\Services\TenantContext::getTenant()?->network_settings ?? [];
        $requireHwOlt = SettingService::get('require_hardware_olt_validation', '0') == '1' || !empty($tenantSettings['require_hardware_olt_validation']);
        if ($requireHwOlt) {
            $maxAtten = (float) ($tenantSettings['opm_max_attenuation_dbm'] ?? SettingService::get('opm_max_attenuation_dbm', '-27.0'));
            $ontSn = $task->customer?->ont_serial_number;

            // Fallback: look in task materials for ONT item with serial number
            if (empty($ontSn)) {
                $matWithSn = $task->materials()->whereNotNull('serial_number')->where('serial_number', '!=', '')->first();
                $ontSn = $matWithSn?->serial_number;
            }

            if (!empty($ontSn)) {
                $diag = app(\App\Services\Network\OltService::class)->assertOpticalHealth($ontSn, $maxAtten);
            } elseif (!empty($task->customer_id) || !empty($task->fiber_node_id)) {
                throw ValidationException::withMessages([
                    'ont_serial_number' => ['Live OLT optical validation is enabled, but no ONT Serial Number is recorded on the subscriber profile or BOM materials.'],
                ]);
            }
        }

        if ($actor->role === 'technician') {
            $this->assertGeofenceGate($task, $actor);
        }

        return $diag ?? null;
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
            if ($from === 'completed' && $actor->isPortalStaff()) {
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
        } elseif ($actor->isPortalStaff()) {
            ProgressUpdate::create([
                'task_id'          => $task->id,
                'user_id'          => $actor->id,
                'note'             => "Dispatcher updated work order status from {$from} to {$to}" . ($reason ? " ({$reason})" : '.'),
                'progress_percent' => $to === 'completed' ? 100 : $task->progress_percent,
            ]);
        }
    }
}
