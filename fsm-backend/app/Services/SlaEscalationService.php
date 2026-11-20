<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskStatusHistory;
use Illuminate\Support\Facades\Schema;

/**
 * Automated SLA Escalation Engine.
 * Detects unassigned urgent tickets (> 15 min) or active tickets breaching sla_warning_hours
 * and triggers a single idempotent escalation alert (stamped via sla_escalated_at).
 */
class SlaEscalationService
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * Scan for un-escalated SLA breaches and dispatch escalation notifications once per ticket.
     *
     * @return int Number of work orders escalated in this sweep
     */
    public function runEscalationSweep(): int
    {
        if (!Schema::hasTable('tasks') || !Schema::hasColumn('tasks', 'sla_escalated_at')) {
            return 0;
        }

        $slaHours = max(1, (int) SettingService::get('sla_warning_hours', '2'));
        $urgentCutoff = now()->subMinutes(15);

        $breachedTasks = Task::whereIn('status', ['pending', 'assigned', 'accepted', 'on-going'])
            ->whereNull('sla_escalated_at')
            ->where(function ($q) use ($slaHours, $urgentCutoff) {
                $q->slaBreached($slaHours)
                    ->orWhere(function ($u) use ($urgentCutoff) {
                        $u->where('status', 'pending')
                            ->where('priority', 'high')
                            ->where('created_at', '<=', $urgentCutoff);
                    });
            })
            ->with('technician')
            ->get();

        $escalatedCount = 0;

        foreach ($breachedTasks as $task) {
            $task->update(['sla_escalated_at' => now()]);
            $code = '#TSK-' . str_pad($task->id, 4, '0', STR_PAD_LEFT);
            $reason = $task->status === 'pending' && $task->priority === 'high'
                ? "Auto-Escalation: High-priority ticket {$code} remained unassigned >15 minutes."
                : "Auto-Escalation: Work order {$code} breached {$slaHours}h SLA window.";

            if (Schema::hasTable('task_status_histories')) {
                TaskStatusHistory::create([
                    'task_id'     => $task->id,
                    'actor_id'    => null,
                    'actor_role'  => 'system',
                    'from_status' => $task->status,
                    'to_status'   => $task->status,
                    'reason'      => $reason,
                    'meta'        => ['event' => 'SLA_BREACHED', 'sla_hours' => $slaHours],
                ]);
            }

            $this->notifications->dispatchWebhookAsync('SLA_BREACHED', [
                'task_id'       => $task->id,
                'code'          => $code,
                'title'         => $task->title,
                'customer_name' => $task->customer_name,
                'priority'      => $task->priority,
                'status'        => $task->status,
                'technician'    => $task->technician?->name ?? 'Unassigned',
                'reason'        => $reason,
            ], $task->id);

            $escalatedCount++;
        }

        return $escalatedCount;
    }
}
