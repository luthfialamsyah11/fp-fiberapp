<?php

namespace App\Services\Network\Drivers;

use App\Services\Network\MikrotikDriverInterface;

class MikrotikSimulatorDriver implements MikrotikDriverInterface
{
    /**
     * In-memory storage for test/simulator runs.
     * @var array<string, array>
     */
    protected static array $secrets = [];

    /**
     * In-memory active sessions.
     * @var array<string, array>
     */
    protected static array $sessions = [];

    public function __construct(protected array $config = [])
    {
    }

    public static function reset(): void
    {
        self::$secrets = [];
        self::$sessions = [];
    }

    public function upsertSecret(string $username, string $password, string $profile, string $service = 'pppoe', ?string $comment = null): array
    {
        $record = [
            'name'       => $username,
            'password'   => $password,
            'profile'    => $profile,
            'service'    => $service,
            'comment'    => $comment ?? 'Benteng Siber Auto-Managed',
            'disabled'   => 'false',
            'updated_at' => now()->toIso8601String(),
        ];

        self::$secrets[$username] = $record;

        // Auto-create an active session in simulator
        self::$sessions[$username] = [
            'name'       => $username,
            'service'    => $service,
            'caller-id'  => '48:8F:5A:' . strtoupper(substr(md5($username), 0, 6)),
            'address'    => '100.64.' . rand(10, 250) . '.' . rand(2, 254),
            'uptime'     => '2h15m40s',
            'bytes-in'   => 104857600,
            'bytes-out'  => 524288000,
        ];

        return $record;
    }

    public function removeActiveSession(string $username): bool
    {
        if (isset(self::$sessions[$username])) {
            unset(self::$sessions[$username]);
            return true;
        }
        return false;
    }

    public function getSecret(string $username): ?array
    {
        return self::$secrets[$username] ?? null;
    }

    public function getActiveSession(string $username): ?array
    {
        return self::$sessions[$username] ?? null;
    }

    public function listSecrets(int $limit = 500): array
    {
        if (!empty(self::$secrets)) {
            return array_slice(array_values(self::$secrets), 0, max(1, $limit));
        }

        // Realistic sample secrets for instant zero-config sandbox demos
        return array_slice([
            ['name' => 'pppoe_downtown_01', 'profile' => 'Fiber-100Mbps', 'service' => 'pppoe', 'comment' => 'Apex Coffee Roasters | +14155550101', 'disabled' => 'false'],
            ['name' => 'pppoe_downtown_02', 'profile' => 'Fiber-300Mbps', 'service' => 'pppoe', 'comment' => 'Harbor Dental Clinic | +14155550102', 'disabled' => 'false'],
            ['name' => 'pppoe_northpop_03', 'profile' => 'ISOLIR', 'service' => 'pppoe', 'comment' => 'Metro Logistics Hub | +14155550103', 'disabled' => 'false'],
        ], 0, max(1, $limit));
    }

    public function testConnection(): array
    {
        $host = $this->config['host'] ?? 'simulator.local';
        return [
            'ok'       => true,
            'identity' => 'MikroTik-CCR2004-SIM (' . $host . ')',
            'message'  => 'Simulator RouterOS v7 REST connection verified (0.4ms).',
        ];
    }
}
