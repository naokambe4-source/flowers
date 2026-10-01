<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Database;
use App\Core\Logger;
use App\DTO\ProviderRate;
use App\DTO\StayCriteria;
use App\Exceptions\ProviderException;
use App\Providers\Adapters\StayApiProvider;
use App\Providers\Contracts\Capability;
use App\Providers\ProviderCache;
use App\Providers\ProviderRegistry;

/**
 * Harici sağlayıcı fiyatlarını eşlenmiş yerel otellere getirir.
 * Geri dönüş zinciri: Sağlayıcı → geçerli önbellek → yerel anlaşmalı veri → manuel teklif talebi.
 * Süresi geçmiş fiyat asla güncel/kesin fiyat olarak kullanılmaz (reference_prices.valid_until).
 */
final class ProviderRateService
{
    /** @var string[] kullanıcıya gösterilecek bilgilendirmeler */
    public array $notices = [];

    public function __construct(private readonly Database $db)
    {
    }

    public function refresh(array $hotelIds, StayCriteria $c): void
    {
        if (!$hotelIds || !SettingsService::bool('providers.live_search')) {
            return;
        }
        [$in, $p] = Database::in($hotelIds, 'h');
        $maps = $this->db->fetchAll(
            "SELECT m.hotel_id, m.external_hotel_id, m.provider_id, h.region_id, r.parent_id AS region_parent
             FROM provider_hotel_map m
             JOIN providers pr ON pr.id = m.provider_id AND pr.is_enabled = 1 AND pr.display_authorized = 1 AND pr.code <> 'manual'
             JOIN hotels h ON h.id = m.hotel_id LEFT JOIN regions r ON r.id = h.region_id
             WHERE m.hotel_id IN $in",
            $p,
        );
        $byProvider = [];
        foreach ($maps as $m) {
            $byProvider[(int) $m['provider_id']][] = $m;
        }
        $ttl = SettingsService::int('cache.rates_ttl', 600);
        foreach ($byProvider as $providerId => $rows) {
            try {
                $provider = ProviderRegistry::find($providerId);
                if (!$provider->supports(Capability::RATES)) {
                    continue;
                }
                $groups = $provider instanceof StayApiProvider ? $this->groupByDestination($providerId, $rows) : ['' => $rows];
                foreach ($groups as $destId => $groupRows) {
                    $extIds = array_values(array_unique(array_map(static fn ($r) => (string) $r['external_hotel_id'], $groupRows)));
                    sort($extIds);
                    $adapter = $provider instanceof StayApiProvider ? $provider->withDestination((string) $destId) : $provider;
                    $result = ProviderCache::remember($providerId, 'rates', $c->cacheKey() . '|' . $destId . '|' . implode(',', $extIds), $ttl, static function () use ($adapter, $extIds, $c) {
                        return [
                            'fetched_at' => date('Y-m-d H:i:s'),
                            'rates' => array_map(static fn (ProviderRate $r) => get_object_vars($r), $adapter->getRates($extIds, $c)),
                        ];
                    });
                    $this->persist($providerId, $groupRows, $result['value'], $c, $ttl);
                }
                $this->db->update('providers', ['status' => 'ok', 'last_error' => null], ['id' => $providerId]);
            } catch (ProviderException $e) {
                $this->db->update('providers', ['status' => 'degraded', 'last_error' => mb_substr($e->getMessage(), 0, 500), 'last_check_at' => date('Y-m-d H:i:s')], ['id' => $providerId]);
                $this->notices[] = 'Harici fiyat sağlayıcısına şu an ulaşılamadı; anlaşmalı fiyatlar ve geçerli kayıtlar gösteriliyor.';
            } catch (\Throwable $e) {
                Logger::error('Sağlayıcı fiyat hatası', ['provider' => $providerId, 'error' => $e->getMessage()]);
                $this->notices[] = 'Harici fiyatlar alınamadı; anlaşmalı fiyatlar gösteriliyor.';
            }
        }
        $this->notices = array_values(array_unique($this->notices));
    }

    private function groupByDestination(int $providerId, array $rows): array
    {
        $dest = [];
        foreach ($this->db->fetchAll('SELECT region_id, external_id FROM provider_destinations WHERE provider_id = ? AND verified_at IS NOT NULL', [$providerId]) as $d) {
            $dest[(int) $d['region_id']] = $d['external_id'];
        }
        $groups = [];
        foreach ($rows as $r) {
            $id = $dest[(int) $r['region_id']] ?? ($r['region_parent'] !== null ? ($dest[(int) $r['region_parent']] ?? null) : null);
            if ($id !== null) {
                $groups[$id][] = $r;
            }
        }
        return $groups;
    }

    private function persist(int $providerId, array $mapRows, array $payload, StayCriteria $c, int $ttl): void
    {
        $extToHotel = [];
        foreach ($mapRows as $m) {
            $extToHotel[(string) $m['external_hotel_id']] = (int) $m['hotel_id'];
        }
        $concepts = [];
        foreach ($this->db->fetchAll('SELECT id, code FROM concepts') as $co) {
            $concepts[strtoupper($co['code'])] = (int) $co['id'];
        }
        $fetchedAt = (string) ($payload['fetched_at'] ?? date('Y-m-d H:i:s'));
        $validUntil = date('Y-m-d H:i:s', min(strtotime($fetchedAt) + $ttl, time() + $ttl));
        if (strtotime($validUntil) <= time()) {
            return;
        }
        $this->db->transaction(function (Database $db) use ($providerId, $extToHotel, $payload, $c, $concepts, $fetchedAt, $validUntil): void {
            $hotelIds = array_values($extToHotel);
            [$in, $p] = Database::in($hotelIds, 'h');
            $db->query(
                "DELETE FROM reference_prices WHERE source = 'provider' AND provider_id = :pid AND hotel_id IN $in AND check_in = :ci AND check_out = :co AND adults = :ad AND children_ages = :ages AND rooms_count = :rc",
                $p + ['pid' => $providerId, 'ci' => $c->checkIn, 'co' => $c->checkOut, 'ad' => $c->adults(), 'ages' => implode(',', $c->allChildAges()), 'rc' => $c->roomCount()],
            );
            $perHotel = [];
            foreach ((array) ($payload['rates'] ?? []) as $r) {
                $hotelId = $extToHotel[(string) ($r['externalHotelId'] ?? '')] ?? null;
                if ($hotelId === null || (int) ($r['totalMinor'] ?? 0) <= 0 || strtoupper((string) $r['currency']) !== 'TRY') {
                    continue;
                }
                if (($perHotel[$hotelId] = ($perHotel[$hotelId] ?? 0) + 1) > 10) {
                    continue;
                }
                $db->insert('reference_prices', [
                    'hotel_id' => $hotelId, 'room_id' => null, 'room_name' => mb_substr((string) $r['roomName'], 0, 150),
                    'source' => 'provider', 'provider_id' => $providerId, 'source_label' => null, 'currency' => 'TRY',
                    'total_minor' => (int) $r['totalMinor'], 'tax_included' => !empty($r['taxIncluded']) ? 1 : 0,
                    'check_in' => $c->checkIn, 'check_out' => $c->checkOut, 'adults' => $c->adults(),
                    'children_ages' => implode(',', $c->allChildAges()), 'rooms_count' => $c->roomCount(),
                    'concept_id' => $concepts[strtoupper((string) ($r['boardCode'] ?? ''))] ?? null,
                    'refundable' => $r['refundable'] === null ? null : ((bool) $r['refundable'] ? 1 : 0),
                    'cancellation_summary' => $r['cancellationSummary'] ?? null, 'captured_at' => $fetchedAt, 'valid_until' => $validUntil,
                    'external_rate_id' => $r['rateKey'] !== null ? mb_substr((string) $r['rateKey'], 0, 190) : null,
                    'provider_bookable' => !empty($r['bookable']) ? 1 : 0,
                ]);
            }
        });
    }

    /** Yönetimden bağlantı testi; sonucu providers tablosuna yazar. */
    public static function test(int $providerId): array
    {
        $db = App::db();
        try {
            $provider = ProviderRegistry::find($providerId);
            $h = $provider->healthCheck();
            $db->update('providers', [
                'status' => $h->ok ? 'ok' : 'error', 'last_check_at' => date('Y-m-d H:i:s'), 'last_error' => $h->ok ? null : $h->message,
                'quota_remaining' => $h->quotaRemaining,
            ], ['id' => $providerId]);
            return ['ok' => $h->ok, 'message' => $h->message . ($h->latencyMs !== null ? ' (' . $h->latencyMs . ' ms)' : '')];
        } catch (ProviderException $e) {
            $db->update('providers', ['status' => 'error', 'last_check_at' => date('Y-m-d H:i:s'), 'last_error' => mb_substr($e->getMessage(), 0, 500)], ['id' => $providerId]);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}
