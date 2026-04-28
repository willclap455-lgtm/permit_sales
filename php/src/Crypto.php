<?php

declare(strict_types=1);

namespace PermitSales;

/**
 * AES-256-GCM helpers for application-level credit-card encryption.
 *
 * The key is taken from CARD_ENCRYPTION_KEY, decoded as base64. Each ciphertext
 * column is paired with its IV and 16-byte auth tag.
 */
final class Crypto
{
    private const CIPHER = 'aes-256-gcm';

    private static function key(): string
    {
        $raw = base64_decode(Env::require('CARD_ENCRYPTION_KEY'), true);
        if ($raw === false || strlen($raw) !== 32) {
            throw new \RuntimeException('CARD_ENCRYPTION_KEY must be base64 of 32 bytes');
        }
        return $raw;
    }

    /**
     * @return array{0:string,1:string,2:string} ciphertext, iv, tag (raw bytes)
     */
    public static function encrypt(string $plaintext): array
    {
        $iv = random_bytes(12);
        $tag = '';
        $ct = openssl_encrypt($plaintext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($ct === false) {
            throw new \RuntimeException('Encryption failed');
        }
        return [$ct, $iv, $tag];
    }

    public static function decrypt(string $ciphertext, string $iv, string $tag): string
    {
        $pt = openssl_decrypt($ciphertext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($pt === false) {
            throw new \RuntimeException('Decryption failed');
        }
        return $pt;
    }

    public static function hashLastFour(string $lastFour): string
    {
        $pepper = Env::require('LAST_FOUR_PEPPER');
        return hash('sha256', $pepper . ':' . $lastFour);
    }
}
