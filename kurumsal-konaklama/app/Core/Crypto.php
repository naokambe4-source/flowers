<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Sağlayıcı anahtarları ve SMTP parolası gibi gizli değerlerin şifrelenmesi.
 * sodium uzantısı varsa XSalsa20-Poly1305 ("v1:"), yoksa OpenSSL AES-256-GCM ("v2:") kullanılır.
 * Her iki biçim de kimlik doğrulamalıdır (değiştirilmiş veri çözülmez); okuma sırasında biçim önekten anlaşılır.
 */
final class Crypto
{
    private const KEY_BYTES = 32;
    private const SODIUM_NONCE_BYTES = 24;
    private const GCM_IV_BYTES = 12;
    private const GCM_TAG_BYTES = 16;

    /** Testlerde OpenSSL yolunu zorlamak için. */
    public static bool $forceOpenssl = false;

    private static function key(): string
    {
        $raw = (string) Config::get('app.key', '');
        $key = base64_decode(str_starts_with($raw, 'base64:') ? substr($raw, 7) : $raw, true);
        if ($key === false || strlen($key) !== self::KEY_BYTES) {
            throw new \RuntimeException('Uygulama anahtarı (app.key) eksik veya geçersiz.');
        }
        return $key;
    }

    public static function sodiumAvailable(): bool
    {
        return !self::$forceOpenssl && function_exists('sodium_crypto_secretbox');
    }

    public static function opensslAvailable(): bool
    {
        return function_exists('openssl_encrypt') && in_array('aes-256-gcm', openssl_get_cipher_methods(), true);
    }

    /** Kurulum kontrolü için: en az bir güvenli şifreleme yolu var mı? */
    public static function available(): bool
    {
        return function_exists('sodium_crypto_secretbox') || self::opensslAvailable();
    }

    public static function generateKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(self::KEY_BYTES));
    }

    public static function encrypt(string $plain): string
    {
        $key = self::key();
        if (self::sodiumAvailable()) {
            $nonce = random_bytes(self::SODIUM_NONCE_BYTES);
            return 'v1:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, $key));
        }
        if (!self::opensslAvailable()) {
            throw new \RuntimeException('Şifreleme için sodium veya OpenSSL (aes-256-gcm) uzantısı gereklidir.');
        }
        $iv = random_bytes(self::GCM_IV_BYTES);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, 'kk-v2', self::GCM_TAG_BYTES);
        if ($cipher === false) {
            throw new \RuntimeException('Şifreleme başarısız.');
        }
        return 'v2:' . base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt(string $cipher): ?string
    {
        if (str_starts_with($cipher, 'v1:')) {
            $bin = base64_decode(substr($cipher, 3), true);
            if ($bin === false || strlen($bin) <= self::SODIUM_NONCE_BYTES || !function_exists('sodium_crypto_secretbox_open')) {
                return null;
            }
            $nonce = substr($bin, 0, self::SODIUM_NONCE_BYTES);
            $plain = sodium_crypto_secretbox_open(substr($bin, self::SODIUM_NONCE_BYTES), $nonce, self::key());
            return $plain === false ? null : $plain;
        }
        if (str_starts_with($cipher, 'v2:')) {
            $bin = base64_decode(substr($cipher, 3), true);
            $head = self::GCM_IV_BYTES + self::GCM_TAG_BYTES;
            if ($bin === false || strlen($bin) < $head || !self::opensslAvailable()) {
                return null;
            }
            $iv = substr($bin, 0, self::GCM_IV_BYTES);
            $tag = substr($bin, self::GCM_IV_BYTES, self::GCM_TAG_BYTES);
            $plain = openssl_decrypt(substr($bin, $head), 'aes-256-gcm', self::key(), OPENSSL_RAW_DATA, $iv, $tag, 'kk-v2');
            return $plain === false ? null : $plain;
        }
        return null;
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
