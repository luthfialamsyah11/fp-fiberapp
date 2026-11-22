<?php

namespace App\Services\Network;

interface MikrotikDriverInterface
{
    /**
     * Create or update PPPoE secret on RouterOS.
     */
    public function upsertSecret(string $username, string $password, string $profile, string $service = 'pppoe', ?string $comment = null): array;

    /**
     * Terminate active PPPoE session for a username so they reconnect under a new profile.
     */
    public function removeActiveSession(string $username): bool;

    /**
     * Get secret details for a username.
     */
    public function getSecret(string $username): ?array;

    /**
     * Get active session info (uptime, ip, caller-id, rx/tx bytes).
     */
    public function getActiveSession(string $username): ?array;
}
