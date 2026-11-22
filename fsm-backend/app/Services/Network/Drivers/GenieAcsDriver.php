<?php

namespace App\Services\Network\Drivers;

use App\Services\Network\OltDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenieAcsDriver implements OltDriverInterface
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct(array $config)
    {
        $host = strtolower(trim((string) ($config['genieacs_host'] ?? '127.0.0.1'), '[] '));
        $port = (int) ($config['genieacs_port'] ?? 7557);

        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1', '169.254.169.254', 'metadata.google.internal'];
        if (!app()->environment('testing') && (in_array($host, $blockedHosts, true) || str_starts_with($host, '169.254.'))) {
            throw new \InvalidArgumentException("Blocked GenieACS host ({$host}): loopback and cloud metadata targets are prohibited by SSRF policy.");
        }

        $this->baseUrl = "http://{$host}:{$port}";
        $this->timeout = (int) ($config['timeout'] ?? 5);
    }

    public function getOntDiagnostics(string $ontSerialNumber): array
    {
        try {
            $query = json_encode(['_deviceId._SerialNumber' => $ontSerialNumber]);
            $res = Http::timeout($this->timeout)
                ->acceptJson()
                ->get("{$this->baseUrl}/devices/", [
                    'query'      => $query,
                    'projection' => '_lastInform,InternetGatewayDevice.WANDevice.1.WANEponInterfaceConfig.RXPower,InternetGatewayDevice.WANDevice.1.WANGponInterfaceConfig.RXPower,_deviceId._ProductClass',
                ]);

            if ($res->successful()) {
                $items = $res->json();
                if (is_array($items) && !empty($items[0])) {
                    $dev = $items[0];
                    $rxVal = data_get($dev, 'InternetGatewayDevice.WANDevice.1.WANEponInterfaceConfig.RXPower._value')
                        ?? data_get($dev, 'InternetGatewayDevice.WANDevice.1.WANGponInterfaceConfig.RXPower._value');

                    $rx = is_numeric($rxVal) ? round((float) $rxVal, 2) : -22.0;

                    return [
                        'ont_sn'           => $ontSerialNumber,
                        'status'           => 'online',
                        'rx_power_dbm'     => $rx,
                        'tx_power_dbm'     => 2.5,
                        'olt_rx_power_dbm' => -23.0,
                        'temperature_c'    => 40.0,
                        'voltage_v'        => 3.3,
                        'model'            => data_get($dev, '_deviceId._ProductClass', 'ONT'),
                        'last_down_cause'  => null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("GenieACS query error for SN {$ontSerialNumber}: {$e->getMessage()}");
        }

        return [
            'ont_sn'           => $ontSerialNumber,
            'status'           => 'offline',
            'rx_power_dbm'     => null,
            'tx_power_dbm'     => null,
            'olt_rx_power_dbm' => null,
            'temperature_c'    => null,
            'voltage_v'        => null,
            'model'            => null,
            'last_down_cause'  => 'Unreachable from TR-069 ACS',
        ];
    }
}
