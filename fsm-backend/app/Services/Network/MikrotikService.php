<?php

namespace App\Services\Network;

use App\Models\Customer;
use App\Models\Tenant;
use App\Services\Network\Drivers\MikrotikRestDriver;
use App\Services\Network\Drivers\MikrotikSimulatorDriver;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Log;

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
        $pass = $password ?: ('sec_' . substr(md5($username . 'bentengsiber'), 0, 8));
        $plan = $profile ?: ($customer->service_plan ?: 'DEFAULT-PROFILE');

        $driver = $this->getDriver();
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
    public function isolateCustomer(Customer $customer, string $isolationProfile = 'ISOLIR'): bool
    {
        $username = $customer->pppoe_username;
        if (empty($username)) {
            return false;
        }

        $driver = $this->getDriver();
        $secret = $driver->getSecret($username);
        $pass = $secret['password'] ?? 'sec_' . substr(md5($username), 0, 8);

        // Update secret profile to ISOLIR
        $driver->upsertSecret(
            $username,
            $pass,
            $isolationProfile,
            'pppoe',
            "ISOLATED: {$customer->name} [CID: {$customer->customer_code}]"
        );

        // Disconnect current session to immediately enforce isolated walled garden
        $driver->removeActiveSession($username);

        $customer->update(['status' => 'isolated']);
        Log::info("Customer {$username} isolated on MikroTik [Profile: {$isolationProfile}]");

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

        $driver = $this->getDriver();
        $secret = $driver->getSecret($username);
        $pass = $secret['password'] ?? 'sec_' . substr(md5($username), 0, 8);
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

        $driver = $this->getDriver();
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
}
