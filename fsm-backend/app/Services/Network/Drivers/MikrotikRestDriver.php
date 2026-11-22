<?php

namespace App\Services\Network\Drivers;

use App\Services\Network\MikrotikDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MikrotikRestDriver implements MikrotikDriverInterface
{
    protected string $baseUrl;
    protected string $user;
    protected string $pass;
    protected int $timeout;
    protected bool $verifySsl;

    public function __construct(array $config)
    {
        $host = strtolower(trim((string) ($config['host'] ?? '127.0.0.1'), '[] '));
        $port = (int) ($config['port'] ?? 443);
        $scheme = !empty($config['use_ssl']) ? 'https' : 'http';

        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1', '169.254.169.254', 'metadata.google.internal'];
        if (!app()->environment('testing') && (in_array($host, $blockedHosts, true) || str_starts_with($host, '169.254.'))) {
            throw new \InvalidArgumentException("Blocked MikroTik host ({$host}): loopback and cloud metadata targets are prohibited by SSRF policy.");
        }

        $this->baseUrl = "{$scheme}://{$host}:{$port}/rest";
        $this->user = $config['user'] ?? 'admin';
        $this->pass = $config['password'] ?? '';
        $this->timeout = (int) ($config['timeout'] ?? 5);
        $this->verifySsl = (bool) ($config['verify_ssl'] ?? false);
    }

    protected function client()
    {
        return Http::withBasicAuth($this->user, $this->pass)
            ->timeout($this->timeout)
            ->withOptions(['verify' => $this->verifySsl])
            ->acceptJson();
    }

    public function upsertSecret(string $username, string $password, string $profile, string $service = 'pppoe', ?string $comment = null): array
    {
        $existing = $this->getSecret($username);

        $payload = [
            'name'     => $username,
            'password' => $password,
            'profile'  => $profile,
            'service'  => $service,
            'comment'  => $comment ?? 'Benteng Siber FSM Managed',
        ];

        if ($existing && !empty($existing['.id'])) {
            $id = $existing['.id'];
            $res = $this->client()->patch("{$this->baseUrl}/ppp/secret/{$id}", $payload);
            if ($res->failed()) {
                Log::warning("MikroTik REST update secret failed: {$res->body()}");
            }
            return array_merge($existing, $payload);
        }

        $res = $this->client()->put("{$this->baseUrl}/ppp/secret", $payload);
        if ($res->failed()) {
            Log::warning("MikroTik REST create secret failed: {$res->body()}");
        }

        return $res->json() ?? $payload;
    }

    public function removeActiveSession(string $username): bool
    {
        $session = $this->getActiveSession($username);
        if (!$session || empty($session['.id'])) {
            return false;
        }

        $id = $session['.id'];
        $res = $this->client()->delete("{$this->baseUrl}/ppp/active/{$id}");
        return $res->successful();
    }

    public function getSecret(string $username): ?array
    {
        $res = $this->client()->get("{$this->baseUrl}/ppp/secret", ['name' => $username]);
        if ($res->successful()) {
            $data = $res->json();
            return is_array($data) && !empty($data[0]) ? $data[0] : null;
        }
        return null;
    }

    public function getActiveSession(string $username): ?array
    {
        $res = $this->client()->get("{$this->baseUrl}/ppp/active", ['name' => $username]);
        if ($res->successful()) {
            $data = $res->json();
            return is_array($data) && !empty($data[0]) ? $data[0] : null;
        }
        return null;
    }
}
