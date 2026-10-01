<?php
declare(strict_types=1);

namespace App\Services\Pricing;

use App\Core\Database;
use App\Services\SettingsService;

/**
 * Üyenin fiyatlamaya etki eden bilgileri.
 * Üye indirimi önceliği: Kuruma özel indirim → Fiyat grubu indirimi → Genel varsayılan (yönetim ayarı).
 */
final class PricingProfile
{
    public function __construct(
        public readonly ?int $userId,
        public readonly ?int $institutionId,
        public readonly ?int $priceGroupId,
        public readonly int $memberDiscountBp,
        public readonly string $discountSource,
    ) {
    }

    public static function forUser(Database $db, ?array $user): self
    {
        $default = SettingsService::int('pricing.default_discount_bp', 1000);
        if (!$user || empty($user['institution_id'])) {
            return new self($user ? (int) $user['id'] : null, null, null, $default, 'Genel üye indirimi');
        }
        $row = $db->fetch(
            'SELECT i.id, i.discount_bp, i.price_group_id, pg.discount_bp AS group_bp, pg.is_active AS group_active, pg.name AS group_name
             FROM institutions i LEFT JOIN price_groups pg ON pg.id = i.price_group_id WHERE i.id = ?',
            [$user['institution_id']],
        );
        if (!$row) {
            return new self((int) $user['id'], null, null, $default, 'Genel üye indirimi');
        }
        if ($row['discount_bp'] !== null) {
            return new self((int) $user['id'], (int) $row['id'], $row['price_group_id'] !== null ? (int) $row['price_group_id'] : null, (int) $row['discount_bp'], 'Kurum indirimi');
        }
        if ($row['group_bp'] !== null && (int) $row['group_active'] === 1) {
            return new self((int) $user['id'], (int) $row['id'], (int) $row['price_group_id'], (int) $row['group_bp'], 'Fiyat grubu indirimi (' . $row['group_name'] . ')');
        }
        return new self((int) $user['id'], (int) $row['id'], $row['price_group_id'] !== null ? (int) $row['price_group_id'] : null, $default, 'Genel üye indirimi');
    }
}
