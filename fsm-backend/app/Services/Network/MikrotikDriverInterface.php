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

    /**
     * List existing PPPoE secrets from RouterOS for 1-click bulk subscriber import.
     *
     * @return array<int, array{name:string, profile:string, service?:string, comment?:string, disabled?:string}>
     */
    public function listSecrets(int $limit = 500): array;

    /**
     * Verify live API connectivity & return router identity metadata.
     *
     * @return array{ok:bool, identity?:string, message:string}
     */
    public function testConnection(): array;
}
