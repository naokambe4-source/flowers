<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Core\App;
use App\Services\ProviderRateService;

/** Etkin harici sağlayıcıların bağlantı durumunu düzenli kontrol eder. */
final class ProviderHealthJob implements JobInterface
{
    public function handle(array $payload): void
    {
        foreach (App::db()->fetchAll("SELECT id FROM providers WHERE is_enabled = 1 AND code <> 'manual'") as $p) {
            ProviderRateService::test((int) $p['id']);
        }
    }
}
