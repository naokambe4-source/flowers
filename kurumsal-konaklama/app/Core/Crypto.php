<?php
declare(strict_types=1);

namespace App\Core;

/** Sağlayıcı anahtarları ve SMTP parolası gibi gizli değerlerin şifrelenmesi (libsodium, XSalsa20-Poly1305). */
final class Crypto
{
    private static function key(): string
    {
        $raw = (string) Config::get('app.key', '');
        $key = base64_decode(str_starts_with($raw, 'base64:') ? substr($raw, 7) : $raw, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \RuntimeException('Uygulama anahtarı (app.key) eksik veya geçersiz.');
        }
        return $key;
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }

    public static function encrypt(string $plain): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return 'v1:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, self::key()));
    }

    public static function decrypt(string $cipher): ?string
    {
        if (!str_starts_with($cipher, 'v1:')) {
            return null;
        }
        $bin = base64_decode(substr($cipher, 3), true);
        if ($bin === false || strlen($bin) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }
        $nonce = substr($bin, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open(substr($bin, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, self::key());
        return $plain === false ? null : $plain;
    }

    /** Tahmin edilemez belirteç (URL güvenli). */
    public static function token(int $bytes = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    public static function hash(string $value): string
    {
        return hash_hmac('sha256', $value, self::key());
    }

    /** Loglarda gizli değerleri maskeler. */
    public static function mask(string $value): string
    {
        $len = mb_strlen($value);
        if ($len <= 4) {
            return str_repeat('•', $len);
        }
        return str_repeat('•', min(8, $len - 4)) . mb_substr($value, -4);
    }
}
