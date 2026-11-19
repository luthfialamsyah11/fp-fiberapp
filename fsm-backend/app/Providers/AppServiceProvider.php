<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public const SCHEMA_VERSION = 'v5.5-gps-captured-at-sync';

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Self-healing automatic database migration & legacy note localization on production FTP deploy
        $this->ensureSchemaSynchronized();

        // Share dynamic notifications to all admin layout views safely
        View::composer('layouts.admin', function ($view) {
            try {
                if (auth()->check() && Schema::hasTable('progress_updates')) {
                    $notifications = \App\Models\ProgressUpdate::with(['task', 'user'])
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get()
                        ->map(function ($p) {
                            return [
                                'title'    => ($p->user->name ?? 'Technician') . ' updated work order ' . ($p->task ? '#TSK-' . str_pad($p->task->id, 4, '0', STR_PAD_LEFT) : ''),
                                'note'     => $p->note ? Str::limit($p->note, 45) : 'Progress ' . $p->progress_percent . '%',
                                'time_ago' => $p->created_at ? $p->created_at->diffForHumans() : 'just now',
                                'url'      => $p->task ? route('admin.tasks.show', $p->task->id) : route('admin.tasks.index'),
                            ];
                        });
                    $view->with('notifications', $notifications);
                }
            } catch (\Throwable $e) {
                $view->with('notifications', collect());
            }
        });
    }

    private function ensureSchemaSynchronized(): void
    {
        if ($this->app->runningUnitTests()) {
            return;
        }

        $lockFile = storage_path('app/schema_version.lock');
        if (File::exists($lockFile) && trim(File::get($lockFile)) === self::SCHEMA_VERSION) {
            return;
        }

        try {
            if (!Schema::hasTable('migrations')) {
                return;
            }

            Artisan::call('migrate', ['--force' => true]);

            // Standardize any legacy Indonesian progress notes stored in database
            if (Schema::hasTable('progress_updates')) {
                DB::table('progress_updates')
                    ->where('note', 'Teknisi tiba di lokasi dan memulai pengerjaan.')
                    ->update(['note' => 'Technician checked in on-site and initiated service execution.']);

                DB::table('progress_updates')
                    ->where('note', 'Pekerjaan selesai dan telah diverifikasi normal.')
                    ->update(['note' => 'Work order completed and verified operational.']);
            }

            File::ensureDirectoryExists(dirname($lockFile));
            File::put($lockFile, self::SCHEMA_VERSION);
        } catch (\Throwable $e) {
            // Never crash web requests if DB is temporarily unreachable
        }
    }
}
