<?php

namespace App\Console;

use App\Models\TechnicianLocation;
use App\Services\SettingService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            // 1. Prune expired GPS telemetry while preserving latest 50 points per technician
            $retentionDays = (int) SettingService::get('telemetry_retention_days', '30');
            $techIds = TechnicianLocation::select('technician_id')->distinct()->pluck('technician_id');
            $protectedIds = [];

            foreach ($techIds as $techId) {
                $recent = TechnicianLocation::where('technician_id', $techId)
                    ->orderBy('id', 'desc')
                    ->take(50)
                    ->pluck('id')
                    ->all();
                $protectedIds = array_merge($protectedIds, $recent);
            }

            $cutoff = now()->subDays($retentionDays)->format('Y-m-d H:i:s');
            TechnicianLocation::whereRaw('COALESCE(captured_at, created_at) < ?', [$cutoff])
                ->when(!empty($protectedIds), fn ($q) => $q->whereNotIn('id', $protectedIds))
                ->delete();

            // 2. Prune expired idempotency keys older than 48 hours
            if (Schema::hasTable('idempotency_keys')) {
                DB::table('idempotency_keys')
                    ->where('created_at', '<', now()->subHours(48))
                    ->delete();
            }
        })->daily()->name('fsm-telemetry-and-idempotency-prune');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
