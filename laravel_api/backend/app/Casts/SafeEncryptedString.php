<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SafeEncryptedString implements CastsAttributes
{
    /**
     * Cast the given value for storage / reading.
     * Backward-compatible read: if value is already plaintext (not encrypted),
     * return it as-is rather than failing.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException) {
            // Backward-compatible read for live legacy plaintext rows
            return (string) $value;
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $stringValue = (string) $value;

        // Prevent double encryption if already a valid encrypted payload
        try {
            Crypt::decryptString($stringValue);
            return $stringValue;
        } catch (\Throwable) {
            return Crypt::encryptString($stringValue);
        }
    }
}
