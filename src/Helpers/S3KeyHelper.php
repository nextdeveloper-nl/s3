<?php

namespace NextDeveloper\S3\Helpers;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Generates and encrypts S3 IAM key pairs.
 *
 * Secret keys are stored encrypted at rest using AES-256-GCM.
 * Encryption key must be a 32-byte value set in config('s3.encryption_key').
 */
class S3KeyHelper
{
    private const ACCESS_KEY_PREFIX = 'AKIA';
    private const ACCESS_KEY_LENGTH = 16;
    private const SECRET_KEY_LENGTH = 40;
    private const CIPHER = 'aes-256-gcm';
    private const TAG_LENGTH = 16;
    private const IV_LENGTH = 12;

    /**
     * Generate a new access key / secret key pair.
     *
     * Returns plaintext values — the caller is responsible for encrypting
     * the secret before persisting it.
     *
     * @return array{access_key: string, secret_key: string}
     */
    public static function generate(): array
    {
        $suffix = strtoupper(Str::random(self::ACCESS_KEY_LENGTH));
        $accessKey = self::ACCESS_KEY_PREFIX . $suffix;

        // 40-char URL-safe base64 secret (30 raw bytes → 40 chars base64url)
        $rawSecret = random_bytes(30);
        $secretKey = rtrim(strtr(base64_encode($rawSecret), '+/', '-_'), '=');

        return [
            'access_key' => $accessKey,
            'secret_key' => $secretKey,
        ];
    }

    /**
     * Encrypt a secret key for storage.
     *
     * Encoded as base64(iv[12] . tag[16] . ciphertext).
     */
    public static function encrypt(string $secret): string
    {
        $key = self::resolveKey();
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $secret,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new RuntimeException('S3KeyHelper: encryption failed');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt a stored secret key.
     */
    public static function decrypt(string $encrypted): string
    {
        $key = self::resolveKey();
        $raw = base64_decode($encrypted, true);

        if ($raw === false || strlen($raw) < self::IV_LENGTH + self::TAG_LENGTH + 1) {
            throw new RuntimeException('S3KeyHelper: invalid encrypted payload');
        }

        $iv = substr($raw, 0, self::IV_LENGTH);
        $tag = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('S3KeyHelper: decryption failed (bad key or tampered data)');
        }

        return $plaintext;
    }

    private static function resolveKey(): string
    {
        $key = config('s3.encryption_key');

        if (empty($key)) {
            throw new RuntimeException('S3KeyHelper: s3.encryption_key is not configured');
        }

        // Accept hex-encoded 64-char keys or raw 32-byte keys
        if (strlen($key) === 64 && ctype_xdigit($key)) {
            return hex2bin($key);
        }

        if (strlen($key) === 32) {
            return $key;
        }

        throw new RuntimeException('S3KeyHelper: encryption key must be a 32-byte raw string or 64-char hex string');
    }
}
