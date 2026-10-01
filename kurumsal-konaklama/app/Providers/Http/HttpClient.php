<?php
declare(strict_types=1);

namespace App\Providers\Http;

use App\Core\App;
use App\Core\Auth;
use App\Exceptions\ProviderException;
use App\Services\RateLimiter;

/**
 * cURL tabanlı HTTP istemcisi.
 * - Bağlantı ve toplam timeout
 * - Yalnız ağ hatası, 429 ve 5xx için en fazla 2 kontrollü tekrar (artan bekleme)
 * - Sağlayıcı bazlı dakika sınırı
 * - api_request_logs tablosuna maskelenmiş kayıt (anahtarlar asla loglanmaz)
 */
class HttpClient
{
    /** Test ortamında gerçek ağ çağrısı yerine kullanılacak sahte yanıtlayıcı. */
    public static ?\Closure $fake = null;

    public function __construct(
        private readonly int $providerId,
        private readonly int $timeout = 10,
        private readonly int $perMinute = 30,
        private readonly array $secretValues = [],
    ) {
    }

    /** @return array{status:int, headers:array, body:string, json:mixed, duration:int} */
    public function request(string $operation, string $method, string $url, array $headers = [], ?array $jsonBody = null, int $maxRetries = 2): array
    {
        if (!RateLimiter::attempt('provider:' . $this->providerId, max(1, $this->perMinute), 60)) {
            $this->log($operation, $method, $url, null, 0, false, 'Sağlayıcı dakika sınırı aşıldı');
            throw new ProviderException('Sağlayıcı istek sınırına ulaşıldı. Lütfen biraz sonra tekrar deneyin.', '', true);
        }
        $attempt = 0;
        $lastError = '';
        while (true) {
            $attempt++;
            $start = microtime(true);
            try {
                $res = self::$fake !== null
                    ? (self::$fake)($method, $url, $headers, $jsonBody)
                    : $this->send($method, $url, $headers, $jsonBody);
            } catch (\Throwable $e) {
                $res = ['status' => 0, 'headers' => [], 'body' => '', 'error' => $e->getMessage()];
            }
            $duration = (int) round((microtime(true) - $start) * 1000);
            $status = (int) $res['status'];
            $retryable = $status === 0 || $status === 429 || $status >= 500;
            $ok = $status >= 200 && $status < 300;
            $lastError = $ok ? '' : ($res['error'] ?? ('HTTP ' . $status . ' ' . mb_substr(strip_tags((string) $res['body']), 0, 180)));
            $this->log($operation, $method, $url, $status ?: null, $duration, $ok, $ok ? null : $lastError);
            if ($ok) {
                $json = json_decode((string) $res['body'], true);
                return ['status' => $status, 'headers' => $res['headers'] ?? [], 'body' => (string) $res['body'], 'json' => $json, 'duration' => $duration];
            }
            if (!$retryable || $attempt > $maxRetries) {
                throw new ProviderException($this->mask('Sağlayıcı hatası: ' . $lastError), '', $retryable);
            }
            usleep(self::$fake !== null ? 0 : (300000 * $attempt * $attempt));
        }
    }

    private function send(string $method, string $url, array $headers, ?array $jsonBody): array
    {
        $ch = curl_init();
        $hdrs = [];
        foreach ($headers as $k => $v) {
            $hdrs[] = $k . ': ' . $v;
        }
        $hdrs[] = 'Accept: application/json';
        $hdrs[] = 'Accept-Encoding: gzip';
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_CONNECTTIMEOUT => min(5, $this->timeout),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => '',
            CURLOPT_USERAGENT => 'KurumsalKonaklama/' . App::VERSION,
        ];
        if ($jsonBody !== null) {
            $hdrs[] = 'Content-Type: application/json';
            $opts[CURLOPT_POSTFIELDS] = json_encode($jsonBody, JSON_UNESCAPED_UNICODE);
        }
        $opts[CURLOPT_HTTPHEADER] = $hdrs;
        $respHeaders = [];
        $opts[CURLOPT_HEADERFUNCTION] = static function ($ch, string $line) use (&$respHeaders): int {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($line);
        };
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($body === false) {
            return ['status' => 0, 'headers' => [], 'body' => '', 'error' => 'Bağlantı hatası: ' . $err];
        }
        return ['status' => $status, 'headers' => $respHeaders, 'body' => (string) $body];
    }

    private function mask(string $text): string
    {
        foreach ($this->secretValues as $s) {
            if (is_string($s) && strlen($s) >= 4) {
                $text = str_replace($s, '••••' . substr($s, -4), $text);
            }
        }
        return $text;
    }

    private function log(string $operation, string $method, string $url, ?int $status, int $duration, bool $ok, ?string $error): void
    {
        $parts = parse_url($url);
        $endpoint = ($parts['path'] ?? '/');
        $query = [];
        parse_str($parts['query'] ?? '', $query);
        foreach ($query as $k => $v) {
            if (preg_match('/key|token|secret|sig|email/i', (string) $k)) {
                $query[$k] = '***';
            }
        }
        try {
            App::db()->insert('api_request_logs', [
                'provider_id' => $this->providerId,
                'operation' => $operation,
                'method' => $method,
                'endpoint' => mb_substr(($parts['host'] ?? '') . $endpoint, 0, 255),
                'request_summary' => $this->mask(mb_substr((string) json_encode($query, JSON_UNESCAPED_UNICODE), 0, 1000)),
                'status_code' => $status,
                'duration_ms' => $duration,
                'success' => $ok ? 1 : 0,
                'error' => $error === null ? null : $this->mask(mb_substr($error, 0, 500)),
                'user_id' => Auth::id(),
            ]);
        } catch (\Throwable) {
            // Loglama hatası isteği bozmamalı
        }
    }
}
