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
            'company_name'             => 'FiberOps Global Carrier',
            'support_email'            => 'noc@bentengsiber.com',
            'support_phone'            => '+1-800-555-0199',
            'currency'                 => 'USD',
            'default_country_code'     => '62',
            'gps_refresh_interval'     => '15',
            'map_style'                => 'openstreetmap',
            'auto_assign'              => '1',
            'sla_warning_hours'        => '2',
            'require_proof_photo'      => '1',
            'require_customer_signature'=> '1',
            'geofence_check_meters'    => '0',
            'webhook_url'              => '',
            'notify_emergency_tickets' => '1',
            'notify_task_completed'    => '1',
            'whatsapp_gateway_url'     => '',
            'whatsapp_gateway_token'   => '',
            'notify_customer_whatsapp' => '0',
            'notify_customer_email'    => '0',
            'fcm_server_key'           => '',
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

    public static function formatCurrency(float $amount): string
    {
        $currency = strtoupper((string) self::get('currency', 'USD'));
        return match ($currency) {
            'IDR', 'RP' => 'IDR ' . number_format($amount, 0, '.', ','),
            'EUR'       => '€' . number_format($amount, 2, '.', ','),
            'GBP'       => '£' . number_format($amount, 2, '.', ','),
            default     => '$' . number_format($amount, 2, '.', ','),
        };
    }

    /**
     * Calculate Haversine distance in meters between two GPS coordinates.
     */
    public static function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000.0; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));
        return $earthRadius * $c;
    }

    /**
     * Validate that a webhook URL is an external HTTP(S) target and does not resolve
     * to loopback, link-local, RFC1918 private, or reserved IP space (SSRF protection).
     */
    public static function isSafeExternalUrl(string $url): bool
    {
        $url = trim($url);
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');
        $host = strtolower(trim($parsed['host'] ?? '', '[]'));

        if (!in_array($scheme, ['http', 'https'], true) || empty($host)) {
            return false;
        }

        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1', '169.254.169.254', 'metadata.google.internal'];
        if (in_array($host, $blockedHosts, true) || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return false;
        }

        // If the host is an IP literal, check directly
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
        }

        // Resolve DNS A records and ensure none resolve to private/reserved ranges
        $resolvedIp = @gethostbyname($host);
        if ($resolvedIp && $resolvedIp !== $host) {
            if (filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Dispatch webhook notification safely without blocking or throwing
     */
    public static function dispatchWebhook(string $event, array $payload): bool
    {
        try {
            $settings = self::all();
            $webhookUrl = trim($settings['webhook_url'] ?? '');

            if (empty($webhookUrl) || str_contains($webhookUrl, 'bot-sample-token')) {
                return false;
            }

            if (!self::isSafeExternalUrl($webhookUrl)) {
                return false;
            }

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
