<?php

namespace App\Services\Network;

use App\Models\Tenant;
use App\Services\Network\Drivers\GenieAcsDriver;
use App\Services\Network\Drivers\OltSimulatorDriver;
use App\Services\TenantContext;
use Illuminate\Validation\ValidationException;

class OltService
{
    protected ?Tenant $tenant = null;
    protected ?OltDriverInterface $driver = null;

    public function __construct(?Tenant $tenant = null)
    {
        $this->tenant = $tenant ?? TenantContext::getTenant();
    }

    public static function forTenant(?Tenant $tenant = null): self
    {
        return new self($tenant);
    }

    public function getDriver(): OltDriverInterface
    {
        if ($this->driver !== null) {
            return $this->driver;
        }

        $settings = $this->tenant?->network_settings ?? [];
        $driverType = $settings['olt_driver'] ?? config('services.olt.driver', 'simulator');

        if ($driverType === 'genieacs') {
            $this->driver = new GenieAcsDriver($settings);
        } else {
            $this->driver = new OltSimulatorDriver($settings);
        }

        return $this->driver;
    }

    public function setDriver(OltDriverInterface $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Get live ONT optical telemetry from OLT / GenieACS.
     */
    public function getOntOpticalStatus(string $ontSerialNumber): array
    {
        return $this->getDriver()->getOntDiagnostics(trim($ontSerialNumber));
    }

    /**
     * Validate ONT physical optical power against carrier tolerance threshold (e.g. -27.0 dBm).
     * Throws ValidationException if ONT is offline or RX power is out of spec.
     *
     * @throws ValidationException
     */
    public function assertOpticalHealth(string $ontSerialNumber, float $threshold = -27.0): array
    {
        $diag = $this->getOntOpticalStatus($ontSerialNumber);

        if ($diag['status'] !== 'online') {
            $cause = $diag['last_down_cause'] ?: 'Loss of Signal';
            throw ValidationException::withMessages([
                'optical_power_rx_dbm' => [
                    "Hardware OLT/ONT [SN: {$ontSerialNumber}] terdeteksi OFFLINE ({$cause}). Pastikan kabel dropcore dan patchcord terhubung sebelum menyelesaikan tiket."
                ]
            ]);
        }

        $rx = $diag['rx_power_dbm'];
        if ($rx === null || (float) $rx < $threshold) {
            $rxDisplay = $rx !== null ? "{$rx} dBm" : "N/A";
            throw ValidationException::withMessages([
                'optical_power_rx_dbm' => [
                    "Redaman fisik optik live dari hardware ({$rxDisplay}) melebihi batas toleransi kualitas ({$threshold} dBm). Harap lakukan re-splicing serat optik sebelum menyelesaikan pekerjaan."
                ]
            ]);
        }

        return $diag;
    }
}
