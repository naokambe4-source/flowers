<?php
declare(strict_types=1);

namespace App\Jobs;

interface JobInterface
{
    /** Hata durumunda exception fırlatmalıdır; kuyruk tekrar deneme yapar. */
    public function handle(array $payload): void;
}
