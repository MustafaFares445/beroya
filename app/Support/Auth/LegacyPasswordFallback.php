<?php

namespace App\Support\Auth;

use Illuminate\Support\Facades\Hash;

/**
 * Temporary fallback for legacy/plain/encrypted passwords during migration.
 * Remove once all user passwords are stored with Laravel hashing.
 */
class LegacyPasswordFallback
{
    public static function verify(string $plainPassword, ?string $storedPassword): bool
    {
        if ($storedPassword === null || $storedPassword === '') {
            return false;
        }

        if (self::isHashedPassword($storedPassword) && Hash::check($plainPassword, $storedPassword)) {
            return true;
        }

        if (hash_equals($storedPassword, $plainPassword)) {
            return true;
        }

        $decrypted = self::decryptLegacyPassword($storedPassword);

        return $decrypted !== null && hash_equals($decrypted, $plainPassword);
    }

    public static function needsRehash(string $plainPassword, ?string $storedPassword): bool
    {
        if ($storedPassword === null || $storedPassword === '') {
            return false;
        }

        if (self::isHashedPassword($storedPassword) && Hash::check($plainPassword, $storedPassword)) {
            return Hash::needsRehash($storedPassword);
        }

        return self::verify($plainPassword, $storedPassword);
    }

    private static function isHashedPassword(string $storedPassword): bool
    {
        return str_starts_with($storedPassword, '$2y$')
            || str_starts_with($storedPassword, '$2a$')
            || str_starts_with($storedPassword, '$2b$')
            || str_starts_with($storedPassword, '$argon2');
    }

    private static function decryptLegacyPassword(string $encryptedText): ?string
    {
        $key = (string) config('legacy.encryption_key', '');

        if ($key === '' || mb_strlen($key) < 32) {
            return null;
        }

        $binaryKey = hash('sha256', $key, true);
        $decoded = base64_decode($encryptedText, true);

        if ($decoded === false || mb_strlen($decoded, '8bit') <= 16) {
            return null;
        }

        $iv = mb_substr($decoded, 0, 16, '8bit');
        $encrypted = mb_substr($decoded, 16, null, '8bit');
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $binaryKey, OPENSSL_RAW_DATA, $iv);

        return $decrypted === false ? null : $decrypted;
    }
}
