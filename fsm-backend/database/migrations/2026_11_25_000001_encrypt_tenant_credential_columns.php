<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen tenants.settings & tenants.network_settings to LONGTEXT on MySQL/MariaDB
     * so AES-256-CBC ciphertext can be stored safely, then encrypt any legacy plaintext rows.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        $driver = DB::getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            if (Schema::hasColumn('tenants', 'settings')) {
                DB::statement('ALTER TABLE tenants MODIFY COLUMN settings LONGTEXT NULL');
            }
            if (Schema::hasColumn('tenants', 'network_settings')) {
                DB::statement('ALTER TABLE tenants MODIFY COLUMN network_settings LONGTEXT NULL');
            }
        }

        // Backfill: encrypt any existing plaintext JSON values at-rest
        $rows = DB::table('tenants')->select(['id', 'settings', 'network_settings'])->get();
        foreach ($rows as $row) {
            $updates = [];
            foreach (['settings', 'network_settings'] as $col) {
                $val = $row->{$col};
                if ($val === null || $val === '') {
                    continue;
                }
                if (!$this->isAlreadyEncrypted((string) $val)) {
                    $updates[$col] = Crypt::encryptString((string) $val);
                }
            }
            if (!empty($updates)) {
                DB::table('tenants')->where('id', $row->id)->update($updates);
            }
        }
    }

    public function down(): void
    {
        // Intentionally preserve encrypted values at rest; no destructive downgrade to plaintext.
    }

    private function isAlreadyEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (DecryptException $e) {
            return false;
        }
    }
};
