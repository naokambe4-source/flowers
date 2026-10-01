<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $routeParams = [];
    private ?array $jsonCache = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $files,
        public readonly array $server,
        public readonly array $cookies,
        private readonly string $rawBody = '',
    ) {
    }

    public static function capture(): self
    {
        $server = $_SERVER;
        $method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string) $_POST['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }
        $raw = '';
        if (str_contains((string) ($server['CONTENT_TYPE'] ?? ''), 'application/json')) {
            $raw = (string) file_get_contents('php://input');
        }
        return new self(
            $method,
            self::resolvePath($server),
            $_GET,
            $_POST,
            $_FILES,
            $server,
            $_COOKIE,
            $raw,
        );
    }

    /** Kurulum yolundan bağımsız uygulama içi yol: "/oteller/abc". */
    public static function resolvePath(array $server): string
    {
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? rawurldecode($path) : '/';
        $base = Url::basePath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }
        if (str_starts_with($path, '/index.php')) {
            $path = substr($path, strlen('/index.php'));
        }
        $path = '/' . trim($path, '/');
        return preg_replace('#/+#', '/', $path) ?? '/';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->post)) {
            return $this->post[$key];
        }
        $json = $this->json();
        if (array_key_exists($key, $json)) {
            return $json[$key];
        }
        return $this->query[$key] ?? $default;
    }

    public function str(string $key, string $default = ''): string
    {
        $v = $this->input($key, $default);
        return is_scalar($v) ? trim((string) $v) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $v = $this->input($key);
        return is_numeric($v) ? (int) $v : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->post) || array_key_exists($key, $this->query);
    }

    public function bool(string $key): bool
    {
        $v = $this->input($key);
        return in_array($v, [1, '1', 'on', 'true', true, 'evet'], true);
    }

    public function arr(string $key): array
    {
        $v = $this->input($key, []);
        return is_array($v) ? $v : [];
    }

    public function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = $this->input($k);
        }
        return $out;
    }

    public function json(): array
    {
        if ($this->rawBody === '') {
            return [];
        }
        if ($this->jsonCache === null) {
            $d = json_decode($this->rawBody, true);
            $this->jsonCache = is_array($d) ? $d : [];
        }
        return $this->jsonCache;
    }

    public function file(string $key): ?array
    {
        $f = $this->files[$key] ?? null;
        return is_array($f) && ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? $f : null;
    }

    /** Çoklu dosya alanını düz liste olarak döndürür. */
    public function fileList(string $key): array
    {
        $f = $this->files[$key] ?? null;
        if (!is_array($f) || !is_array($f['name'] ?? null)) {
            return $this->file($key) ? [$this->file($key)] : [];
        }
        $out = [];
        foreach ($f['name'] as $i => $name) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name' => $name, 'type' => $f['type'][$i] ?? '', 'tmp_name' => $f['tmp_name'][$i] ?? '',
                'error' => $f['error'][$i] ?? 0, 'size' => $f['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $v = $this->server[$key] ?? null;
        return $v === null ? null : (string) $v;
    }

    public function isPost(): bool
    {
        return $this->method !== 'GET' && $this->method !== 'HEAD';
    }

    public function wantsJson(): bool
    {
        return str_contains((string) $this->header('Accept'), 'application/json')
            || $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function ip(): string
    {
        $ip = (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
        $trusted = (array) Config::get('app.trusted_proxies', []);
        if ($trusted && in_array($ip, $trusted, true)) {
            $fwd = (string) ($this->server['HTTP_X_FORWARDED_FOR'] ?? '');
            $first = trim(explode(',', $fwd)[0] ?? '');
            if (filter_var($first, FILTER_VALIDATE_IP)) {
                return $first;
            }
        }
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    public function userAgent(): string
    {
        return mb_substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function isSecure(): bool
    {
        return Url::isHttps($this->server);
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, ?string $default = null): ?string
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function fullUrl(): string
    {
        return (string) ($this->server['REQUEST_URI'] ?? '/');
    }
}
