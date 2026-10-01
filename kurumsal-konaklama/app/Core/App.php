<?php
declare(strict_types=1);

namespace App\Core;

use App\Exceptions\DomainException;
use App\Exceptions\HttpException;
use App\Exceptions\ValidationException;
use App\Services\SettingsService;

final class App
{
    public const VERSION = '1.0.0';

    private static ?Database $db = null;
    private static ?Router $router = null;
    private static ?Request $request = null;
    private static bool $installed = false;

    public function __construct(private readonly string $root)
    {
    }

    public static function db(): Database
    {
        if (self::$db === null) {
            $cfg = Config::get('db');
            if (!is_array($cfg)) {
                throw new \RuntimeException('Veritabanı ayarları bulunamadı.');
            }
            self::$db = new Database($cfg);
        }
        return self::$db;
    }

    public static function setDb(?Database $db): void
    {
        self::$db = $db;
    }

    public static function router(): Router
    {
        if (self::$router === null) {
            self::$router = new Router();
            (require APP_ROOT . '/app/routes.php')(self::$router);
        }
        return self::$router;
    }

    public static function request(): ?Request
    {
        return self::$request;
    }

    public static function setRequest(?Request $r): void
    {
        self::$request = $r;
    }

    public static function installed(): bool
    {
        return self::$installed;
    }

    public static function lockFile(): string
    {
        return APP_ROOT . '/storage/installed.lock';
    }

    public static function detectInstalled(): bool
    {
        return self::$installed = Config::load() && is_file(self::lockFile());
    }

    public function run(): void
    {
        $this->configureRuntime();
        $request = Request::capture();
        self::$request = $request;
        Session::start($request->isSecure());
        $response = $this->handle($request);
        $response->send();
    }

    private function configureRuntime(): void
    {
        self::detectInstalled();
        date_default_timezone_set((string) Config::get('app.timezone', 'Europe/Istanbul'));
        mb_internal_encoding('UTF-8');
        $debug = (bool) Config::get('app.debug', false);
        ini_set('display_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);
        set_error_handler(static function (int $no, string $str, string $file, int $line): bool {
            if (!(error_reporting() & $no)) {
                return false;
            }
            throw new \ErrorException($str, 0, $no, $file, $line);
        });
    }

    public function handle(Request $request): Response
    {
        try {
            $response = $this->dispatch($request);
        } catch (\Throwable $e) {
            $response = self::renderException($e, $request);
        }
        return $this->withSecurityHeaders($response, $request);
    }

    private function dispatch(Request $request): Response
    {
        $path = $request->path;

        if (!self::$installed) {
            if (!str_starts_with($path, '/kurulum')) {
                // Rewrite henüz doğrulanmadığı için index.php üzerinden yönlendir
                return Response::redirect(Url::basePath() . '/index.php/kurulum');
            }
        } elseif (str_starts_with($path, '/kurulum') && $path !== '/kurulum/rewrite-test') {
            throw new HttpException(404, 'Kurulum tamamlanmış ve kilitlenmiştir.');
        }

        if (self::$installed && Config::get('app.force_https', false) && !$request->isSecure() && PHP_SAPI !== 'cli') {
            return Response::redirect(Url::absolute($path, $request->query), 301);
        }

        if ($legacy = $this->legacyRedirect($request)) {
            return $legacy;
        }

        [$route, $params] = self::router()->resolve($request->method === 'HEAD' ? 'GET' : $request->method, $path);
        $request->setRouteParams($params);

        if ($request->isPost() && !in_array('nocsrf', $route['middleware'], true) && !Csrf::validate($request)) {
            throw new HttpException(419);
        }

        foreach ($route['middleware'] as $mw) {
            if ($r = Middleware::handle($mw, $request)) {
                return $r;
            }
        }

        $handler = $route['handler'];
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class($request);
            $result = $controller->$method(...array_values($params));
        } else {
            $result = $handler($request, ...array_values($params));
        }
        return $result instanceof Response ? $result : Response::html((string) $result);
    }

    /** Eski query-string adreslerini yeni temiz adreslere 301 ile yönlendirir. */
    private function legacyRedirect(Request $request): ?Response
    {
        $page = $request->query['page'] ?? null;
        if (!is_string($page) || !in_array($request->path, ['/', '/index.php'], true)) {
            return null;
        }
        $map = [
            'login' => '/giris', 'giris' => '/giris', 'dashboard' => '/panel', 'panel' => '/panel', 'home' => '/panel',
            'hotels' => '/oteller', 'oteller' => '/oteller', 'bookings' => '/rezervasyonlarim', 'reservations' => '/rezervasyonlarim',
            'offers' => '/tekliflerim', 'favorites' => '/favorilerim', 'profile' => '/profilim', 'support' => '/destek',
            'register' => '/erisim-talebi', 'apply' => '/erisim-talebi', 'admin' => '/yonetim', 'contact' => '/iletisim',
        ];
        if ($page === 'hotel' && is_string($request->query['slug'] ?? null)) {
            return Response::to('/oteller/' . rawurlencode($request->query['slug']), [], 301);
        }
        return isset($map[$page]) ? Response::to($map[$page], [], 301) : Response::to('/', [], 301);
    }

    private function withSecurityHeaders(Response $response, Request $request): Response
    {
        $response->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');
        $public = in_array($request->path, ['/', '/giris'], true);
        if (!$public) {
            $response->withHeader('X-Robots-Tag', 'noindex, nofollow');
        }
        if (Auth::check() && !isset($response->headers()['Cache-Control'])) {
            $response->withHeader('Cache-Control', 'private, no-store');
        }
        return $response;
    }

    public static function renderException(\Throwable $e, Request $request): Response
    {
        if ($e instanceof ValidationException) {
            if ($request->wantsJson()) {
                return Response::json(['ok' => false, 'message' => $e->getMessage(), 'errors' => $e->errors], 422);
            }
            Session::flashInput($request->post, $e->errors);
            Session::flash('error', $e->getMessage());
            return self::back($request);
        }
        if ($e instanceof DomainException) {
            if ($request->wantsJson()) {
                return Response::json(['ok' => false, 'message' => $e->getMessage()], 409);
            }
            Session::flashInput($request->post);
            Session::flash('error', $e->getMessage());
            return self::back($request);
        }
        if ($e instanceof HttpException) {
            if ($e->status === 401) {
                if ($request->wantsJson()) {
                    return Response::json(['ok' => false, 'message' => $e->getMessage()], 401);
                }
                Session::put('intended', $request->method === 'GET' ? $request->fullUrl() : null);
                return Response::to('/giris');
            }
            if ($request->wantsJson() || str_starts_with($request->path, '/medya/')) {
                return $request->wantsJson()
                    ? Response::json(['ok' => false, 'message' => $e->getMessage()], $e->status)
                    : new Response('', $e->status, ['Content-Type' => 'text/plain']);
            }
            return self::errorPage($e->status, $e->getMessage());
        }

        Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine(), 'path' => $request->path, 'trace' => substr($e->getTraceAsString(), 0, 3000)]);
        if ($request->wantsJson()) {
            return Response::json(['ok' => false, 'message' => 'Beklenmeyen bir hata oluştu. Lütfen tekrar deneyin.'], 500);
        }
        $msg = Config::get('app.debug', false) ? $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() : 'Beklenmeyen bir hata oluştu. Sorun kaydedildi; lütfen biraz sonra tekrar deneyin.';
        return self::errorPage(500, $msg);
    }

    private static function errorPage(int $status, string $message): Response
    {
        try {
            $layout = self::$installed && Auth::check() ? (Auth::can('admin.access') && str_starts_with((string) self::$request?->path, '/yonetim') ? 'admin' : 'member') : 'guest';
            $html = View::render('errors/http', ['status' => $status, 'message' => $message, 'title' => 'Hata ' . $status], $layout);
        } catch (\Throwable $inner) {
            Logger::error('Hata sayfası oluşturulamadı: ' . $inner->getMessage());
            $html = '<!doctype html><meta charset="utf-8"><title>Hata</title><p>' . htmlspecialchars($message) . '</p>';
        }
        return Response::html($html, $status);
    }

    public static function back(Request $request, string $fallback = '/panel'): Response
    {
        $ref = (string) ($request->server['HTTP_REFERER'] ?? '');
        $target = null;
        if ($ref !== '') {
            $p = parse_url($ref);
            $host = (string) ($request->server['HTTP_HOST'] ?? '');
            if (($p['host'] ?? '') !== '' && isset($p['port'])) {
                $p['host'] .= ':' . $p['port'];
            }
            if (($p['host'] ?? '') === $host) {
                $target = ($p['path'] ?? '/') . (isset($p['query']) ? '?' . $p['query'] : '');
            }
        }
        return Response::redirect(Url::safeRedirectTarget($target, $fallback));
    }

    public static function siteName(): string
    {
        try {
            return self::$installed ? SettingsService::get('site.name', 'Kurumsal Konaklama') : 'Kurumsal Konaklama';
        } catch (\Throwable) {
            return 'Kurumsal Konaklama';
        }
    }
}
