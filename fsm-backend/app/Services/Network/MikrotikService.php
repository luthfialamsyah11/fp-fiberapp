<?php

namespace App\Services\Network;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\TenantRouter;
use App\Services\Network\Drivers\MikrotikRestDriver;
use App\Services\Network\Drivers\MikrotikSimulatorDriver;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MikrotikService
{
    protected ?Tenant $tenant = null;
    protected ?MikrotikDriverInterface $driver = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant ?? TenantContext::getTenant();
    }

    public static function forTenant(?Tenant $tenant = null): self
    {
        return new self($tenant);
    }

    /**
     * Build a driver instance for a specific TenantRouter (multi-router POP architecture).
     */
    public function getDriverForRouter(?TenantRouter $router = null): MikrotikDriverInterface
    {
        if ($this->driver !== null && $router === null) {
            return $this->driver;
        }

        if ($router === null && $this->tenant && Schema::hasTable('tenant_routers')) {
            $router = TenantRouter::withoutGlobalScopes()
                ->where('tenant_id', $this->tenant->id)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
        }

        if ($router !== null) {
            if ($router->driver === 'rest') {
                return new MikrotikRestDriver([
                    'host'       => $router->host ?: '127.0.0.1',
                    'port'       => $router->port ?: 443,
                    'user'       => $router->username ?: 'admin',
                    'password'   => (string) ($router->password ?? ''),
                    'use_ssl'    => (bool) $router->use_ssl,
                    'verify_ssl' => (bool) $router->verify_ssl,
                    'timeout'    => 4,
                ]);
            }

            return new MikrotikSimulatorDriver([
                'host' => $router->host ?: $router->code,
            ]);
        }

        return $this->getDriver();
    }

    /**
     * Resolve the router driver assigned to a specific subscriber, falling back to default router.
     */
    public function getDriverForCustomer(Customer $customer): MikrotikDriverInterface
    {
        if ($this->driver !== null) {
            return $this->driver;
        }

        if (!empty($customer->router_id) && Schema::hasTable('tenant_routers')) {
            $router = TenantRouter::withoutGlobalScopes()
                ->where('id', $customer->router_id)
                ->where('tenant_id', $customer->tenant_id ?? $this->tenant?->id)
                ->where('is_active', true)
                ->first();
            if ($router) {
                return $this->getDriverForRouter($router);
            }
        }

        return $this->getDriverForRouter(null);
    }

    public function getDriver(): MikrotikDriverInterface
    {
        if ($this->driver !== null) {
            return $this->driver;
        }

        $settings = $this->tenant?->network_settings ?? [];
        $driverType = $settings['mikrotik_driver'] ?? config('services.mikrotik.driver', 'simulator');

        if ($driverType === 'rest') {
            $this->driver = new MikrotikRestDriver([
                'host'       => $settings['mikrotik_host'] ?? config('services.mikrotik.host', '127.0.0.1'),
                'port'       => $settings['mikrotik_port'] ?? config('services.mikrotik.port', 443),
                'user'       => $settings['mikrotik_user'] ?? config('services.mikrotik.user', 'admin'),
                'password'   => $settings['mikrotik_password'] ?? config('services.mikrotik.password', ''),
                'use_ssl'    => $settings['mikrotik_use_ssl'] ?? true,
                'verify_ssl' => $settings['mikrotik_verify_ssl'] ?? false,
                'timeout'    => $settings['mikrotik_timeout'] ?? 4,
            ]);
        } else {
            $this->driver = new MikrotikSimulatorDriver($settings);
        }

        return $this->driver;
    }

    /**
     * Override or mock driver directly (useful in unit testing).
     */
    public function setDriver(MikrotikDriverInterface $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Provision or update PPPoE credentials on MikroTik for a customer.
     */
    public function provisionCustomerPppoe(Customer $customer, ?string $password = null, ?string $profile = null): array
    {
        $username = $customer->pppoe_username ?: ('cid_' . ($customer->customer_code ?: $customer->id));
        $pass = $password ?: ('sec_' . \Illuminate\Support\Str::random(12));
        $plan = $profile ?: ($customer->service_plan ?: 'DEFAULT-PROFILE');

        $driver = $this->getDriverForCustomer($customer);
        $res = $driver->upsertSecret(
            $username,
            $pass,
            $plan,
            'pppoe',
            "Benteng Siber CID: {$customer->customer_code} - {$customer->name}"
        );

        if (empty($customer->pppoe_username)) {
            $customer->update(['pppoe_username' => $username]);
        }

        Log::info("MikroTik PPPoE provisioned for {$username} [Profile: {$plan}]");
        return $res;
    }

    /**
     * Isolate a customer due to unpaid bill or disciplinary block.
     * Changes secret profile to ISOLIR and kicks active session.
     */
    public function isolateCustomer(Customer $customer, ?string $isolationProfile = null): bool
    {
        $username = $customer->pppoe_username;
        if (empty($username)) {
            return false;
        }

        $profile = $isolationProfile ?: ($customer->router?->isolation_profile ?: 'ISOLIR');
        $driver = $this->getDriverForCustomer($customer);
        $secret = $driver->getSecret($username);
        $pass = $secret['password'] ?? ('sec_' . \Illuminate\Support\Str::random(12));

        // Update secret profile to ISOLIR
        $driver->upsertSecret(
            $username,
            $pass,
            $profile,
            'pppoe',
            "ISOLATED: {$customer->name} [CID: {$customer->customer_code}]"
        );

        // Disconnect current session to immediately enforce isolated walled garden
        $driver->removeActiveSession($username);

        $customer->update(['status' => 'isolated']);
        Log::info("Customer {$username} isolated on MikroTik [Profile: {$profile}]");

        return true;
    }

    /**
     * Un-isolate a customer immediately upon payment or ticket resolution.
     * Restores secret profile to service_plan and terminates active isolated session.
     */
    public function unIsolateCustomer(Customer $customer): bool
    {
        $username = $customer->pppoe_username;
        if (empty($username)) {
            return false;
        }

        $driver = $this->getDriverForCustomer($customer);
        $secret = $driver->getSecret($username);
        $pass = $secret['password'] ?? ('sec_' . \Illuminate\Support\Str::random(12));
        $normalProfile = $customer->service_plan ?: 'DEFAULT-PROFILE';

        // Restore normal profile
        $driver->upsertSecret(
            $username,
            $pass,
            $normalProfile,
            'pppoe',
            "ACTIVE: {$customer->name} [CID: {$customer->customer_code}]"
        );

        // Kick session to re-authenticate under high-speed active profile
        $driver->removeActiveSession($username);

        $customer->update(['status' => 'active']);
        Log::info("Customer {$username} un-isolated on MikroTik [Profile restored to {$normalProfile}]");

        return true;
    }

    /**
     * Retrieve live PPPoE status and active connection metrics.
     */
    public function getCustomerPppoeStatus(Customer $customer): array
    {
        $username = $customer->pppoe_username;
        if (empty($username)) {
            return ['status' => 'unprovisioned'];
        }

        $driver = $this->getDriverForCustomer($customer);
        $secret = $driver->getSecret($username);
        $active = $driver->getActiveSession($username);

        return [
            'username'     => $username,
            'is_secret_on' => (bool) $secret,
            'profile'      => $secret['profile'] ?? null,
            'is_online'    => (bool) $active,
            'ip_address'   => $active['address'] ?? null,
            'caller_id'    => $active['caller-id'] ?? null,
            'uptime'       => $active['uptime'] ?? null,
            'bytes_in'     => $active['bytes-in'] ?? 0,
            'bytes_out'    => $active['bytes-out'] ?? 0,
        ];
    }

    /**
     * Pull PPPoE secrets from a specific tenant router (or default router) for 1-click import.
     */
    public function listRouterSecrets(?TenantRouter $router = null, int $limit = 500): array
    {
        return $this->getDriverForRouter($router)->listSecrets($limit);
    }

    /**
     * Test connectivity to a specific tenant router and update health metadata.
     */
    public function testRouterConnection(TenantRouter $router): array
    {
        $result = $this->getDriverForRouter($router)->testConnection();
        $router->update([
            'last_tested_at'   => now(),
            'last_test_status' => !empty($result['ok']) ? 'connected' : 'failed',
        ]);
        return $result;
    }
}
