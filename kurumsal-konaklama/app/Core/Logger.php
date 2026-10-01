<?php
declare(strict_types=1);

namespace App\Core;

/** Dosya tabanlı log. Gizli anahtar içerebilecek alanlar maskelenir. */
final class Logger
{
    private const SECRET_KEYS = ['password', 'pass', 'secret', 'api_key', 'apikey', 'x-api-key', 'token', 'authorization', 'signature', 'key'];

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function sanitize(array $context): array
    {
        foreach ($context as $k => $v) {
            if (is_array($v)) {
                $context[$k] = self::sanitize($v);
            } elseif (is_string($k) && in_array(strtolower($k), self::SECRET_KEYS, true)) {
                $context[$k] = is_string($v) ? Crypto::mask($v) : '***';
            }
        }
        return $context;
    }

    private static function write(string $level, string $message, array $context): void
    {
        $dir = APP_ROOT . '/storage/logs';
        if (!is_dir($dir) || !is_writable($dir)) {
            error_log("[$level] $message");
            return;
        }
        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            str_replace(["\r", "\n"], ' ', $message),
            $context ? json_encode(self::sanitize($context), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) : '',
        );
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
