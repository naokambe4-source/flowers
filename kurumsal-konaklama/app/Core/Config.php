<?php
declare(strict_types=1);

namespace App\Core;

final class Config
{
    private static array $items = [];
    private static bool $loaded = false;

    public static function path(): string
    {
        $env = getenv('KK_CONFIG');
        return $env !== false && $env !== '' ? $env : APP_ROOT . '/config/config.php';
    }

    public static function load(): bool
    {
        $file = self::path();
        if (is_file($file)) {
            $data = require $file;
            self::$items = is_array($data) ? $data : [];
            self::$loaded = true;
        }
        return self::$loaded;
    }

    public static function loaded(): bool
    {
        return self::$loaded;
    }

    public static function set(array $items): void
    {
        self::$items = $items;
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    /** Ayar dosyasını güvenli biçimde yazar (kurulum sihirbazı). */
    public static function write(array $items, ?string $file = null): void
    {
        $file ??= self::path();
        $export = var_export($items, true);
        $php = "<?php\n// Kurulum sihirbazı tarafından oluşturuldu. Bu dosyayı paylaşmayın.\nreturn {$export};\n";
        $tmp = $file . '.tmp';
        if (file_put_contents($tmp, $php, LOCK_EX) === false) {
            throw new \RuntimeException('Ayar dosyası yazılamadı: ' . $file);
        }
        @chmod($tmp, 0640);
        rename($tmp, $file);
        self::$items = $items;
        self::$loaded = true;
    }
}
