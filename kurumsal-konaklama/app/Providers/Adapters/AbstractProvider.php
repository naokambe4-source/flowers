<?php
declare(strict_types=1);

namespace App\Providers\Adapters;

use App\Core\App;
use App\Core\Crypto;
use App\DTO\ProviderBookingResult;
use App\DTO\ProviderHealth;
use App\DTO\ProviderRate;
use App\DTO\StayCriteria;
use App\Exceptions\ProviderException;
use App\Exceptions\UnsupportedCapabilityException;
use App\Providers\Contracts\HotelProviderInterface;
use App\Providers\Http\HttpClient;

abstract class AbstractProvider implements HotelProviderInterface
{
    protected array $settings;
    private ?array $credentials = null;
    private ?HttpClient $http = null;

    public function __construct(protected readonly array $row)
    {
        $this->settings = json_decode((string) ($row['settings_json'] ?? '{}'), true) ?: [];
    }

    /** Yönetim ekranında istenen kimlik bilgisi alanları: anahtar => etiket */
    abstract public static function credentialFields(): array;

    public function id(): int
    {
        return (int) $this->row['id'];
    }

    public function code(): string
    {
        return (string) $this->row['code'];
    }

    public function supports(string $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    protected function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /** Kimlik bilgileri yalnız sunucuda çözülür; ön yüze veya loglara gönderilmez. */
    protected function credential(string $key): string
    {
        if ($this->credentials === null) {
            $this->credentials = [];
            foreach (App::db()->fetchAll('SELECT key_name, value_encrypted FROM provider_credentials WHERE provider_id = ?', [$this->id()]) as $c) {
                $this->credentials[$c['key_name']] = Crypto::decrypt((string) $c['value_encrypted']) ?? '';
            }
        }
        $v = $this->credentials[$key] ?? '';
        if ($v === '') {
            throw new ProviderException('Sağlayıcı anahtarı tanımlı değil: ' . $key, $this->code());
        }
        return $v;
    }

    protected function http(): HttpClient
    {
        if ($this->http === null) {
            $secrets = [];
            foreach (array_keys(static::credentialFields()) as $k) {
                try {
                    $secrets[] = $this->credential($k);
                } catch (ProviderException) {
                }
            }
            $this->http = new HttpClient($this->id(), (int) $this->setting('timeout', 10), (int) ($this->row['rate_limit_per_minute'] ?? 30), $secrets);
        }
        return $this->http;
    }

    protected function baseUrl(): string
    {
        $url = rtrim((string) $this->setting('base_url', ''), '/');
        if (!preg_match('#^https://#', $url)) {
            throw new ProviderException('Sağlayıcı adresi HTTPS olmalıdır.', $this->code());
        }
        return $url;
    }

    protected function unsupported(string $operation): never
    {
        throw new UnsupportedCapabilityException($this->row['name'] . ' bu işlemi desteklemiyor: ' . $operation, $this->code());
    }

    /** "1234.56" veya 1234.56 → kuruş; float hesaplama yapılmaz, metin üzerinden çevrilir. */
    protected static function toMinor(mixed $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }
        $s = is_float($amount) ? number_format($amount, 2, '.', '') : trim((string) $amount);
        if (!preg_match('/^(\d+)(?:\.(\d+))?$/', $s, $m)) {
            return null;
        }
        $frac = substr(str_pad($m[2] ?? '0', 3, '0'), 0, 3);
        $minor = (int) $m[1] * 100 + intdiv((int) $frac + 5, 10);
        return $minor;
    }

    public function searchDestinations(string $query): array
    {
        $this->unsupported('destinasyon arama');
    }

    public function searchHotels(string $destinationId, StayCriteria $criteria): array
    {
        $this->unsupported('otel arama');
    }

    public function getHotel(string $externalHotelId): array
    {
        $this->unsupported('otel içeriği');
    }

    public function getAvailability(string $externalHotelId, StayCriteria $criteria): array
    {
        $this->unsupported('müsaitlik');
    }

    public function getRates(array $externalHotelIds, StayCriteria $criteria): array
    {
        $this->unsupported('oda fiyatları');
    }

    public function checkRate(string $rateKey, StayCriteria $criteria): ProviderRate
    {
        $this->unsupported('fiyat doğrulama');
    }

    public function createBooking(string $rateKey, StayCriteria $criteria, array $holder, array $guests, string $clientReference): ProviderBookingResult
    {
        $this->unsupported('rezervasyon oluşturma');
    }

    public function getBooking(string $reference, array $context = []): ProviderBookingResult
    {
        $this->unsupported('rezervasyon sorgulama');
    }

    public function cancelBooking(string $reference, array $context = []): ProviderBookingResult
    {
        $this->unsupported('iptal');
    }

    public function healthCheck(): ProviderHealth
    {
        $this->unsupported('bağlantı testi');
    }
}
