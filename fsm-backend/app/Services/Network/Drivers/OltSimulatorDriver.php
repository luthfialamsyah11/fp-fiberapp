<?php

namespace App\Services\Network\Drivers;

use App\Services\Network\OltDriverInterface;

class OltSimulatorDriver implements OltDriverInterface
{
    /**
     * Preset mocks for specific serial numbers during tests.
     * @var array<string, array>
     */
    protected static array $mockedDiagnostics = [];

    public function __construct(protected array $config = [])
    {
    }

    public static function setMock(string $sn, array $data): void
    {
        self::$mockedDiagnostics[$sn] = $data;
    }

    public static function reset(): void
    {
        self::$mockedDiagnostics = [];
    }

    public function getOntDiagnostics(string $ontSerialNumber): array
    {
        if (isset(self::$mockedDiagnostics[$ontSerialNumber])) {
            return array_merge([
                'ont_sn'           => $ontSerialNumber,
                'status'           => 'online',
                'rx_power_dbm'     => -21.40,
                'tx_power_dbm'     => 2.50,
                'olt_rx_power_dbm' => -22.10,
                'temperature_c'    => 42.0,
                'voltage_v'        => 3.3,
                'model'            => 'Benteng-Simulated-ONT-v1',
                'last_down_cause'  => null,
            ], self::$mockedDiagnostics[$ontSerialNumber]);
        }

        // Special test convention: if SN contains "OFFLINE", return offline
        if (str_contains(strtoupper($ontSerialNumber), 'OFFLINE')) {
            return [
                'ont_sn'           => $ontSerialNumber,
                'status'           => 'offline',
                'rx_power_dbm'     => null,
                'tx_power_dbm'     => null,
                'olt_rx_power_dbm' => null,
                'temperature_c'    => null,
                'voltage_v'        => null,
                'model'            => 'ZTE-F609-Offline',
                'last_down_cause'  => 'LOS (Loss of Signal)',
            ];
        }

        // Special test convention: if SN contains "BADLOSS", return poor optical power (-31.2 dBm)
        if (str_contains(strtoupper($ontSerialNumber), 'BADLOSS')) {
            return [
                'ont_sn'           => $ontSerialNumber,
                'status'           => 'online',
                'rx_power_dbm'     => -31.20,
                'tx_power_dbm'     => 1.10,
                'olt_rx_power_dbm' => -32.50,
                'temperature_c'    => 46.5,
                'voltage_v'        => 3.25,
                'model'            => 'Huawei-HG8245H-BadLoss',
                'last_down_cause'  => null,
            ];
        }

        // Default healthy ONT telemetry
        return [
            'ont_sn'           => $ontSerialNumber,
            'status'           => 'online',
            'rx_power_dbm'     => -21.80,
            'tx_power_dbm'     => 2.45,
            'olt_rx_power_dbm' => -22.30,
            'temperature_c'    => 41.2,
            'voltage_v'        => 3.31,
            'model'            => 'Benteng-Standard-ONT-F670L',
            'last_down_cause'  => null,
        ];
    }
}
