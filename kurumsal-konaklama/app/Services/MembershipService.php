<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\DomainException;

/** Erişim başvurusu akışı: Bekliyor → Onaylandı / Reddedildi. */
final class MembershipService
{
    public function __construct(private readonly Database $db)
    {
    }

    public function apply(array $d, string $ip): int
    {
        $email = mb_strtolower((string) $d['email']);
        if ($this->db->value('SELECT 1 FROM users WHERE email = ?', [$email])) {
            // Hesap varlığını ifşa etmemek için genel mesaj
            throw new DomainException('Bu e-posta adresiyle işlem yapılamıyor. Hesabınız varsa “Şifremi unuttum” bağlantısını kullanın.');
        }
        if ($this->db->value("SELECT 1 FROM membership_requests WHERE email = ? AND status = 'pending'", [$email])) {
            throw new DomainException('Bu e-posta adresiyle bekleyen bir başvurunuz bulunuyor. Değerlendirme sonrası bilgilendirileceksiniz.');
        }
        $institutionId = !empty($d['institution_id']) ? (int) $d['institution_id'] : null;
        if ($institutionId !== null && !$this->db->value('SELECT 1 FROM institutions WHERE id = ? AND is_active = 1', [$institutionId])) {
            $institutionId = null;
        }
        $id = $this->db->insert('membership_requests', [
            'first_name' => $d['first_name'], 'last_name' => $d['last_name'], 'email' => $email, 'phone' => $d['phone'],
            'institution_id' => $institutionId, 'institution_text' => $institutionId ? null : (mb_substr(trim((string) ($d['institution_text'] ?? '')), 0, 200) ?: null),
            'department' => $d['department'] ?: null, 'note' => $d['note'] ?: null, 'kvkk_accepted_at' => date('Y-m-d H:i:s'),
            'status' => 'pending', 'ip' => $ip,
        ]);
        NotificationService::notifyStaff('applications.manage', 'Yeni erişim başvurusu', $d['first_name'] . ' ' . $d['last_name'] . ' — ' . $email, '/yonetim/basvurular');
        return $id;
    }

    public static function registrationMode(): string
    {
        $m = SettingsService::get('membership.registration_mode', 'application');
        return in_array($m, ['application', 'open', 'closed'], true) ? $m : 'application';
    }

    /** @return string[] küçük harfli izinli alan adları (boş: tümü serbest) */
    public static function allowedDomains(): array
    {
        $parts = preg_split('/[\s,;]+/', mb_strtolower(SettingsService::get('membership.allowed_domains'))) ?: [];
        return array_values(array_filter(array_map(static fn ($d) => ltrim(trim($d), '@'), $parts)));
    }

    public static function emailAllowed(string $email): bool
    {
        $domains = self::allowedDomains();
        if (!$domains) {
            return true;
        }
        $host = mb_strtolower((string) substr(strrchr($email, '@') ?: '', 1));
        foreach ($domains as $d) {
            if ($host === $d || str_ends_with($host, '.' . $d)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Açık kayıt: yönetimin açtığı modda kullanıcı kendi hesabını oluşturur; hesap Standart Üye rolüyle hemen aktiftir.
     * @return array kullanıcı satırı
     */
    public function register(array $d, string $ip): array
    {
        if (self::registrationMode() !== 'open') {
            throw new DomainException('Üyelik kaydı şu anda kapalıdır.');
        }
        $email = mb_strtolower(trim((string) $d['email']));
        if (!self::emailAllowed($email)) {
            throw new \App\Exceptions\ValidationException(['email' => 'Kayıt yalnız şu alan adlarındaki e-posta adresleriyle yapılabilir: ' . implode(', ', self::allowedDomains())]);
        }
        $institutionId = !empty($d['institution_id']) ? (int) $d['institution_id'] : null;
        if ($institutionId !== null && !$this->db->value('SELECT 1 FROM institutions WHERE id = ? AND is_active = 1', [$institutionId])) {
            $institutionId = null;
        }
        if ($institutionId === null && SettingsService::bool('membership.require_institution')) {
            throw new \App\Exceptions\ValidationException(['institution_id' => 'Listeden kurumunuzu seçin.']);
        }
        $user = $this->db->transaction(function (Database $db) use ($d, $email, $institutionId) {
            if ($db->value('SELECT 1 FROM users WHERE email = ?', [$email])) {
                throw new DomainException('Bu e-posta adresiyle işlem yapılamıyor. Hesabınız varsa giriş yapın veya “Şifremi unuttum” bağlantısını kullanın.');
            }
            $roleId = (int) $db->value("SELECT id FROM roles WHERE slug = 'member'");
            $id = $db->insert('users', [
                'first_name' => $d['first_name'], 'last_name' => $d['last_name'], 'email' => $email, 'phone' => $d['phone'],
                'institution_id' => $institutionId, 'department' => ($d['department'] ?? '') ?: null, 'membership_type' => 'uye',
                'role_id' => $roleId, 'status' => 'active', 'password_hash' => password_hash((string) $d['password'], PASSWORD_DEFAULT),
                'password_changed_at' => date('Y-m-d H:i:s'),
            ]);
            return $db->fetch('SELECT * FROM users WHERE id = ?', [$id]);
        });
        AuditService::log('user.register', 'user', (int) $user['id'], null, ['email' => $email, 'institution_id' => $institutionId, 'ip' => $ip]);
        NotificationService::notifyStaff('users.view', 'Yeni üye kaydı', $user['first_name'] . ' ' . $user['last_name'] . ' — ' . $email, '/yonetim/uyeler');
        return $user;
    }

    /**
     * Başvuruyu onaylar: kullanıcı oluşturulur (aktif), parola oluşturma bağlantısı gönderilir.
     * @return array{user_id:int, link:array}
     */
    public function approve(int $requestId, int $adminId, ?int $institutionId, string $note = ''): array
    {
        $r = $this->db->transaction(function (Database $db) use ($requestId, $adminId, $institutionId, $note) {
            $req = $db->fetch('SELECT * FROM membership_requests WHERE id = ? FOR UPDATE', [$requestId]);
            if (!$req || $req['status'] !== 'pending') {
                throw new DomainException('Başvuru bulunamadı veya zaten sonuçlandırılmış.');
            }
            $inst = $institutionId ?? ($req['institution_id'] !== null ? (int) $req['institution_id'] : null);
            if ($inst === null) {
                throw new DomainException('Onay için kurum seçilmelidir.');
            }
            if ($db->value('SELECT 1 FROM users WHERE email = ?', [$req['email']])) {
                throw new DomainException('Bu e-posta ile kayıtlı bir kullanıcı zaten var.');
            }
            $roleId = (int) $db->value("SELECT id FROM roles WHERE slug = 'member'");
            $userId = $db->insert('users', [
                'first_name' => $req['first_name'], 'last_name' => $req['last_name'], 'email' => $req['email'], 'phone' => $req['phone'],
                'institution_id' => $inst, 'department' => $req['department'], 'membership_type' => 'personel', 'role_id' => $roleId,
                'status' => 'active', 'created_by' => $adminId, 'password_changed_at' => date('Y-m-d H:i:s'),
            ]);
            $db->update('membership_requests', ['status' => 'approved', 'reviewed_by' => $adminId, 'reviewed_at' => date('Y-m-d H:i:s'), 'review_note' => $note ?: null, 'user_id' => $userId, 'institution_id' => $inst], ['id' => $requestId]);
            return ['user_id' => $userId, 'user' => $db->fetch('SELECT * FROM users WHERE id = ?', [$userId])];
        });
        AuditService::log('application.approve', 'membership_request', $requestId, null, ['user_id' => $r['user_id']]);
        $token = AuthService::createToken($r['user_id'], 'invite', 72);
        $link = \App\Core\Url::absolute('/sifre-olustur/' . $token);
        $sent = NotificationService::email($r['user']['email'], 'membership_approved', ['ad' => $r['user']['first_name'], 'baglanti' => $link, 'sure' => '72']);
        return ['user_id' => $r['user_id'], 'link' => ['sent' => $sent, 'link' => $link]];
    }

    public function reject(int $requestId, int $adminId, string $note): void
    {
        $req = $this->db->fetch('SELECT * FROM membership_requests WHERE id = ?', [$requestId]);
        if (!$req || $req['status'] !== 'pending') {
            throw new DomainException('Başvuru bulunamadı veya zaten sonuçlandırılmış.');
        }
        $this->db->update('membership_requests', ['status' => 'rejected', 'reviewed_by' => $adminId, 'reviewed_at' => date('Y-m-d H:i:s'), 'review_note' => $note ?: null], ['id' => $requestId]);
        AuditService::log('application.reject', 'membership_request', $requestId, null, ['note' => $note]);
        NotificationService::email($req['email'], 'membership_rejected', ['ad' => $req['first_name'], 'not' => $note]);
    }
}
