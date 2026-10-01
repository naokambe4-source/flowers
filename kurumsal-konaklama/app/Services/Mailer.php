<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;
use PHPMailer\PHPMailer\PHPMailer;

/** SMTP e-posta gönderimi (PHPMailer). Yönetimden yapılandırılmamışsa e-posta gönderilmez. */
final class Mailer
{
    public static function configured(): bool
    {
        return SettingsService::bool('mail.enabled') && SettingsService::get('mail.host') !== '' && SettingsService::get('mail.from_email') !== '';
    }

    public static function send(string $to, string $subject, string $textBody): void
    {
        if (!self::configured()) {
            throw new DomainException('E-posta ayarları yapılandırılmamış.');
        }
        $m = new PHPMailer(true);
        $m->isSMTP();
        $m->CharSet = PHPMailer::CHARSET_UTF8;
        $m->Host = SettingsService::get('mail.host');
        $m->Port = SettingsService::int('mail.port', 587);
        $enc = SettingsService::get('mail.encryption', 'tls');
        $m->SMTPSecure = $enc === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : ($enc === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : '');
        $m->SMTPAutoTLS = $enc !== 'none';
        $user = SettingsService::get('mail.username');
        if ($user !== '') {
            $m->SMTPAuth = true;
            $m->Username = $user;
            $m->Password = SettingsService::get('mail.password');
        }
        $m->Timeout = 15;
        $m->setFrom(SettingsService::get('mail.from_email'), SettingsService::get('mail.from_name', SettingsService::get('site.name')));
        $m->addAddress($to);
        $m->Subject = $subject;
        $m->isHTML(true);
        $m->Body = self::html($subject, $textBody);
        $m->AltBody = $textBody;
        $m->send();
    }

    private static function html(string $subject, string $text): string
    {
        $site = htmlspecialchars(SettingsService::get('site.name', 'Kurumsal Konaklama'), ENT_QUOTES);
        $body = nl2br(htmlspecialchars($text, ENT_QUOTES));
        $body = preg_replace('#(https?://[^\s<]+)#', '<a href="$1" style="color:#0b7f86">$1</a>', $body) ?? $body;
        return '<!doctype html><html lang="tr"><body style="margin:0;background:#f3f6fa;font-family:Arial,Helvetica,sans-serif;color:#132a4a">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fff;border-radius:12px;overflow:hidden">'
            . '<tr><td style="background:#0f2240;color:#fff;padding:20px 28px;font-size:18px;font-weight:bold">' . $site . '</td></tr>'
            . '<tr><td style="padding:28px;font-size:16px;line-height:1.6"><h1 style="font-size:20px;margin:0 0 16px">' . htmlspecialchars($subject, ENT_QUOTES) . '</h1>' . $body . '</td></tr>'
            . '<tr><td style="padding:16px 28px;font-size:12px;color:#5b6b82;border-top:1px solid #e3e8ef">Bu e-posta ' . $site . ' tarafından otomatik gönderilmiştir.</td></tr>'
            . '</table></td></tr></table></body></html>';
    }
}
