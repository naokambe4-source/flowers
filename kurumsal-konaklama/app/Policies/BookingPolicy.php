<?php
declare(strict_types=1);

namespace App\Policies;

use App\Core\Auth;

/** Rezervasyon/talep erişim kuralları: sahibi, yetkili personel veya aynı kurumun kurum yöneticisi. */
final class BookingPolicy
{
    public static function canView(array $user, array $record): bool
    {
        if ((int) $record['user_id'] === (int) $user['id']) {
            return true;
        }
        if (Auth::can('bookings.view') || Auth::can('requests.manage')) {
            return true;
        }
        return InstitutionPolicy::sameInstitution($user, $record['institution_id'] ?? null) && Auth::can('institution.own');
    }

    /** Değişiklik (iptal, kabul) yalnız sahibine aittir; personel yönetim panelinden işlem yapar. */
    public static function canModify(array $user, array $record): bool
    {
        return (int) $record['user_id'] === (int) $user['id'];
    }
}
