<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SettingService
{
    public static function getFilePath()
    {
        return storage_path('app/settings.json');
    }

    public static function all()
    {
        $defaults = [
            'company_name'             => 'FiberOps Indonesia',
            'support_email'            => 'support@fiberops.net',
            'support_phone'            => '021-9988-7766',
            'gps_refresh_interval'     => '15',
            'map_style'                => 'openstreetmap',
            'auto_assign'              => '1',
            'sla_warning_hours'        => '2',
            'require_proof_photo'      => '1',
            'geofence_check_meters'    => '100',
            'webhook_url'              => '',
            'notify_emergency_tickets' => '1',
            'notify_task_completed'    => '1',
            'telemetry_retention_days' => '30',
        ];

        $filePath = self::getFilePath();
        if (File::exists($filePath)) {
            try {
                $saved = json_decode(File::get($filePath), true);
                if (is_array($saved)) {
                    return array_merge($defaults, $saved);
                }
            } catch (\Exception $e) {
                // Fallback to defaults
            }
        }

        return $defaults;
    }

    public static function get($key, $default = null)
    {
        $settings = self::all();
        return $settings[$key] ?? $default;
    }

    /**
     * Dispatch webhook notification safely without blocking or throwing
     */
    public static function dispatchWebhook(string $event, array $payload): bool
    {
        try {
            $settings = self::all();
            $webhookUrl = trim($settings['webhook_url'] ?? '');

            if (empty($webhookUrl)) {
                return false;
            }

            // Check trigger filters
            if ($event === 'TASK_EMERGENCY_CREATED' && empty($settings['notify_emergency_tickets'])) {
                return false;
            }
            if ($event === 'TASK_COMPLETED' && empty($settings['notify_task_completed'])) {
                return false;
            }

            $body = [
                'event'       => $event,
                'source'      => 'FiberOps FSM Dispatcher',
                'timestamp'   => now()->toIso8601String(),
                'server_time' => now()->format('Y-m-d H:i:s'),
                'data'        => $payload,
            ];

            // Send with short 2.5s timeout so user action is never delayed
            Http::timeout(2.5)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($webhookUrl, $body);

            return true;
        } catch (\Exception $e) {
            Log::warning("[WebhookService] Dispatch failed for {$event}: " . $e->getMessage());
            return false;
        }
    }
}
