<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Logger;
use App\Jobs\JobInterface;

/**
 * cPanel cron ile çalışan veritabanı tabanlı iş kuyruğu.
 * Başarısız işler artan bekleme ile tekrar denenir; deneme hakkı bitince "failed" olur ve yönetimden yeniden kuyruğa alınabilir.
 */
final class QueueService
{
    public const TYPES = [
        'send_email' => \App\Jobs\SendEmailJob::class,
        'expire_offers' => \App\Jobs\ExpireOffersJob::class,
        'complete_bookings' => \App\Jobs\CompleteBookingsJob::class,
        'cleanup' => \App\Jobs\CleanupJob::class,
        'provider_health' => \App\Jobs\ProviderHealthJob::class,
    ];

    public static function push(string $type, array $payload = [], int $delaySeconds = 0, int $maxAttempts = 5): int
    {
        if (!isset(self::TYPES[$type])) {
            throw new \InvalidArgumentException('Bilinmeyen iş tipi: ' . $type);
        }
        return App::db()->insert('jobs', [
            'type' => $type,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => 'queued',
            'max_attempts' => $maxAttempts,
            'available_at' => date('Y-m-d H:i:s', time() + $delaySeconds),
        ]);
    }

    /** @return array{done:int, failed:int} */
    public static function work(int $limit = 20, int $maxSeconds = 50): array
    {
        $db = App::db();
        $start = time();
        $done = 0;
        $failed = 0;
        // Takılı kalmış işleri (15 dk) serbest bırak
        $db->query("UPDATE jobs SET status = 'queued', reserved_at = NULL WHERE status = 'running' AND reserved_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        for ($i = 0; $i < $limit && time() - $start < $maxSeconds; $i++) {
            $job = $db->transaction(static function ($db) {
                $row = $db->fetch("SELECT * FROM jobs WHERE status = 'queued' AND available_at <= NOW() ORDER BY id LIMIT 1 FOR UPDATE");
                if (!$row) {
                    return null;
                }
                $db->update('jobs', ['status' => 'running', 'reserved_at' => date('Y-m-d H:i:s'), 'attempts' => (int) $row['attempts'] + 1], ['id' => $row['id']]);
                $row['attempts'] = (int) $row['attempts'] + 1;
                return $row;
            });
            if ($job === null) {
                break;
            }
            try {
                $class = self::TYPES[$job['type']] ?? null;
                if ($class === null) {
                    throw new \RuntimeException('Tanımsız iş tipi');
                }
                /** @var JobInterface $handler */
                $handler = new $class();
                $handler->handle(json_decode((string) $job['payload'], true) ?: []);
                $db->update('jobs', ['status' => 'done', 'completed_at' => date('Y-m-d H:i:s'), 'last_error' => null], ['id' => $job['id']]);
                $done++;
            } catch (\Throwable $e) {
                $final = $job['attempts'] >= (int) $job['max_attempts'];
                $db->update('jobs', [
                    'status' => $final ? 'failed' : 'queued',
                    'available_at' => date('Y-m-d H:i:s', time() + 60 * (2 ** min(6, $job['attempts']))),
                    'reserved_at' => null,
                    'last_error' => mb_substr($e->getMessage(), 0, 1000),
                ], ['id' => $job['id']]);
                Logger::warning('İş başarısız', ['job' => $job['id'], 'type' => $job['type'], 'error' => $e->getMessage()]);
                $failed++;
            }
        }
        return ['done' => $done, 'failed' => $failed];
    }

    public static function retry(int $jobId): void
    {
        App::db()->update('jobs', ['status' => 'queued', 'attempts' => 0, 'available_at' => date('Y-m-d H:i:s'), 'last_error' => null], ['id' => $jobId]);
    }

    /** Periyodik görevler (cron her çalıştığında, zamanı gelmişse kuyruğa eklenir). */
    public static function schedulePeriodic(): void
    {
        $tasks = ['expire_offers' => 300, 'complete_bookings' => 3600, 'cleanup' => 86400, 'provider_health' => 3600];
        foreach ($tasks as $type => $every) {
            $key = 'cron.last_' . $type;
            $last = (int) SettingsService::get($key, '0');
            if (time() - $last >= $every) {
                self::push($type, [], 0, 3);
                SettingsService::set($key, (string) time());
            }
        }
        SettingsService::set('cron.last_run', date('Y-m-d H:i:s'));
    }
}
