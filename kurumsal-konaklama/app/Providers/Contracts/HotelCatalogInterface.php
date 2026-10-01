<?php
declare(strict_types=1);

namespace App\Providers\Contracts;

/**
 * Gerçek otel kataloğu sağlayan kaynaklar (içe aktarma için). Fiyat içermez.
 * Dönen kayıtlar normalleştirilmiştir; içe aktarma servisi bunları yerel otellere dönüştürür.
 */
interface HotelCatalogInterface
{
    /**
     * Bir merkez noktası çevresindeki oteller.
     * @return array<int, array{external_id:string, name:string, stars:?int, latitude:?float, longitude:?float, address:?string,
     *   city:?string, description:?string, photo_urls:string[], website:?string, phone:?string, facilities:string[]}>
     */
    public function listHotels(float $latitude, float $longitude, int $radiusMeters, int $limit): array;

    /** İçe aktarılan verinin kaynak gösterimi (lisans / atıf metni). */
    public function attribution(): string;
}
