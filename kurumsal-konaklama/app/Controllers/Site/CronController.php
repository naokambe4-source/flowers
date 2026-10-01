<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Core\Config;
use App\Core\Response;
use App\Exceptions\HttpException;
use App\Services\QueueService;

/** CLI cron kullanılamayan hostingler için token korumalı HTTP tetikleyici. */
final class CronController extends Controller
{
    public function run(string $token): Response
    {
        $expected = (string) Config::get('app.cron_token', '');
        if (strlen($expected) < 32 || !hash_equals($expected, $token)) {
            throw new HttpException(404);
        }
        QueueService::schedulePeriodic();
        $r = QueueService::work(20, 25);
        return Response::json(['ok' => true] + $r);
    }
}
