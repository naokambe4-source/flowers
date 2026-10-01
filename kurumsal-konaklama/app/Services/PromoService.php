<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\DomainException;

final class PromoService
{
    public function __construct(private readonly Database $db)
    {
    }

    /** Kodu doğrular; geçersizse kullanıcıya gösterilebilir hata fırlatır. */
    public function resolve(string $code, array $user, int $hotelId, int $nights): array
    {
        $code = mb_strtoupper(trim($code));
        $p = $this->db->fetch('SELECT * FROM promo_codes WHERE code = ? AND is_active = 1', [$code]);
        $now = date('Y-m-d H:i:s');
        if (!$p || ($p['valid_from'] !== null && $p['valid_from'] > $now) || ($p['valid_to'] !== null && $p['valid_to'] < $now)) {
            throw new DomainException('Promosyon kodu geçerli değil.');
        }
        if ($p['institution_id'] !== null && (int) $p['institution_id'] !== (int) $user['institution_id']) {
            throw new DomainException('Bu promosyon kodu kurumunuz için geçerli değil.');
        }
        if ($p['hotel_id'] !== null && (int) $p['hotel_id'] !== $hotelId) {
            throw new DomainException('Bu promosyon kodu seçilen otelde geçerli değil.');
        }
        if ($p['min_nights'] !== null && $nights < (int) $p['min_nights']) {
            throw new DomainException('Bu promosyon kodu en az ' . (int) $p['min_nights'] . ' gecelik konaklamada geçerlidir.');
        }
        if ($p['max_uses'] !== null && (int) $p['used_count'] >= (int) $p['max_uses']) {
            throw new DomainException('Promosyon kodunun kullanım limiti dolmuştur.');
        }
        if ($p['per_user_limit'] !== null) {
            $used = (int) $this->db->value("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND promo_code_id = ? AND status NOT IN ('draft','cancelled')", [$user['id'], $p['id']]);
            if ($used >= (int) $p['per_user_limit']) {
                throw new DomainException('Bu promosyon kodunu kullanım hakkınız dolmuştur.');
            }
        }
        return $p;
    }

    /** Kullanım sayacını atomik artırır (transaction içinde). */
    public function consume(int $promoId): void
    {
        $n = $this->db->query('UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ? AND is_active = 1 AND (max_uses IS NULL OR used_count < max_uses)', [$promoId])->rowCount();
        if ($n !== 1) {
            throw new DomainException('Promosyon kodunun kullanım limiti dolmuştur.');
        }
    }

    public function release(int $promoId): void
    {
        $this->db->query('UPDATE promo_codes SET used_count = GREATEST(0, CAST(used_count AS SIGNED) - 1) WHERE id = ?', [$promoId]);
    }
}
