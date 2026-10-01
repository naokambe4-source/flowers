<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Auth;
use App\Core\Crypto;
use App\Exceptions\DomainException;

/** Giriş, parola belirteçleri ve hesap durumu kontrolleri. */
final class AuthService
{
    /** Hesap durumuna göre kullanıcıya gösterilecek mesaj (null = giriş yapabilir). */
    public static function statusMessage(array $user): ?string
    {
        return match ($user['status']) {
            'active' => null,
            'pending' => 'Hesabınız henüz onaylanmadı. Onaylandığında e-posta ile bilgilendirileceksiniz.',
            'passive' => 'Hesabınız pasif durumdadır. Lütfen kurum yöneticiniz veya destek ile iletişime geçin.',
            'suspended' => 'Hesabınız askıya alınmıştır. Lütfen destek ile iletişime geçin.',
            'rejected' => 'Erişim başvurunuz onaylanmamıştır.',
            default => 'Hesabınız ile giriş yapılamıyor.',
        };
    }

    /**
     * @return array kullanıcı satırı
     * @throws DomainException
     */
    public static function attempt(string $email, string $password, string $ip): array
    {
        $email = mb_strtolower(trim($email));
        $max = SettingsService::int('security.login_max_attempts', 5);
        $decay = SettingsService::int('security.login_decay_minutes', 15) * 60;
        $keyUser = 'login:' . $email;
        $keyIp = 'login-ip:' . $ip;
        if (RateLimiter::tooManyAttempts($keyUser, $max) || RateLimiter::tooManyAttempts($keyIp, $max * 4)) {
            $wait = (int) ceil(max(RateLimiter::availableIn($keyUser), RateLimiter::availableIn($keyIp)) / 60);
            throw new DomainException("Çok fazla hatalı deneme yapıldı. Lütfen $wait dakika sonra tekrar deneyin.");
        }
        $user = App::db()->fetch('SELECT u.*, i.is_active AS institution_active, r.slug AS role_slug FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN institutions i ON i.id = u.institution_id WHERE u.email = ?', [$email]);
        $hash = $user['password_hash'] ?? null;
        // Zamanlama saldırısına karşı kullanıcı yoksa da hash doğrulaması yapılır
        if ($hash) {
            $valid = password_verify($password, $hash);
        } else {
            password_verify($password, password_hash('kk-dummy', PASSWORD_DEFAULT));
            $valid = false;
        }
        if (!$user || !$hash || !$valid) {
            RateLimiter::hit($keyUser, $decay);
            RateLimiter::hit($keyIp, $decay);
            throw new DomainException('E-posta adresi veya parola hatalı.');
        }
        if ($msg = self::statusMessage($user)) {
            throw new DomainException($msg);
        }
        if ($user['institution_id'] !== null && (int) $user['institution_active'] !== 1 && $user['role_slug'] !== 'super_admin') {
            throw new DomainException('Kurumunuzun platform erişimi şu anda pasif durumdadır.');
        }
        RateLimiter::clear($keyUser);
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            App::db()->update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], ['id' => $user['id']]);
        }
        App::db()->update('users', ['last_login_at' => date('Y-m-d H:i:s'), 'last_login_ip' => $ip], ['id' => $user['id']]);
        Auth::login($user);
        return $user;
    }

    /** Parola oluşturma/sıfırlama bağlantısı üretir. Düz belirteç yalnız döndürülür, veritabanında hash saklanır. */
    public static function createToken(int $userId, string $purpose, int $hours): string
    {
        $token = Crypto::token(32);
        App::db()->query("UPDATE password_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL AND purpose = ?", [$userId, $purpose]);
        App::db()->insert('password_tokens', [
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'purpose' => $purpose,
            'expires_at' => date('Y-m-d H:i:s', time() + $hours * 3600),
        ]);
        return $token;
    }

    public static function findToken(string $token): ?array
    {
        return App::db()->fetch(
            'SELECT t.*, u.email, u.first_name, u.status FROM password_tokens t JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW()',
            [hash('sha256', $token)],
        );
    }

    public static function setPassword(int $userId, string $password): void
    {
        App::db()->update('users', [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'password_changed_at' => date('Y-m-d H:i:s'),
        ], ['id' => $userId]);
    }

    /** Davet veya sıfırlama bağlantısı gönderir; e-posta yapılandırılmamışsa bağlantıyı döndürür (yönetici iletebilir). */
    public static function sendPasswordLink(array $user, string $purpose): array
    {
        $hours = $purpose === 'invite' ? 72 : 2;
        $token = self::createToken((int) $user['id'], $purpose, $hours);
        $link = \App\Core\Url::absolute('/sifre-olustur/' . $token);
        $sent = NotificationService::email($user['email'], $purpose === 'invite' ? 'user_invite' : 'password_reset', [
            'ad' => $user['first_name'], 'baglanti' => $link, 'sure' => (string) $hours,
        ]);
        return ['sent' => $sent, 'link' => $link];
    }
}
