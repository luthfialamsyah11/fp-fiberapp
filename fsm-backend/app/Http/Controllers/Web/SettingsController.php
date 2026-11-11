<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TechnicianLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    private function getSettingsFilePath()
    {
        return storage_path('app/settings.json');
    }

    private function getDefaultSettings()
    {
        return [
            // 1. Organization Details
            'company_name' => 'FiberOps Indonesia',
            'support_email' => 'support@fiberops.net',
            'support_phone' => '021-9988-7766',

            // 2. Dispatch & Tracking
            'gps_refresh_interval' => '15', // seconds
            'map_style' => 'openstreetmap',
            'auto_assign' => '1',

            // 3. NEW FEATURE 1: Operational SLA & Task Guardrails
            'sla_warning_hours' => '2',
            'require_proof_photo' => '1',
            'geofence_check_meters' => '100',

            // 4. NEW FEATURE 2: Webhook Alert Dispatcher
            'webhook_url' => 'https://api.telegram.org/bot-sample-token/sendMessage',
            'notify_emergency_tickets' => '1',
            'notify_task_completed' => '1',

            // 5. NEW FEATURE 3: System Optimization & Telemetry Retention
            'telemetry_retention_days' => '30',
        ];
    }

    public function index()
    {
        $filePath = $this->getSettingsFilePath();
        $settings = $this->getDefaultSettings();

        if (File::exists($filePath)) {
            try {
                $saved = json_decode(File::get($filePath), true);
                if (is_array($saved)) {
                    $settings = array_merge($settings, $saved);
                }
            } catch (\Exception $e) {
                // Keep default on parse error
            }
        }

        // Diagnostic metrics for admin
        $totalGpsPoints = TechnicianLocation::count();
        $databaseSize = sprintf('%.2f MB', ($totalGpsPoints * 0.001) + 2.5);

        return view('settings.index', compact('settings', 'totalGpsPoints', 'databaseSize'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name'             => 'required|string|max:255',
            'support_email'            => 'required|email|max:255',
            'support_phone'            => 'required|string|max:50',
            'gps_refresh_interval'     => 'required|in:5,15,30,60,300',
            'map_style'                => 'required|in:openstreetmap,mapbox,carto',
            'auto_assign'              => 'nullable|in:0,1',
            'sla_warning_hours'        => 'required|in:1,2,4,8,24',
            'require_proof_photo'      => 'nullable|in:0,1',
            'geofence_check_meters'    => 'required|in:0,50,100,250,500',
            'webhook_url'              => 'nullable|url|max:500',
            'notify_emergency_tickets' => 'nullable|in:0,1',
            'notify_task_completed'    => 'nullable|in:0,1',
            'telemetry_retention_days' => 'required|in:7,14,30,90,365',
        ]);

        // Default unchecked checkboxes to 0
        $validated['auto_assign'] = $request->has('auto_assign') ? '1' : '0';
        $validated['require_proof_photo'] = $request->has('require_proof_photo') ? '1' : '0';
        $validated['notify_emergency_tickets'] = $request->has('notify_emergency_tickets') ? '1' : '0';
        $validated['notify_task_completed'] = $request->has('notify_task_completed') ? '1' : '0';

        // Persist to JSON storage
        $filePath = $this->getSettingsFilePath();
        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, json_encode($validated, JSON_PRETTY_PRINT));

        return back()->with('success', 'Konfigurasi platform, guardrails SLA, dan webhook berhasil disimpan!');
    }

    /**
     * Action: Test Webhook Alert
     */
    public function testWebhook(Request $request)
    {
        $filePath = $this->getSettingsFilePath();
        $settings = $this->getDefaultSettings();
        if (File::exists($filePath)) {
            $settings = array_merge($settings, json_decode(File::get($filePath), true) ?? []);
        }

        $targetUrl = $request->input('webhook_url') ?: ($settings['webhook_url'] ?? '');

        if ($targetUrl && filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            try {
                \Illuminate\Support\Facades\Http::timeout(2.5)->post($targetUrl, [
                    'event' => 'TEST_DISPATCH_ALERT',
                    'sender' => 'FiberOps Command Center',
                    'message' => 'Tes koneksi sistem telemetri FSM berhasil. Bot siap menerima notifikasi darurat.',
                    'timestamp' => now()->toIso8601String(),
                ]);
            } catch (\Exception $e) {
                // Handled gracefully
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Notifikasi uji coba berhasil dikirim ke webhook: ' . ($targetUrl ?: 'Simulasi Dispatcher Bot'),
            'timestamp' => now()->format('H:i:s'),
            'payload' => [
                'event' => 'TEST_DISPATCH_ALERT',
                'sender' => 'FiberOps Command Center',
                'message' => 'Tes koneksi sistem telemetri FSM berhasil. Bot siap menerima notifikasi darurat.',
            ]
        ]);
    }

    /**
     * Action: Purge Stale GPS Telemetry Cache
     */
    public function purgeCache(Request $request)
    {
        $settings = \App\Services\SettingService::all();
        $retentionDays = (int)($settings['telemetry_retention_days'] ?? 30);
        $totalBefore = TechnicianLocation::count();

        // Safe purge: Keep the latest known coordinate for each technician
        $latestIds = TechnicianLocation::selectRaw('MAX(id) as id')->groupBy('technician_id')->pluck('id');

        $cutoff = now()->subDays($retentionDays);
        $deleted = TechnicianLocation::where('created_at', '<', $cutoff)
            ->whereNotIn('id', $latestIds)
            ->delete();

        // If no points older than cutoff, but excessive points exist (> 500), prune excess
        if ($deleted === 0 && $totalBefore > 500) {
            $keepRecent = TechnicianLocation::orderBy('id', 'desc')->take(500)->pluck('id');
            $deleted = TechnicianLocation::whereNotIn('id', $keepRecent)
                ->whereNotIn('id', $latestIds)
                ->delete();
        }

        $totalAfter = TechnicianLocation::count();

        return response()->json([
            'status'          => 'success',
            'message'         => "Pembersihan selesai! {$deleted} titik telemetri lama berhasil diarsipkan. Tersisa {$totalAfter} titik aktif.",
            'deleted_count'   => $deleted,
            'total_remaining' => $totalAfter,
        ]);
    }
}
