<?php

namespace App\Services\Network;

interface OltDriverInterface
{
    /**
     * Read live optical power diagnostics from ONT / OLT.
     *
     * @param string $ontSerialNumber
     * @return array{
     *     ont_sn: string,
     *     status: string, // online, offline, dying_gasp
     *     rx_power_dbm: ?float,
     *     tx_power_dbm: ?float,
     *     olt_rx_power_dbm: ?float,
     *     temperature_c: ?float,
     *     voltage_v: ?float,
     *     model: ?string,
     *     last_down_cause: ?string
     * }
     */
    public function getOntDiagnostics(string $ontSerialNumber): array;
}
