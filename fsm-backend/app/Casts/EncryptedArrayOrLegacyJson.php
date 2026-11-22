<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Zero-downtime AES-256-CBC encrypted array cast with transparent fallback
 * for legacy plaintext JSON rows prior to backfill encryption.
 */
class EncryptedArrayOrLegacyJson implements CastsAttributes
{
    /**
     * Cast the given value from database storage into a PHP array.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $raw = (string) $value;

        // 1. Primary path: decrypt AES-256-CBC ciphertext
        try {
            $decrypted = Crypt::decryptString($raw);
            $decoded = json_decode($decrypted, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        } catch (DecryptException $e) {
            // Fall through to legacy plaintext JSON check
        }

        // 2. Fallback path: read legacy plaintext JSON safely
        $legacy = json_decode($raw, true);
        return is_array($legacy) ? $legacy : [];
    }

    /**
     * Prepare the given value for storage (always AES-256-CBC encrypted).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $arrayValue = is_array($value) ? $value : (json_decode((string) $value, true) ?: []);
        $json = json_encode($arrayValue, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return Crypt::encryptString($json === false ? '{}' : $json);
    }
}
