-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 10, 2026 at 05:34 PM
-- Server version: 8.0.44
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laravel`
--

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_reset_tokens_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2026_05_21_115422_add_role_to_users_table', 1),
(6, '2026_05_21_115422_create_tasks_table', 1),
(7, '2026_05_21_115423_create_progress_updates_table', 1),
(8, '2026_05_21_115423_create_proof_of_work_table', 1),
(9, '2026_05_21_115423_create_technician_locations_table', 1),
(10, '2026_05_21_182558_update_tasks_table_add_columns_and_fix_status', 1),
(11, '2026_06_10_165941_add_is_online_to_users_table', 2);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(1, 'App\\Models\\User', 2, 'auth_token', 'f9c693d443781e3dfc8087077ebe7281a7182ed4c7f64d8dc625141d476103a1', '[\"*\"]', '2026-06-03 08:38:23', NULL, '2026-06-03 08:10:10', '2026-06-03 08:38:23');

-- --------------------------------------------------------

--
-- Table structure for table `progress_updates`
--

CREATE TABLE `progress_updates` (
  `id` bigint UNSIGNED NOT NULL,
  `task_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `progress_percent` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `progress_updates`
--

INSERT INTO `progress_updates` (`id`, `task_id`, `user_id`, `note`, `progress_percent`, `created_at`, `updated_at`) VALUES
(1, 1, 8, 'Teknisi ditugaskan untuk investigasi kabel drop core.', 20, '2026-06-03 06:48:47', '2026-06-03 08:48:47'),
(2, 1, 8, 'Teknisi tiba di lokasi. Melakukan pengecekan redaman optik di ODP.', 50, '2026-06-03 07:18:47', '2026-06-03 08:48:47'),
(3, 1, 8, 'Ditemukan kabel drop core terjepit dahan pohon. Melakukan penyambungan ulang (splicing).', 80, '2026-06-03 07:48:47', '2026-06-03 08:48:47'),
(4, 1, 8, 'Penyambungan selesai. Redaman normal di -18dBm. Koneksi kembali up dan stabil.', 100, '2026-06-03 08:18:47', '2026-06-03 08:48:47'),
(5, 2, 9, 'Teknisi mempersiapkan ONT router dan kabel fiber.', 10, '2026-06-03 07:48:47', '2026-06-03 08:48:47'),
(6, 2, 9, 'Penarikan drop core dari ODP ke unit apartemen selesai.', 45, '2026-06-03 08:18:47', '2026-06-03 08:48:47'),
(7, 2, 9, 'Sedang melakukan instalasi router dan konfigurasi SSID WiFi.', 75, '2026-06-03 08:38:47', '2026-06-03 08:48:47'),
(8, 3, 10, 'Tugas diterima oleh teknisi Citra. Dijadwalkan kunjungan jam 14:00.', 25, '2026-06-03 08:03:47', '2026-06-03 08:48:47'),
(9, 6, 12, 'Tugas ditugaskan.', 20, '2026-06-03 04:48:47', '2026-06-03 08:48:47'),
(10, 6, 12, 'Teknisi mengecek connector patch cord di router, kotor terkena debu.', 60, '2026-06-03 05:48:47', '2026-06-03 08:48:47'),
(11, 6, 12, 'Melakukan pembersihan connector dengan alcohol wipes dan perapihan bend radius serat optik.', 90, '2026-06-03 06:48:47', '2026-06-03 08:48:47'),
(12, 6, 12, 'Redaman kembali normal di -21.5dBm. Pengujian ping stabil.', 100, '2026-06-03 07:48:47', '2026-06-03 08:48:47'),
(13, 7, 9, 'Ditolak teknisi karena stock adaptor 12V di gudang area sedang kosong. Dialihkan ke esok hari.', 10, '2026-06-03 05:28:47', '2026-06-03 08:48:47');

-- --------------------------------------------------------

--
-- Table structure for table `proof_of_work`
--

CREATE TABLE `proof_of_work` (
  `id` bigint UNSIGNED NOT NULL,
  `task_id` bigint UNSIGNED NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `proof_of_work`
--

INSERT INTO `proof_of_work` (`id`, `task_id`, `image`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'https://images.unsplash.com/photo-1600132806370-bf17e65e942f?w=500&auto=format&fit=crop&q=60', 'Foto kondisi SEBELUM perbaikan: Kendala jaringan diidentifikasi.', '2026-06-03 06:48:47', '2026-06-03 08:48:47'),
(2, 1, 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=500&auto=format&fit=crop&q=60', 'Foto kondisi SESUDAH perbaikan: Pekerjaan selesai, tes sinyal & kecepatan normal.', '2026-06-03 08:18:47', '2026-06-03 08:48:47'),
(3, 6, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=500&auto=format&fit=crop&q=60', 'Foto kondisi SEBELUM perbaikan: Kendala jaringan diidentifikasi.', '2026-06-03 06:48:47', '2026-06-03 08:48:47'),
(4, 6, 'https://images.unsplash.com/photo-1601524909162-be87252be298?w=500&auto=format&fit=crop&q=60', 'Foto kondisi SESUDAH perbaikan: Pekerjaan selesai, tes sinyal & kecepatan normal.', '2026-06-03 08:18:47', '2026-06-03 08:48:47');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` bigint UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `address` text COLLATE utf8mb4_unicode_ci,
  `location` text COLLATE utf8mb4_unicode_ci,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `technician_id` bigint UNSIGNED DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `additional_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `customer_name`, `customer_phone`, `description`, `address`, `location`, `latitude`, `longitude`, `technician_id`, `status`, `priority`, `scheduled_at`, `additional_notes`, `created_at`, `updated_at`) VALUES
(1, 'WiFi Loss of Signal - Red LOS Indicator', 'Budi Sudarsono', '081299887711', 'Pelanggan melaporkan lampu indikator LOS menyala merah pada router Huawei HG8245H. Koneksi terputus total sejak kemarin sore.', 'Jl. Kebon Sirih No. 12, Menteng, Jakarta Pusat', 'Jl. Kebon Sirih No. 12, Jakarta Pusat', '-6.18342100', '106.82849100', 8, 'completed', 'high', '2026-06-01 08:48:47', NULL, '2026-06-03 08:48:47', '2026-06-03 08:48:47'),
(2, 'Pemasangan Baru WiFi Fiber 50 Mbps', 'Lestari Indah', '085712345678', 'Instalasi baru layanan Wifi FiberOps 50Mbps. Memerlukan penarikan drop core sepanjang 40 meter dari ODP-SUD-04.', 'Apartemen Sudirman Tower Lt. 14 Unit B, SCBD', 'Sudirman Tower Lt. 14, SCBD, Jakarta Selatan', '-6.22631000', '106.81291000', 9, 'on-going', 'medium', '2026-06-03 08:48:47', NULL, '2026-06-03 08:48:47', '2026-06-03 08:48:47'),
(3, 'WiFi Sering Putus & Router Lambat', 'Susilo Bambang', '081344556677', 'Koneksi lambat saat digunakan lebih dari 5 perangkat. Router sering restart sendiri. Minta ganti router dual-band.', 'Jl. Palmerah Barat No. 88, Jakarta Barat', 'Jl. Palmerah Barat No. 88, Palmerah', '-6.19832000', '106.79124000', 10, 'on-going', 'low', '2026-06-03 10:48:47', NULL, '2026-06-03 08:48:47', '2026-06-10 09:31:02'),
(4, 'Migrasi ONT ke GPON Dual Band', 'PT Makmur Jaya', '0217654321', 'Upgrade perangkat ONT lama ke Fiberhome HG6145F dual band 2.4Ghz & 5Ghz untuk mendukung bandwidth kantor.', 'Gedung Menara Mulia Lantai 5, Gatot Subroto', 'Gedung Menara Mulia Lt. 5, Gatot Subroto', '-6.22230000', '106.81890000', 11, 'on-going', 'medium', '2026-06-04 08:48:47', NULL, '2026-06-03 08:48:47', '2026-06-10 09:34:23'),
(5, 'Kabel Drop Core Terputus Tabrakan Truk', 'Rian Hidayat', '089988887777', 'Kabel drop optik menjuntai ke jalan raya dan terputus akibat tersangkut muatan truk kontainer. Perlu penarikan kabel baru dari tiang ODP terdekat.', 'Jl. Kali Besar Barat No. 10, Kota Tua', 'Jl. Kali Besar Barat No. 10, Jakarta Barat', '-6.13600000', '106.81150000', NULL, 'pending', 'high', '2026-06-03 09:48:47', NULL, '2026-06-03 08:48:47', '2026-06-03 08:48:47'),
(6, 'Pengecekan Redaman Tinggi (High Attenuation)', 'Siti Aminah', '085211223344', 'Redaman optik terpantau naik mencapai -31dBm di sistem NMS (standar < -24dBm). Mengakibatkan packet loss tinggi dan game lag.', 'Perumahan Kebon Jeruk Indah Blok C/14', 'Kebon Jeruk Indah Blok C/14, Jakarta Barat', '-6.19150000', '106.77250000', 12, 'completed', 'medium', '2026-06-02 08:48:47', NULL, '2026-06-03 08:48:47', '2026-06-03 08:48:47'),
(7, 'WiFi Mati Total Pasca Hujan Badai', 'David Beckham', '087711223344', 'Setelah petir semalam, adaptor router hangus terbakar. Perlu penggantian adaptor ONT router 12V 1.5A.', 'Kebayoran Baru Residence kav 7A, Jakarta Selatan', 'Kebayoran Baru Residence, Kebayoran Baru', '-6.24150000', '106.80150000', 9, 'rejected', 'high', '2026-06-03 04:48:47', NULL, '2026-06-03 08:48:47', '2026-06-03 08:48:47'),
(8, 'jaringan nirkabel', 'ilal', '081234567890', 'ganti kabel jaringan', 'Jl. Galuh Mas Raya, Sukaharja, Telukjambe Timur, Karawang, Jawa Barat 41361', 'Jl. Galuh Mas Raya, Sukaharja, Telukjambe Timur, Karawang, Jawa Barat 41361', '-6.32727900', '107.29268000', 9, 'assigned', 'medium', '2026-06-04 02:27:00', NULL, '2026-06-03 19:32:08', '2026-06-03 19:38:39');

-- --------------------------------------------------------

--
-- Table structure for table `technician_locations`
--

CREATE TABLE `technician_locations` (
  `id` bigint UNSIGNED NOT NULL,
  `technician_id` bigint UNSIGNED NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `technician_locations`
--

INSERT INTO `technician_locations` (`id`, `technician_id`, `latitude`, `longitude`, `created_at`, `updated_at`) VALUES
(1, 8, '-6.18039200', '106.82215300', '2026-06-03 08:18:46', '2026-06-03 08:48:46'),
(2, 8, '-6.17739200', '106.82515300', '2026-06-03 08:33:46', '2026-06-03 08:48:46'),
(3, 8, '-6.17539200', '106.82715300', '2026-06-03 08:48:46', '2026-06-03 08:48:46'),
(4, 9, '-6.23472800', '106.81087800', '2026-06-03 08:18:46', '2026-06-03 08:48:46'),
(5, 9, '-6.23172800', '106.81387800', '2026-06-03 08:33:46', '2026-06-03 08:48:46'),
(6, 9, '-6.22972800', '106.81587800', '2026-06-03 08:48:46', '2026-06-03 08:48:46'),
(7, 10, '-6.20030100', '106.78983300', '2026-06-03 08:18:47', '2026-06-03 08:48:47'),
(8, 10, '-6.19730100', '106.79283300', '2026-06-03 08:33:47', '2026-06-03 08:48:47'),
(9, 10, '-6.19530100', '106.79483300', '2026-06-03 08:48:47', '2026-06-03 08:48:47'),
(10, 11, '-6.20685200', '106.84008300', '2026-06-03 08:18:47', '2026-06-03 08:48:47'),
(11, 11, '-6.20385200', '106.84308300', '2026-06-03 08:33:47', '2026-06-03 08:48:47'),
(12, 11, '-6.20185200', '106.84508300', '2026-06-03 08:48:47', '2026-06-03 08:48:47'),
(13, 12, '-6.13950200', '106.80830200', '2026-06-03 08:18:47', '2026-06-03 08:48:47'),
(14, 12, '-6.13650200', '106.81130200', '2026-06-03 08:33:47', '2026-06-03 08:48:47'),
(15, 12, '-6.13450200', '106.81330200', '2026-06-03 08:48:47', '2026-06-03 08:48:47'),
(16, 9, '-6.34716624', '107.34977220', '2026-06-10 09:26:16', '2026-06-10 09:26:16'),
(17, 9, '-6.34721694', '107.34981220', '2026-06-10 09:26:48', '2026-06-10 09:26:48'),
(18, 9, '-6.34721694', '107.34981220', '2026-06-10 09:27:17', '2026-06-10 09:27:17'),
(19, 9, '-6.34735481', '107.34972437', '2026-06-10 09:27:47', '2026-06-10 09:27:47'),
(20, 8, '-6.34736164', '107.34971601', '2026-06-10 09:28:28', '2026-06-10 09:28:28'),
(21, 8, '-6.34718761', '107.34980819', '2026-06-10 09:29:30', '2026-06-10 09:29:30'),
(22, 8, '-6.34715744', '107.34974035', '2026-06-10 09:29:59', '2026-06-10 09:29:59'),
(23, 10, '-6.34723502', '107.34979576', '2026-06-10 09:30:47', '2026-06-10 09:30:47'),
(24, 10, '-6.34717013', '107.34975617', '2026-06-10 09:31:20', '2026-06-10 09:31:20'),
(25, 10, '-6.34717013', '107.34975617', '2026-06-10 09:31:48', '2026-06-10 09:31:48'),
(26, 10, '-6.34720104', '107.34978001', '2026-06-10 09:32:08', '2026-06-10 09:32:08'),
(27, 10, '-6.34723390', '107.34978761', '2026-06-10 09:32:47', '2026-06-10 09:32:47'),
(28, 11, '-6.34710754', '107.34977439', '2026-06-10 09:33:22', '2026-06-10 09:33:22'),
(29, 11, '-6.34717478', '107.34977503', '2026-06-10 09:34:06', '2026-06-10 09:34:06'),
(30, 11, '-6.34735792', '107.34974059', '2026-06-10 09:34:39', '2026-06-10 09:34:39'),
(31, 11, '-6.34735792', '107.34974059', '2026-06-10 09:35:08', '2026-06-10 09:35:08'),
(32, 12, '-6.34723728', '107.34976007', '2026-06-10 09:35:54', '2026-06-10 09:35:54'),
(33, 12, '-6.34734779', '107.34976899', '2026-06-10 09:36:26', '2026-06-10 09:36:26'),
(34, 12, '-6.34734779', '107.34976899', '2026-06-10 09:36:55', '2026-06-10 09:36:55'),
(35, 12, '-6.34710735', '107.34976257', '2026-06-10 09:37:27', '2026-06-10 09:37:27'),
(36, 12, '-6.34710735', '107.34976257', '2026-06-10 09:37:55', '2026-06-10 09:37:55'),
(37, 12, '-6.34723851', '107.34980867', '2026-06-10 09:40:50', '2026-06-10 09:40:50'),
(38, 12, '-6.34719543', '107.34976617', '2026-06-10 09:41:23', '2026-06-10 09:41:23'),
(39, 12, '-6.34719543', '107.34976617', '2026-06-10 09:41:51', '2026-06-10 09:41:51'),
(40, 12, '-6.34717029', '107.34980752', '2026-06-10 09:42:23', '2026-06-10 09:42:23'),
(41, 12, '-6.34717029', '107.34980752', '2026-06-10 09:42:51', '2026-06-10 09:42:51'),
(42, 12, '-6.34720292', '107.34977882', '2026-06-10 09:51:15', '2026-06-10 09:51:15'),
(43, 12, '-6.34720292', '107.34977882', '2026-06-10 09:51:27', '2026-06-10 09:51:27'),
(44, 12, '-6.34736976', '107.34980130', '2026-06-10 09:51:59', '2026-06-10 09:51:59'),
(45, 12, '-6.34736976', '107.34980130', '2026-06-10 09:52:28', '2026-06-10 09:52:28'),
(46, 12, '-6.34733858', '107.34980070', '2026-06-10 09:53:00', '2026-06-10 09:53:00'),
(47, 12, '-6.34733858', '107.34980070', '2026-06-10 09:53:28', '2026-06-10 09:53:28'),
(48, 12, '-6.34730288', '107.34979814', '2026-06-10 09:53:59', '2026-06-10 09:53:59'),
(49, 12, '-6.34730288', '107.34979814', '2026-06-10 09:54:28', '2026-06-10 09:54:28'),
(50, 12, '-6.34716221', '107.34973384', '2026-06-10 09:55:00', '2026-06-10 09:55:00'),
(51, 12, '-6.34716221', '107.34973384', '2026-06-10 09:55:28', '2026-06-10 09:55:28'),
(52, 12, '-6.34712912', '107.34978247', '2026-06-10 09:56:00', '2026-06-10 09:56:00'),
(53, 12, '-6.34712912', '107.34978247', '2026-06-10 09:56:28', '2026-06-10 09:56:28'),
(54, 12, '-6.34712651', '107.34978971', '2026-06-10 09:57:31', '2026-06-10 09:57:31'),
(55, 12, '-6.34731228', '107.34977966', '2026-06-10 10:11:18', '2026-06-10 10:11:18'),
(56, 8, '-6.34719734', '107.34977798', '2026-06-10 10:15:50', '2026-06-10 10:15:50');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','technician') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'technician',
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_online` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `phone`, `is_active`, `is_online`, `remember_token`, `created_at`, `updated_at`) VALUES
(7, 'Admin FiberOps', 'admin@fsm.com', NULL, '$2y$12$rPEYToiX.Q2Zfz1QOUC6COguNetxw7P0t71lAAcqGM.UOcAhbQuAa', 'admin', '081122334455', 1, 1, 'Vtqk7nYHqp2fiEfeqJhNEW40NHT8nEKlHC2LMDkrleXzMl9XZbNfoEOrfJyI', '2026-06-03 08:48:46', '2026-06-03 08:48:46'),
(8, 'Alex Pratama', 'alex@fsm.com', NULL, '$2y$12$EpHd9d8ejPAKZa5yzx.0lu7j83VEcESOxSMTZD/moMituNyjTxE/2', 'technician', '081234567801', 1, 0, NULL, '2026-06-03 08:48:46', '2026-06-10 10:15:51'),
(9, 'Budi Santoso', 'budi@fsm.com', NULL, '$2y$12$IJCOpYSCgM.sVcY9.Go3mu8eSajlRQtqoOF29sNcTSGT2uK81gw/W', 'technician', '081234567802', 1, 1, NULL, '2026-06-03 08:48:46', '2026-06-03 08:48:46'),
(10, 'Citra Wijaya', 'citra@fsm.com', NULL, '$2y$12$hA3pMIdntjjHgFOLh.tGqOiQu7MseIa2PMMx7qZii/1lhdFuZhGLe', 'technician', '081234567803', 1, 1, NULL, '2026-06-03 08:48:47', '2026-06-03 08:48:47'),
(11, 'Dedi Kurniawan', 'dedi@fsm.com', NULL, '$2y$12$AdB5nI42wQy9zwzo5M4qxOF4YcGchRMl9qw8qJDCl7NfbqWOR98iW', 'technician', '081234567804', 1, 1, NULL, '2026-06-03 08:48:47', '2026-06-03 08:48:47'),
(12, 'Eka Putra', 'eka@fsm.com', NULL, '$2y$12$Gpx9lFTF3Kd2sljXSVx2x.OEWKuzHLcrLcPmwjJV.U04NBI9PMURi', 'technician', '081234567805', 1, 0, NULL, '2026-06-03 08:48:47', '2026-06-10 10:11:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `progress_updates`
--
ALTER TABLE `progress_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `progress_updates_task_id_foreign` (`task_id`),
  ADD KEY `progress_updates_user_id_foreign` (`user_id`);

--
-- Indexes for table `proof_of_work`
--
ALTER TABLE `proof_of_work`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proof_of_work_task_id_foreign` (`task_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tasks_technician_id_foreign` (`technician_id`);

--
-- Indexes for table `technician_locations`
--
ALTER TABLE `technician_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `technician_locations_technician_id_foreign` (`technician_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `progress_updates`
--
ALTER TABLE `progress_updates`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `proof_of_work`
--
ALTER TABLE `proof_of_work`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `technician_locations`
--
ALTER TABLE `technician_locations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `progress_updates`
--
ALTER TABLE `progress_updates`
  ADD CONSTRAINT `progress_updates_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `progress_updates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proof_of_work`
--
ALTER TABLE `proof_of_work`
  ADD CONSTRAINT `proof_of_work_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_technician_id_foreign` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `technician_locations`
--
ALTER TABLE `technician_locations`
  ADD CONSTRAINT `technician_locations_technician_id_foreign` FOREIGN KEY (`technician_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
