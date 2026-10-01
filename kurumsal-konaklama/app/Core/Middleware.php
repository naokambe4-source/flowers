<?php
declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;

/**
 * Rota ara katmanları:
 *  auth      — giriş zorunlu (misafir 401 → /giris)
 *  guest     — giriş yapmış kullanıcıyı panele yönlendirir
 *  can:x     — izin zorunlu (403)
 *  staff     — yönetim paneli erişimi (admin.access)
 *  nocsrf    — CSRF kontrolünü kapatır (yalnız token korumalı cron gibi uçlar)
 */
final class Middleware
{
    public static function handle(string $name, Request $request): ?Response
    {
        [$mw, $arg] = array_pad(explode(':', $name, 2), 2, null);
        switch ($mw) {
            case 'auth':
                if (!Auth::check()) {
                    throw new HttpException(401);
                }
                return null;
            case 'guest':
                return Auth::check() ? Response::to('/panel') : null;
            case 'can':
                if (!Auth::check()) {
                    throw new HttpException(401);
                }
                foreach (explode('|', (string) $arg) as $perm) {
                    if (Auth::can($perm)) {
                        return null;
                    }
                }
                throw new HttpException(403);
            case 'staff':
                if (!Auth::check()) {
                    throw new HttpException(401);
                }
                if (!Auth::can('admin.access')) {
                    throw new HttpException(403);
                }
                return null;
            case 'nocsrf':
                return null;
            default:
                throw new \LogicException('Bilinmeyen middleware: ' . $name);
        }
    }
}
