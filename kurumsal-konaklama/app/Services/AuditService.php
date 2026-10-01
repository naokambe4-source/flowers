<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Auth;
use App\Core\Logger;

/** Yönetim işlemlerinin denetim kaydı: kim, ne, ne zaman, IP, eski/yeni değer. Gizli alanlar maskelenir. */
final class AuditService
{
    private const HIDDEN = ['password', 'password_hash', 'value_encrypted', 'api_key', 'secret', 'token_hash', 'mail.password', 'verify_token'];

    public static function log(string $action, ?string $entityType = null, int|string|null $entityId = null, ?array $old = null, ?array $new = null): void
    {
        if ($old !== null && $new !== null) {
            [$old, $new] = self::diff($old, $new);
        }
        $req = App::request();
        App::db()->insert('audit_logs', [
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId === null ? null : (string) $entityId,
            'old_values' => $old === null ? null : self::encode($old),
            'new_values' => $new === null ? null : self::encode($new),
            'ip' => $req?->ip(),
            'user_agent' => $req?->userAgent(),
        ]);
    }

    private static function diff(array $old, array $new): array
    {
        $o = [];
        $n = [];
        foreach ($new as $k => $v) {
            $before = $old[$k] ?? null;
            if ((string) json_encode($before) !== (string) json_encode($v) && (string) $before !== (string) (is_scalar($v) ? $v : json_encode($v))) {
                $o[$k] = $before;
                $n[$k] = $v;
            }
        }
        return [$o, $n];
    }

    private static function encode(array $values): string
    {
        foreach ($values as $k => $v) {
            if (in_array((string) $k, self::HIDDEN, true)) {
                $values[$k] = '[gizli]';
            }
        }
        return (string) json_encode(Logger::sanitize($values), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
