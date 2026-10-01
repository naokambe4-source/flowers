<?php
declare(strict_types=1);

namespace App\Core;

/** Basit PHP şablon motoru. Şablonlarda tüm çıktı e() ile kaçırılır. */
final class View
{
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function shared(string $key, mixed $default = null): mixed
    {
        return self::$shared[$key] ?? $default;
    }

    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = self::partial($template, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial('layouts/' . $layout, array_merge($data, ['content' => $content]));
    }

    public static function partial(string $template, array $data = []): string
    {
        $file = APP_ROOT . '/app/Views/' . $template . '.php';
        if (!preg_match('#^[a-z0-9_/\-]+$#', $template) || !is_file($file)) {
            throw new \RuntimeException('Şablon bulunamadı: ' . $template);
        }
        $vars = array_merge(self::$shared, $data);
        return (static function () use ($file, $vars): string {
            extract($vars, EXTR_SKIP);
            ob_start();
            try {
                require $file;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }
            return (string) ob_get_clean();
        })();
    }
}
