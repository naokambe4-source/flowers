<?php
declare(strict_types=1);

namespace App\Providers\Contracts;

/** Sağlayıcı yetenekleri. Her adaptör desteklediklerini capabilities() ile açıkça bildirir. */
final class Capability
{
    public const DESTINATIONS = 'destinations';
    public const SEARCH = 'search';
    public const CONTENT = 'content';
    public const AVAILABILITY = 'availability';
    public const RATES = 'rates';
    public const CHECK_RATE = 'check_rate';
    public const BOOKING = 'booking';
    public const BOOKING_LOOKUP = 'booking_lookup';
    public const CANCEL = 'cancel';
    public const HEALTH = 'health';

    public const LABELS = [
        self::DESTINATIONS => 'Destinasyon arama',
        self::SEARCH => 'Otel arama',
        self::CONTENT => 'Otel içeriği',
        self::AVAILABILITY => 'Müsaitlik',
        self::RATES => 'Oda fiyatları',
        self::CHECK_RATE => 'Fiyat yeniden doğrulama',
        self::BOOKING => 'Rezervasyon oluşturma',
        self::BOOKING_LOOKUP => 'Rezervasyon sorgulama',
        self::CANCEL => 'İptal',
        self::HEALTH => 'Bağlantı testi',
    ];
}
