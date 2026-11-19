<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TechnicianLocation;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SettingService::all();

        $totalGpsPoints = TechnicianLocation::count();
        $databaseSize = $this->formatOldestPingLabel();

        return view('settings.index', compact('settings', 'totalGpsPoints', 'databaseSize'));
    }

    private function formatOldestPingLabel(): string
    {
        $oldestPoint = TechnicianLocation::orderByRaw('COALESCE(captured_at, created_at) ASC')->first();
        if (!$oldestPoint) {
            return 'No GPS records stored';
        }

        $oldestTime = $oldestPoint->captured_at ?? $oldestPoint->created_at;
        return $oldestTime ? 'Oldest ping: ' . $oldestTime->diffForHumans() : 'No GPS records stored';
    }

    public function update(Request $request)
    {
        $rawCountryCode = preg_replace('/[\s+]+/', '', (string) $request->input('default_country_code', '62'));
        $request->merge(['default_country_code' => $rawCountryCode]);

        $validated = $request->validate([
            'company_name'               => 'required|string|max:255',
            'support_email'              => 'required|email|max:255',
            'support_phone'              => 'required|string|max:50',
            'currency'                   => 'nullable|in:USD,EUR,GBP,IDR',
            'default_country_code'       => ['required', 'regex:/^[0-9]{1,5}$/'],
            'gps_refresh_interval'       => 'required|in:5,15,30,60,300',
            'map_style'                  => 'required|in:openstreetmap,mapbox,carto',
            'auto_assign'                => 'nullable|in:0,1',
            'sla_warning_hours'          => 'required|in:1,2,4,8,24',
            'require_proof_photo'        => 'nullable|in:0,1',
            'require_customer_signature' => 'nullable|in:0,1',
            'geofence_check_meters'      => 'required|in:0,50,100,250,500',
            'webhook_url'                => [
                'nullable',
                'url',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (!empty($value) && !SettingService::isSafeExternalUrl((string) $value)) {
                        $fail('The webhook URL must be a public external HTTP/HTTPS endpoint (private/loopback hosts are blocked by the SSRF guard).');
                    }
                },
            ],
            'notify_emergency_tickets'   => 'nullable|in:0,1',
            'notify_task_completed'      => 'nullable|in:0,1',
            'telemetry_retention_days'   => 'required|in:7,14,30,90,365',
        ], [
            'default_country_code.regex' => 'Default country dial code must contain 1 to 5 digits only (e.g., 62 or 1).',
        ]);

        $validated['auto_assign'] = $request->has('auto_assign') ? '1' : '0';
        $validated['require_proof_photo'] = $request->has('require_proof_photo') ? '1' : '0';
        $validated['require_customer_signature'] = $request->has('require_customer_signature') ? '1' : '0';
        $validated['notify_emergency_tickets'] = $request->has('notify_emergency_tickets') ? '1' : '0';
        $validated['notify_task_completed'] = $request->has('notify_task_completed') ? '1' : '0';
        $validated['currency'] = $validated['currency'] ?? 'USD';
        $validated['default_country_code'] = $validated['default_country_code'] ?: '62';
        $validated['webhook_url'] = trim((string) ($validated['webhook_url'] ?? ''));

        $filePath = SettingService::getFilePath();
        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, json_encode($validated, JSON_PRETTY_PRINT));

        return back()->with('success', 'Platform configuration, SLA guardrails, and webhook settings saved successfully.');
    }

    public function testWebhook(Request $request)
    {
        $settings = SettingService::all();
        $targetUrl = trim((string) ($request->input('webhook_url') ?: ($settings['webhook_url'] ?? '')));

        if (empty($targetUrl)) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Please enter a valid external HTTP(S) webhook URL before testing.',
                'timestamp' => now()->format('H:i:s'),
            ], 422);
        }

        if (!SettingService::isSafeExternalUrl($targetUrl)) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Webhook URL rejected: Only external public HTTP/HTTPS endpoints are permitted (SSRF guard).',
                'timestamp' => now()->format('H:i:s'),
            ], 422);
        }

        $payload = [
            'event'     => 'TEST_DISPATCH_ALERT',
            'sender'    => 'FiberOps Command Center',
            'message'   => 'FSM telemetry webhook connectivity verified. Endpoint ready for dispatch alerts.',
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            $resp = Http::timeout(3.5)->post($targetUrl, $payload);
            if ($resp->failed()) {
                return response()->json([
                    'status'    => 'error',
                    'message'   => 'Webhook endpoint returned HTTP ' . $resp->status() . ' during connectivity test.',
                    'timestamp' => now()->format('H:i:s'),
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Webhook connection failed: ' . $e->getMessage(),
                'timestamp' => now()->format('H:i:s'),
            ], 422);
        }

        return response()->json([
            'status'    => 'success',
            'message'   => 'Test notification delivered to webhook: ' . $targetUrl,
            'timestamp' => now()->format('H:i:s'),
            'payload'   => $payload,
        ]);
    }

    /**
     * Safe per-technician GPS telemetry retention purge.
     */
    public function purgeCache(Request $request)
    {
        $settings = SettingService::all();
        $validated = $request->validate([
            'telemetry_retention_days' => 'nullable|in:7,14,30,90,365',
        ]);
        $retentionDays = (int) ($validated['telemetry_retention_days'] ?? ($settings['telemetry_retention_days'] ?? 30));

        // Always keep the latest 50 points per technician so no active technician loses their route trail
        $technicianIds = TechnicianLocation::select('technician_id')->distinct()->pluck('technician_id');
        $protectedIds = [];

        foreach ($technicianIds as $techId) {
            $recentForTech = TechnicianLocation::where('technician_id', $techId)
                ->orderBy('id', 'desc')
                ->take(50)
                ->pluck('id')
                ->all();
            $protectedIds = array_merge($protectedIds, $recentForTech);
        }

        $cutoff = now()->subDays($retentionDays);
        $deleted = TechnicianLocation::whereRaw('COALESCE(captured_at, created_at) < ?', [$cutoff->format('Y-m-d H:i:s')])
            ->when(!empty($protectedIds), fn ($q) => $q->whereNotIn('id', $protectedIds))
            ->delete();

        $totalAfter = TechnicianLocation::count();
        $oldestPingLabel = $this->formatOldestPingLabel();

        return response()->json([
            'status'            => 'success',
            'message'           => "Telemetry purge complete ({$retentionDays}-day window). {$deleted} expired GPS records removed ({$totalAfter} active records retained; latest 50 per technician safeguarded).",
            'retention_days'    => $retentionDays,
            'deleted_count'     => $deleted,
            'total_remaining'   => $totalAfter,
            'oldest_ping_label' => $oldestPingLabel,
        ]);
    }
}
