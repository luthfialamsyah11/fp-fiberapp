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
}
