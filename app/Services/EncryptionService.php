<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class EncryptionService
{
    /**
     * Encrypt sensitive data
     */
    public static function encrypt(string $value): string
    {
        try {
            return Crypt::encryptString($value);
        } catch (\Exception $e) {
            Log::error('Encryption failed: ' . $e->getMessage());
            throw new \Exception('Failed to encrypt sensitive data');
        }
    }

    /**
     * Decrypt sensitive data
     */
    public static function decrypt(string $encryptedValue): string
    {
        try {
            return Crypt::decryptString($encryptedValue);
        } catch (\Exception $e) {
            Log::error('Decryption failed: ' . $e->getMessage());
            throw new \Exception('Failed to decrypt sensitive data');
        }
    }

    /**
     * Check if value is encrypted
     */
    public static function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

