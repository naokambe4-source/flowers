<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Services\Mailer;

final class SendEmailJob implements JobInterface
{
    public function handle(array $payload): void
    {
        if (empty($payload['to']) || !filter_var($payload['to'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Geçersiz alıcı');
        }
        Mailer::send((string) $payload['to'], (string) ($payload['subject'] ?? ''), (string) ($payload['body'] ?? ''));
    }
}
