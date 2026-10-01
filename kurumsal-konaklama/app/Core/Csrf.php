<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $t = Session::get('_csrf');
        if (!is_string($t) || strlen($t) !== 64) {
            $t = bin2hex(random_bytes(32));
            Session::put('_csrf', $t);
        }
        return $t;
    }

    public static function validate(Request $request): bool
    {
        $sent = $request->input('_token') ?? $request->header('X-CSRF-Token');
        $t = Session::get('_csrf');
        return is_string($sent) && is_string($t) && hash_equals($t, $sent);
    }
}
