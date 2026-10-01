<?php
declare(strict_types=1);

namespace App\Providers;

use App\Core\App;
use App\Exceptions\ProviderException;
use App\Providers\Adapters\AbstractProvider;
use App\Providers\Contracts\HotelProviderInterface;

/** Sağlayıcı kayıtlarından adaptör örnekleri üretir. Yeni sağlayıcı: adaptör sınıfı + providers satırı. */
final class ProviderRegistry
{
    private static array $instances = [];

    public static function reset(): void
    {
        self::$instances = [];
    }

    public static function row(int $id): ?array
    {
        return App::db()->fetch('SELECT * FROM providers WHERE id = ?', [$id]);
    }

    public static function make(array $row): AbstractProvider
    {
        $id = (int) $row['id'];
        if (isset(self::$instances[$id])) {
            return self::$instances[$id];
        }
        $class = (string) $row['adapter'];
        if (!class_exists($class) || !is_subclass_of($class, HotelProviderInterface::class)) {
            throw new ProviderException('Sağlayıcı adaptörü bulunamadı: ' . $class);
        }
        return self::$instances[$id] = new $class($row);
    }

    public static function find(int $id): AbstractProvider
    {
        $row = self::row($id);
        if (!$row) {
            throw new ProviderException('Sağlayıcı bulunamadı.');
        }
        return self::make($row);
    }

    /** @return AbstractProvider[] etkin sağlayıcılar */
    public static function enabled(): array
    {
        return array_map([self::class, 'make'], App::db()->fetchAll('SELECT * FROM providers WHERE is_enabled = 1 ORDER BY id'));
    }

    /** Yönetim ekranı için sınıf listesi (yeni sağlayıcı kaydı oluştururken). */
    public static function adapters(): array
    {
        $out = [];
        foreach (glob(APP_ROOT . '/app/Providers/Adapters/*Provider.php') ?: [] as $f) {
            $name = basename($f, '.php');
            if ($name !== 'AbstractProvider') {
                $out[] = 'App\\Providers\\Adapters\\' . $name;
            }
        }
        return $out;
    }
}
