<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Response;
use App\Services\RateLimiter;

/** QR doğrulama: yalnız gerekli bilgiler (kod, durum, tarihler, misafir baş harfleri). Otel adı ve kişisel veri gösterilmez. */
final class VoucherVerifyController extends Controller
{
    public function show(string $token): Response
    {
        if (!RateLimiter::attempt('verify:' . $this->request->ip(), 60, 3600)) {
            throw new \App\Exceptions\HttpException(429);
        }
        $b = App::db()->fetch('SELECT id, code, status, check_in, check_out, nights, rooms_count, adults, children, contact_name FROM bookings WHERE verify_token = ?', [$token]);
        $masked = null;
        if ($b) {
            $masked = implode(' ', array_map(static fn ($p) => mb_substr($p, 0, 1) . str_repeat('*', max(2, mb_strlen($p) - 1)), preg_split('/\s+/', trim((string) $b['contact_name'])) ?: []));
        }
        return $this->view('site/verify', ['title' => 'Voucher doğrulama', 'b' => $b, 'masked' => $masked], 'guest');
    }
}
