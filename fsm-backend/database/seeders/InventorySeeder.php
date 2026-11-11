<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventoryItem;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'code' => 'MAT-DC-1C',
                'name' => 'Kabel Dropcore Fiber Optic 1 Core G.657A',
                'unit' => 'meter',
                'stock' => 2500,
                'unit_price' => 1500,
                'category' => 'material',
                'description' => 'Kabel dropcore aerial 1 core kawat baja pelindung',
                'is_active' => true,
            ],
            [
                'code' => 'DEV-ONT-HW',
                'name' => 'Modem ONT Huawei EchoLife HG8245H5 Dual-Band',
                'unit' => 'unit',
                'stock' => 35,
                'unit_price' => 350000,
                'category' => 'device',
                'description' => 'ONT GPON Gigabit 4 GE + 2 POTS + WiFi AC',
                'is_active' => true,
            ],
            [
                'code' => 'DEV-ONT-ZTE',
                'name' => 'Modem ONT ZTE F609 GPON V3',
                'unit' => 'unit',
                'stock' => 25,
                'unit_price' => 280000,
                'category' => 'device',
                'description' => 'ONT GPON 4 Port LAN + 1 Telp + WiFi b/g/n',
                'is_active' => true,
            ],
            [
                'code' => 'MAT-FC-SCUPC',
                'name' => 'Fast Connector SC/UPC Biru Quick Assembly',
                'unit' => 'pcs',
                'stock' => 250,
                'unit_price' => 5000,
                'category' => 'material',
                'description' => 'Konektor mekanik tanpa fusion splicer',
                'is_active' => true,
            ],
            [
                'code' => 'MAT-ROSET-1P',
                'name' => 'Roset Kotak Terminasi Fiber Optic 1 Port',
                'unit' => 'pcs',
                'stock' => 100,
                'unit_price' => 12000,
                'category' => 'material',
                'description' => 'Wall outlet indoor FTTH 1 port adaptor SC',
                'is_active' => true,
            ],
            [
                'code' => 'MAT-PATCH-3M',
                'name' => 'Patch Cord SC/UPC to SC/UPC Simplex 3 Meter',
                'unit' => 'pcs',
                'stock' => 75,
                'unit_price' => 18000,
                'category' => 'material',
                'description' => 'Kabel jumper roset ke ONT modem',
                'is_active' => true,
            ],
            [
                'code' => 'MAT-SCLAMP',
                'name' => 'S-Clamp / Dropwire Tension Clamp Tiang',
                'unit' => 'pcs',
                'stock' => 150,
                'unit_price' => 7500,
                'category' => 'material',
                'description' => 'Klem penarik kabel dropcore pada tiang distribusi',
                'is_active' => true,
            ],
            [
                'code' => 'MAT-PROT-SLV',
                'name' => 'Protection Sleeve Sambungan Fiber 60mm',
                'unit' => 'pcs',
                'stock' => 400,
                'unit_price' => 1000,
                'category' => 'material',
                'description' => 'Selongsong pelindung core hasil splicing',
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            InventoryItem::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }
}
