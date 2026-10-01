<?php
declare(strict_types=1);

namespace App\Policies;

use App\Core\Auth;

/** Kurum yöneticisi yalnız kendi kurumunun verilerine erişebilir. */
final class InstitutionPolicy
{
    public static function sameInstitution(array $user, mixed $institutionId): bool
    {
        return $institutionId !== null && $user['institution_id'] !== null && (int) $user['institution_id'] === (int) $institutionId;
    }

    /** Kurum verisini görüntüleme: kurumlar yetkisi olan personel veya aynı kurumun yöneticisi. */
    public static function canViewInstitution(array $user, int $institutionId): bool
    {
        if (Auth::can('institutions.view')) {
            return true;
        }
        return Auth::can('institution.own') && self::sameInstitution($user, $institutionId);
    }
}
