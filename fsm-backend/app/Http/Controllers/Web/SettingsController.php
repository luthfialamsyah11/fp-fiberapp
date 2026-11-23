<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\TechnicianLocation;
use App\Models\TenantRouter;
use App\Services\Network\MikrotikService;
use App\Services\SettingService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SettingService::all();
        $tenant = TenantContext::getTenant();
        $networkSettings = $tenant?->network_settings ?? [];
        $paymentSettings = $tenant?->settings['payment'] ?? [];
        $routers = Schema::hasTable('tenant_routers')
            ? TenantRouter::orderByDesc('is_default')->orderBy('name')->get()
            : collect();

        $totalGpsPoints = TechnicianLocation::whereHas('technician')->count();
        $databaseSize = $this->formatOldestPingLabel();
        $recentNotificationLogs = Schema::hasTable('notification_logs')
            ? NotificationLog::latest('id')->take(10)->get()
            : collect();

        return view('settings.index', compact('settings', 'networkSettings', 'paymentSettings', 'routers', 'totalGpsPoints', 'databaseSize', 'recentNotificationLogs'));
    }

    private function formatOldestPingLabel(): string
    {
        $oldestPoint = TechnicianLocation::whereHas('technician')
            ->orderByRaw('COALESCE(captured_at, created_at) ASC')
            ->first();
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
            'currency'                   => 'nullable|in:USD,EUR,GBP,AUD,IDR',
            'default_country_code'       => ['required', 'regex:/^[0-9]{1,5}$/'],
            'gps_refresh_interval'       => 'required|in:5,15,30,60,300',
            'map_style'                  => 'required|in:openstreetmap,mapbox,carto',
            'auto_assign'                => 'nullable|in:0,1',
            'sla_warning_hours'          => 'required|in:1,2,4,8,24',
            'require_proof_photo'        => 'nullable|in:0,1',
            'require_customer_signature' => 'nullable|in:0,1',
            'require_opm_reading'        => 'nullable|in:0,1',
            'opm_max_attenuation_dbm'    => 'nullable|numeric|between:-40,-10',
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
            'whatsapp_gateway_url'       => [
                'nullable',
                'url',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (!empty($value) && !SettingService::isSafeExternalUrl((string) $value)) {
                        $fail('The WhatsApp Gateway URL must be a public external HTTP/HTTPS endpoint.');
                    }
                },
            ],
            'whatsapp_gateway_token'     => 'nullable|string|max:500',
            'notify_customer_whatsapp'   => 'nullable|in:0,1',
            'notify_customer_email'      => 'nullable|in:0,1',
            'fcm_server_key'             => 'nullable|string|max:500',
            'telemetry_retention_days'   => 'required|in:7,14,30,90,365',
            // Phase 2 & 3: Active Network Orchestrator & Payment Gateway (per-tenant)
            'mikrotik_driver'                 => 'nullable|in:simulator,rest',
            'mikrotik_host'                   => 'nullable|string|max:255',
            'mikrotik_port'                   => 'nullable|integer|min:1|max:65535',
            'mikrotik_user'                   => 'nullable|string|max:120',
            'mikrotik_password'               => 'nullable|string|max:255',
            'mikrotik_use_ssl'                => 'nullable|in:0,1',
            'olt_driver'                      => 'nullable|in:simulator,genieacs',
            'genieacs_host'                   => 'nullable|string|max:255',
            'genieacs_port'                   => 'nullable|integer|min:1|max:65535',
            'require_hardware_olt_validation' => 'nullable|in:0,1',
            'payment_driver'                  => 'nullable|in:simulator,stripe,midtrans',
            'payment_server_key'              => 'nullable|string|max:255',
            'payment_webhook_secret'          => 'nullable|string|max:255',
            'payment_is_production'           => 'nullable|in:0,1',
        ], [
            'default_country_code.regex' => 'Default country dial code must contain 1 to 5 digits only (e.g., 62 or 1).',
        ]);

        // Extract tenant-scoped network & payment config before saving JSON settings
        $tenant = TenantContext::getTenant();
        if ($tenant && ($request->has('mikrotik_driver') || $request->has('olt_driver') || $request->has('payment_driver') || $request->has('currency'))) {
            $existingNet = (array) ($tenant->network_settings ?? []);
            $newNet = array_merge($existingNet, [
                'mikrotik_driver'                 => $validated['mikrotik_driver'] ?? ($existingNet['mikrotik_driver'] ?? 'simulator'),
                'mikrotik_host'                   => trim((string) ($validated['mikrotik_host'] ?? ($existingNet['mikrotik_host'] ?? ''))),
                'mikrotik_port'                   => (int) ($validated['mikrotik_port'] ?? ($existingNet['mikrotik_port'] ?? 443)),
                'mikrotik_user'                   => trim((string) ($validated['mikrotik_user'] ?? ($existingNet['mikrotik_user'] ?? 'admin'))),
                'mikrotik_use_ssl'                => $request->boolean('mikrotik_use_ssl'),
                'olt_driver'                      => $validated['olt_driver'] ?? ($existingNet['olt_driver'] ?? 'simulator'),
                'genieacs_host'                   => trim((string) ($validated['genieacs_host'] ?? ($existingNet['genieacs_host'] ?? ''))),
                'genieacs_port'                   => (int) ($validated['genieacs_port'] ?? ($existingNet['genieacs_port'] ?? 7557)),
                'require_hardware_olt_validation' => $request->boolean('require_hardware_olt_validation'),
                'opm_max_attenuation_dbm'         => (float) ($validated['opm_max_attenuation_dbm'] ?? -27.0),
            ]);
            if (!empty($validated['mikrotik_password'])) {
                $newNet['mikrotik_password'] = $validated['mikrotik_password'];
            }

            $existingTenantSettings = (array) ($tenant->settings ?? []);
            if (!empty($validated['currency'])) {
                $existingTenantSettings['currency'] = $validated['currency'];
            }
            $existingPay = (array) ($existingTenantSettings['payment'] ?? []);
            $newPay = array_merge($existingPay, [
                'driver'        => $validated['payment_driver'] ?? ($existingPay['driver'] ?? 'simulator'),
                'is_production' => $request->boolean('payment_is_production'),
            ]);
            if (!empty($validated['payment_server_key'])) {
                $newPay['server_key'] = trim($validated['payment_server_key']);
            }
            if (!empty($validated['payment_webhook_secret'])) {
                $newPay['webhook_secret'] = trim($validated['payment_webhook_secret']);
            }
            $existingTenantSettings['payment'] = $newPay;

            $tenant->update([
                'network_settings' => $newNet,
                'settings'         => $existingTenantSettings,
            ]);
        }

        unset(
            $validated['mikrotik_driver'], $validated['mikrotik_host'], $validated['mikrotik_port'],
            $validated['mikrotik_user'], $validated['mikrotik_password'], $validated['mikrotik_use_ssl'],
            $validated['olt_driver'], $validated['genieacs_host'], $validated['genieacs_port'],
            $validated['require_hardware_olt_validation'],
            $validated['payment_driver'], $validated['payment_server_key'], $validated['payment_webhook_secret'], $validated['payment_is_production']
        );

        $validated['auto_assign'] = $request->boolean('auto_assign') ? '1' : '0';
        $validated['require_proof_photo'] = $request->boolean('require_proof_photo') ? '1' : '0';
        $validated['require_customer_signature'] = $request->boolean('require_customer_signature') ? '1' : '0';
        $validated['require_opm_reading'] = $request->boolean('require_opm_reading') ? '1' : '0';
        $validated['opm_max_attenuation_dbm'] = (string) ($validated['opm_max_attenuation_dbm'] ?? '-27.0');
        $validated['notify_emergency_tickets'] = $request->boolean('notify_emergency_tickets') ? '1' : '0';
        $validated['notify_task_completed'] = $request->boolean('notify_task_completed') ? '1' : '0';
        $validated['notify_customer_whatsapp'] = $request->boolean('notify_customer_whatsapp') ? '1' : '0';
        $validated['notify_customer_email'] = $request->boolean('notify_customer_email') ? '1' : '0';
        $validated['currency'] = $validated['currency'] ?? 'USD';
        $validated['default_country_code'] = $validated['default_country_code'] ?: '62';
        $validated['webhook_url'] = trim((string) ($validated['webhook_url'] ?? ''));
        $validated['whatsapp_gateway_url'] = trim((string) ($validated['whatsapp_gateway_url'] ?? ''));
        $validated['whatsapp_gateway_token'] = trim((string) ($validated['whatsapp_gateway_token'] ?? ''));
        $validated['fcm_server_key'] = trim((string) ($validated['fcm_server_key'] ?? ''));

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

        // Always keep the latest 50 points per technician within CURRENT TENANT only
        $technicianIds = TechnicianLocation::whereHas('technician')
            ->select('technician_id')
            ->distinct()
            ->pluck('technician_id');
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
        $deleted = TechnicianLocation::whereHas('technician')
            ->whereRaw('COALESCE(captured_at, created_at) < ?', [$cutoff->format('Y-m-d H:i:s')])
            ->when(!empty($protectedIds), fn ($q) => $q->whereNotIn('id', $protectedIds))
            ->delete();

        $totalAfter = TechnicianLocation::whereHas('technician')->count();
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

    /**
     * Trigger an immediate SLA breach escalation sweep manually from Admin Settings.
     */
    public function escalateSlaNow()
    {
        $escalated = app(\App\Services\SlaEscalationService::class)->runEscalationSweep();

        return back()->with(
            'success',
            $escalated > 0
                ? "SLA escalation sweep complete: {$escalated} overdue work order(s) escalated."
                : 'SLA escalation sweep complete: all active work orders are within SLA thresholds.'
        );
    }

    /**
     * Add a MikroTik POP router to this tenant's Multi-Router Fleet (password encrypted AES-256 at rest).
     */
    public function storeRouter(Request $request)
    {
        // Normalize router code to uppercase BEFORE uniqueness validation to prevent case-sensitivity unique crashes
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code', ''))),
        ]);

        $tenantId = TenantContext::id();
        $uniqueCode = Rule::unique('tenant_routers', 'code');
        if ($tenantId !== null) {
            $uniqueCode->where('tenant_id', $tenantId);
        }

        $validated = $request->validate([
            'name'              => 'required|string|max:140',
            'code'              => ['required', 'alpha_dash', 'max:40', $uniqueCode],
            'driver'            => 'required|in:simulator,rest',
            'host'              => 'nullable|string|max:255',
            'port'              => 'nullable|integer|min:1|max:65535',
            'username'          => 'nullable|string|max:120',
            'password'          => 'nullable|string|max:255',
            'isolation_profile' => 'nullable|string|max:80',
            'use_ssl'           => 'nullable|in:0,1',
            'is_default'        => 'nullable|in:0,1',
        ]);

        if ($validated['driver'] === 'rest') {
            $host = strtolower(trim((string) ($validated['host'] ?? ''), '[] '));
            $blocked = ['localhost', '127.0.0.1', '0.0.0.0', '::1', '169.254.169.254', 'metadata.google.internal'];
            if ($host === '' || in_array($host, $blocked, true) || str_starts_with($host, '169.254.')) {
                return back()->with('error', 'Router host is required and cannot point to loopback or cloud metadata endpoints (SSRF guard).');
            }
        }

        $router = DB::transaction(function () use ($request, $validated, $tenantId) {
            $isDefault = $request->boolean('is_default') || TenantRouter::count() === 0;
            if ($isDefault) {
                TenantRouter::query()->update(['is_default' => false]);
            }

            return TenantRouter::create([
                'tenant_id'         => $tenantId ?? 1,
                'name'              => strip_tags(trim($validated['name'])),
                'code'              => strtoupper(trim($validated['code'])),
                'driver'            => $validated['driver'],
                'host'              => trim((string) ($validated['host'] ?? '')),
                'port'              => (int) ($validated['port'] ?? 443),
                'username'          => trim((string) ($validated['username'] ?? 'admin')),
                'password'          => $validated['password'] ?? '',
                'isolation_profile' => trim((string) ($validated['isolation_profile'] ?? 'ISOLIR')) ?: 'ISOLIR',
                'use_ssl'           => $request->boolean('use_ssl'),
                'is_default'        => $isDefault,
                'is_active'         => true,
            ]);
        });

        return back()->with('success', "POP Router '{$router->name}' ({$router->code}) saved with AES-256 encrypted credentials.");
    }

    /**
     * Test connectivity to a tenant's MikroTik POP router.
     */
    public function testRouter(TenantRouter $router)
    {
        $result = MikrotikService::forTenant()->testRouterConnection($router);
        if (!empty($result['ok'])) {
            return back()->with('success', "[{$router->code}] {$result['message']}");
        }

        return back()->with('error', "[{$router->code}] {$result['message']}");
    }

    /**
     * Remove a POP router from this tenant's fleet.
     */
    public function destroyRouter(TenantRouter $router)
    {
        $label = $router->name;
        $wasDefault = (bool) $router->is_default;
        $router->delete();

        if ($wasDefault) {
            $replacement = TenantRouter::where('is_active', true)->orderBy('id')->first();
            if ($replacement) {
                $replacement->update(['is_default' => true]);
            }
        }

        return back()->with('success', "POP Router '{$label}' removed.");
    }
}
