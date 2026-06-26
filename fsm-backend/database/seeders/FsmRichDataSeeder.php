<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Task;
use App\Models\ProgressUpdate;
use App\Models\ProofOfWork;
use App\Models\TechnicianLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FsmRichDataSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing FSM data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ProofOfWork::truncate();
        ProgressUpdate::truncate();
        TechnicianLocation::truncate();
        Task::truncate();
        User::where('role', 'technician')->delete();
        User::where('email', 'admin@fsm.com')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create Admin
        $admin = User::create([
            'name'      => 'Admin FiberOps',
            'email'     => 'admin@fsm.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'phone'     => '081122334455',
            'is_active' => true,
        ]);

        // Create Technicians
        $technicians = [
            [
                'name' => 'Alex Pratama',
                'email' => 'alex@fsm.com',
                'phone' => '081234567801',
                'lat' => -6.175392,
                'lng' => 106.827153, // Monas
            ],
            [
                'name' => 'Budi Santoso',
                'email' => 'budi@fsm.com',
                'phone' => '081234567802',
                'lat' => -6.229728,
                'lng' => 106.815878, // SCBD
            ],
            [
                'name' => 'Citra Wijaya',
                'email' => 'citra@fsm.com',
                'phone' => '081234567803',
                'lat' => -6.195301,
                'lng' => 106.794833, // Palmerah
            ],
            [
                'name' => 'Dedi Kurniawan',
                'email' => 'dedi@fsm.com',
                'phone' => '081234567804',
                'lat' => -6.201852,
                'lng' => 106.845083, // Manggarai
            ],
            [
                'name' => 'Eka Putra',
                'email' => 'eka@fsm.com',
                'phone' => '081234567805',
                'lat' => -6.134502,
                'lng' => 106.813302, // Kota Tua
            ]
        ];

        $techModels = [];
        foreach ($technicians as $tech) {
            $user = User::create([
                'name' => $tech['name'],
                'email' => $tech['email'],
                'password' => Hash::make('password123'),
                'role' => 'technician',
                'phone' => $tech['phone'],
                'is_active' => true,
            ]);

            // Save historical and current location
            // Seed a route trail (3 points)
            TechnicianLocation::create([
                'technician_id' => $user->id,
                'latitude' => $tech['lat'] - 0.005,
                'longitude' => $tech['lng'] - 0.005,
                'created_at' => Carbon::now()->subMinutes(30),
            ]);
            TechnicianLocation::create([
                'technician_id' => $user->id,
                'latitude' => $tech['lat'] - 0.002,
                'longitude' => $tech['lng'] - 0.002,
                'created_at' => Carbon::now()->subMinutes(15),
            ]);
            TechnicianLocation::create([
                'technician_id' => $user->id,
                'latitude' => $tech['lat'],
                'longitude' => $tech['lng'],
                'created_at' => Carbon::now(),
            ]);

            $techModels[] = $user;
        }

        // Mock photos from Unsplash for ISP/WiFi installation tasks
        $beforePhotos = [
            'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=500&auto=format&fit=crop&q=60', // messy office
            'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=500&auto=format&fit=crop&q=60', // server rack messy
            'https://images.unsplash.com/photo-1600132806370-bf17e65e942f?w=500&auto=format&fit=crop&q=60', // cables
        ];

        $afterPhotos = [
            'https://images.unsplash.com/photo-1563770660941-20978e870e26?w=500&auto=format&fit=crop&q=60', // clean wifi router
            'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=500&auto=format&fit=crop&q=60', // router glowing green
            'https://images.unsplash.com/photo-1601524909162-be87252be298?w=500&auto=format&fit=crop&q=60', // laptop speed test 100mbps
        ];

        // Tasks data
        $tasksData = [
            [
                'title' => 'WiFi Loss of Signal - Red LOS Indicator',
                'customer_name' => 'Budi Sudarsono',
                'customer_phone' => '081299887711',
                'address' => 'Jl. Kebon Sirih No. 12, Menteng, Jakarta Pusat',
                'location' => 'Jl. Kebon Sirih No. 12, Jakarta Pusat',
                'lat' => -6.183421,
                'lng' => 106.828491,
                'status' => 'completed',
                'priority' => 'high',
                'tech_index' => 0, // Alex
                'scheduled' => Carbon::now()->subDays(2),
                'desc' => 'Pelanggan melaporkan lampu indikator LOS menyala merah pada router Huawei HG8245H. Koneksi terputus total sejak kemarin sore.',
                'updates' => [
                    ['note' => 'Teknisi ditugaskan untuk investigasi kabel drop core.', 'percent' => 20, 'minutes_ago' => 120],
                    ['note' => 'Teknisi tiba di lokasi. Melakukan pengecekan redaman optik di ODP.', 'percent' => 50, 'minutes_ago' => 90],
                    ['note' => 'Ditemukan kabel drop core terjepit dahan pohon. Melakukan penyambungan ulang (splicing).', 'percent' => 80, 'minutes_ago' => 60],
                    ['note' => 'Penyambungan selesai. Redaman normal di -18dBm. Koneksi kembali up dan stabil.', 'percent' => 100, 'minutes_ago' => 30],
                ],
                'has_proof' => true,
                'before_img' => $beforePhotos[2],
                'after_img' => $afterPhotos[1],
            ],
            [
                'title' => 'Pemasangan Baru WiFi Fiber 50 Mbps',
                'customer_name' => 'Lestari Indah',
                'customer_phone' => '085712345678',
                'address' => 'Apartemen Sudirman Tower Lt. 14 Unit B, SCBD',
                'location' => 'Sudirman Tower Lt. 14, SCBD, Jakarta Selatan',
                'lat' => -6.226310,
                'lng' => 106.812910,
                'status' => 'on-going',
                'priority' => 'medium',
                'tech_index' => 1, // Budi
                'scheduled' => Carbon::now(),
                'desc' => 'Instalasi baru layanan Wifi FiberOps 50Mbps. Memerlukan penarikan drop core sepanjang 40 meter dari ODP-SUD-04.',
                'updates' => [
                    ['note' => 'Teknisi mempersiapkan ONT router dan kabel fiber.', 'percent' => 10, 'minutes_ago' => 60],
                    ['note' => 'Penarikan drop core dari ODP ke unit apartemen selesai.', 'percent' => 45, 'minutes_ago' => 30],
                    ['note' => 'Sedang melakukan instalasi router dan konfigurasi SSID WiFi.', 'percent' => 75, 'minutes_ago' => 10],
                ],
                'has_proof' => false,
            ],
            [
                'title' => 'WiFi Sering Putus & Router Lambat',
                'customer_name' => 'Susilo Bambang',
                'customer_phone' => '081344556677',
                'address' => 'Jl. Palmerah Barat No. 88, Jakarta Barat',
                'location' => 'Jl. Palmerah Barat No. 88, Palmerah',
                'lat' => -6.198320,
                'lng' => 106.791240,
                'status' => 'accepted',
                'priority' => 'low',
                'tech_index' => 2, // Citra
                'scheduled' => Carbon::now()->addHours(2),
                'desc' => 'Koneksi lambat saat digunakan lebih dari 5 perangkat. Router sering restart sendiri. Minta ganti router dual-band.',
                'updates' => [
                    ['note' => 'Tugas diterima oleh teknisi Citra. Dijadwalkan kunjungan jam 14:00.', 'percent' => 25, 'minutes_ago' => 45],
                ],
                'has_proof' => false,
            ],
            [
                'title' => 'Migrasi ONT ke GPON Dual Band',
                'customer_name' => 'PT Makmur Jaya',
                'customer_phone' => '0217654321',
                'address' => 'Gedung Menara Mulia Lantai 5, Gatot Subroto',
                'location' => 'Gedung Menara Mulia Lt. 5, Gatot Subroto',
                'lat' => -6.222300,
                'lng' => 106.818900,
                'status' => 'assigned',
                'priority' => 'medium',
                'tech_index' => 3, // Dedi
                'scheduled' => Carbon::now()->addDays(1),
                'desc' => 'Upgrade perangkat ONT lama ke Fiberhome HG6145F dual band 2.4Ghz & 5Ghz untuk mendukung bandwidth kantor.',
                'updates' => [],
                'has_proof' => false,
            ],
            [
                'title' => 'Kabel Drop Core Terputus Tabrakan Truk',
                'customer_name' => 'Rian Hidayat',
                'customer_phone' => '089988887777',
                'address' => 'Jl. Kali Besar Barat No. 10, Kota Tua',
                'location' => 'Jl. Kali Besar Barat No. 10, Jakarta Barat',
                'lat' => -6.136000,
                'lng' => 106.811500,
                'status' => 'pending',
                'priority' => 'high',
                'tech_index' => null, // Unassigned
                'scheduled' => Carbon::now()->addHours(1),
                'desc' => 'Kabel drop optik menjuntai ke jalan raya dan terputus akibat tersangkut muatan truk kontainer. Perlu penarikan kabel baru dari tiang ODP terdekat.',
                'updates' => [],
                'has_proof' => false,
            ],
            [
                'title' => 'Pengecekan Redaman Tinggi (High Attenuation)',
                'customer_name' => 'Siti Aminah',
                'customer_phone' => '085211223344',
                'address' => 'Perumahan Kebon Jeruk Indah Blok C/14',
                'location' => 'Kebon Jeruk Indah Blok C/14, Jakarta Barat',
                'lat' => -6.191500,
                'lng' => 106.772500,
                'status' => 'completed',
                'priority' => 'medium',
                'tech_index' => 4, // Eka
                'scheduled' => Carbon::now()->subDays(1),
                'desc' => 'Redaman optik terpantau naik mencapai -31dBm di sistem NMS (standar < -24dBm). Mengakibatkan packet loss tinggi dan game lag.',
                'updates' => [
                    ['note' => 'Tugas ditugaskan.', 'percent' => 20, 'minutes_ago' => 240],
                    ['note' => 'Teknisi mengecek connector patch cord di router, kotor terkena debu.', 'percent' => 60, 'minutes_ago' => 180],
                    ['note' => 'Melakukan pembersihan connector dengan alcohol wipes dan perapihan bend radius serat optik.', 'percent' => 90, 'minutes_ago' => 120],
                    ['note' => 'Redaman kembali normal di -21.5dBm. Pengujian ping stabil.', 'percent' => 100, 'minutes_ago' => 60]
                ],
                'has_proof' => true,
                'before_img' => $beforePhotos[0],
                'after_img' => $afterPhotos[2],
            ],
            [
                'title' => 'WiFi Mati Total Pasca Hujan Badai',
                'customer_name' => 'David Beckham',
                'customer_phone' => '087711223344',
                'address' => 'Kebayoran Baru Residence kav 7A, Jakarta Selatan',
                'location' => 'Kebayoran Baru Residence, Kebayoran Baru',
                'lat' => -6.241500,
                'lng' => 106.801500,
                'status' => 'rejected',
                'priority' => 'high',
                'tech_index' => 1, // Budi
                'scheduled' => Carbon::now()->subHours(4),
                'desc' => 'Setelah petir semalam, adaptor router hangus terbakar. Perlu penggantian adaptor ONT router 12V 1.5A.',
                'updates' => [
                    ['note' => 'Ditolak teknisi karena stock adaptor 12V di gudang area sedang kosong. Dialihkan ke esok hari.', 'percent' => 10, 'minutes_ago' => 200]
                ],
                'has_proof' => false,
            ]
        ];

        foreach ($tasksData as $data) {
            $techId = $data['tech_index'] !== null ? $techModels[$data['tech_index']]->id : null;

            $task = Task::create([
                'title' => $data['title'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'description' => $data['desc'],
                'address' => $data['address'],
                'location' => $data['location'],
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'technician_id' => $techId,
                'status' => $data['status'],
                'priority' => $data['priority'],
                'scheduled_at' => $data['scheduled'],
            ]);

            // Seed updates
            foreach ($data['updates'] as $update) {
                ProgressUpdate::create([
                    'task_id' => $task->id,
                    'user_id' => $techId ?? $admin->id,
                    'note' => $update['note'],
                    'progress_percent' => $update['percent'],
                    'created_at' => Carbon::now()->subMinutes($update['minutes_ago']),
                ]);
            }

            // Seed proof of work
            if ($data['has_proof']) {
                // Before
                ProofOfWork::create([
                    'task_id' => $task->id,
                    'image' => $data['before_img'],
                    'description' => 'Foto kondisi SEBELUM perbaikan: Kendala jaringan diidentifikasi.',
                    'created_at' => Carbon::now()->subMinutes(120),
                ]);

                // After
                ProofOfWork::create([
                    'task_id' => $task->id,
                    'image' => $data['after_img'],
                    'description' => 'Foto kondisi SESUDAH perbaikan: Pekerjaan selesai, tes sinyal & kecepatan normal.',
                    'created_at' => Carbon::now()->subMinutes(30),
                ]);
            }
        }
    }
}
