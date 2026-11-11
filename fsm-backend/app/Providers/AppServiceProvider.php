<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

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
                                'title'    => ($p->user->name ?? 'Teknisi') . ' memperbarui tugas ' . ($p->task ? '#TSK-' . str_pad($p->task->id, 4, '0', STR_PAD_LEFT) : ''),
                                'note'     => $p->note ? \Illuminate\Support\Str::limit($p->note, 45) : 'Progress ' . $p->progress_percent . '%',
                                'time_ago' => $p->created_at ? $p->created_at->diffForHumans() : 'baru saja',
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
}
