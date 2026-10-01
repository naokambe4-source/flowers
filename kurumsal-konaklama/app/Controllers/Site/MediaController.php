<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Auth;
use App\Core\Response;
use App\Exceptions\HttpException;
use App\Policies\InstitutionPolicy;
use App\Services\ImageService;
use App\Services\SettingsService;

/**
 * Görseller herkese açık klasörde tutulmaz. Bu uçlar oturum ve yetki kontrolüyle sunar.
 * Yalnız logo ve giriş/karşılama görselleri (otel içermeyen site görselleri) misafire açıktır.
 */
final class MediaController extends Controller
{
    private const SITE_KEYS = ['site_logo' => 'site.logo', 'login_image' => 'login.image', 'home_hero_image' => 'home.hero_image', 'support_image' => 'support.image'];

    private function serve(string $kind, string $key, string $size, bool $private = true): Response
    {
        $found = ImageService::path($kind, $key, $size);
        if (!$found) {
            throw new HttpException(404);
        }
        [$path, $mime] = $found;
        $etag = '"' . md5($path . filemtime($path)) . '"';
        if (($this->request->server['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
            return new Response('', 304, ['ETag' => $etag, 'Cache-Control' => ($private ? 'private' : 'public') . ', max-age=86400']);
        }
        return Response::file($path, $mime, [
            'Cache-Control' => ($private ? 'private' : 'public') . ', max-age=86400',
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ]);
    }

    public function site(string $key): Response
    {
        $setting = self::SITE_KEYS[$key] ?? null;
        if ($setting === null) {
            throw new HttpException(404);
        }
        $value = SettingsService::get($setting);
        if ($value === '') {
            throw new HttpException(404);
        }
        return $this->serve('site', $value, 'large', false);
    }

    public function hotelImage(string $id, string $size): Response
    {
        $img = App::db()->fetch('SELECT i.storage_key, h.status FROM hotel_images i JOIN hotels h ON h.id = i.hotel_id WHERE i.id = ?', [(int) $id]);
        if (!$img || ($img['status'] !== 'published' && !Auth::can('hotels.view'))) {
            throw new HttpException(404);
        }
        return $this->serve('hotels', $img['storage_key'], $size);
    }

    public function roomImage(string $id, string $size): Response
    {
        $img = App::db()->fetch('SELECT i.storage_key, h.status FROM room_images i JOIN rooms r ON r.id = i.room_id JOIN hotels h ON h.id = r.hotel_id WHERE i.id = ?', [(int) $id]);
        if (!$img || ($img['status'] !== 'published' && !Auth::can('hotels.view'))) {
            throw new HttpException(404);
        }
        return $this->serve('rooms', $img['storage_key'], $size);
    }

    public function region(string $id): Response
    {
        $key = App::db()->value('SELECT image_path FROM regions WHERE id = ?', [(int) $id]);
        if (!$key) {
            throw new HttpException(404);
        }
        return $this->serve('regions', (string) $key, 'medium');
    }

    public function campaign(string $id): Response
    {
        $key = App::db()->value('SELECT image_path FROM campaigns WHERE id = ?', [(int) $id]);
        if (!$key) {
            throw new HttpException(404);
        }
        return $this->serve('campaigns', (string) $key, 'medium');
    }

    public function institutionLogo(string $id): Response
    {
        if (!InstitutionPolicy::canViewInstitution($this->user(), (int) $id)) {
            throw new HttpException(404);
        }
        $key = App::db()->value('SELECT logo_path FROM institutions WHERE id = ?', [(int) $id]);
        if (!$key) {
            throw new HttpException(404);
        }
        return $this->serve('institutions', (string) $key, 'thumb');
    }
}
