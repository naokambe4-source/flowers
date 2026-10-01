<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Url;

/**
 * Sistem içi bildirim + (yapılandırılmışsa) e-posta kuyruğu.
 * Şablonlar notification_templates tablosundan yönetilir; {ad}, {kod}, {otel}, {giris}, {cikis}, {tutar}, {baglanti}, {not} değişkenleri.
 */
final class NotificationService
{
    public static function render(string $templateKey, array $vars): ?array
    {
        $tpl = App::db()->fetch('SELECT * FROM notification_templates WHERE `key` = ?', [$templateKey]);
        if (!$tpl) {
            return null;
        }
        $vars += ['site' => SettingsService::get('site.name', 'Kurumsal Konaklama')];
        $repl = [];
        foreach ($vars as $k => $v) {
            $repl['{' . $k . '}'] = (string) $v;
        }
        return [
            'subject' => strtr((string) $tpl['subject'], $repl),
            'body' => strtr((string) $tpl['body'], $repl),
            'send_email' => (int) $tpl['send_email'] === 1,
        ];
    }

    /** Kullanıcıya sistem içi bildirim ve e-posta. */
    public static function notifyUser(int $userId, string $type, string $templateKey, array $vars, ?string $link = null): void
    {
        $user = App::db()->fetch('SELECT id, first_name, email FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return;
        }
        $vars += ['ad' => $user['first_name'], 'baglanti' => $link ? Url::absolute($link) : Url::absolute('/panel')];
        $msg = self::render($templateKey, $vars);
        if ($msg === null) {
            return;
        }
        App::db()->insert('notifications', [
            'user_id' => $userId,
            'type' => $type,
            'title' => mb_substr($msg['subject'], 0, 200),
            'body' => mb_substr(trim(preg_replace('/https?:\/\/\S+/', '', $msg['body']) ?? ''), 0, 1000),
            'link' => $link,
        ]);
        if ($msg['send_email'] && Mailer::configured()) {
            QueueService::push('send_email', ['to' => $user['email'], 'subject' => $msg['subject'], 'body' => $msg['body']]);
        }
    }

    /** Doğrudan e-posta (hesabı olmayan alıcılar veya parola bağlantıları). E-posta yapılandırılmamışsa false döner. */
    public static function email(string $to, string $templateKey, array $vars): bool
    {
        $msg = self::render($templateKey, $vars);
        if ($msg === null || !Mailer::configured()) {
            return false;
        }
        QueueService::push('send_email', ['to' => $to, 'subject' => $msg['subject'], 'body' => $msg['body']]);
        return true;
    }

    /** Yetkili personele bildirim (permission bazlı). */
    public static function notifyStaff(string $permission, string $title, string $note, string $link): void
    {
        $users = App::db()->fetchAll(
            "SELECT DISTINCT u.id FROM users u JOIN roles r ON r.id = u.role_id
             LEFT JOIN role_permission rp ON rp.role_id = r.id LEFT JOIN permissions p ON p.id = rp.permission_id
             WHERE u.status = 'active' AND (r.slug = 'super_admin' OR p.slug = ?) LIMIT 50",
            [$permission],
        );
        foreach ($users as $u) {
            App::db()->insert('notifications', ['user_id' => $u['id'], 'type' => 'staff', 'title' => mb_substr($title, 0, 200), 'body' => mb_substr($note, 0, 1000), 'link' => $link]);
        }
        $admin = SettingsService::get('mail.admin_notify_email');
        if ($admin !== '' && filter_var($admin, FILTER_VALIDATE_EMAIL)) {
            self::email($admin, 'staff_new_item', ['baslik' => $title, 'not' => $note, 'baglanti' => Url::absolute($link)]);
        }
    }

    public static function unreadCount(int $userId): int
    {
        return (int) App::db()->value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }
}
