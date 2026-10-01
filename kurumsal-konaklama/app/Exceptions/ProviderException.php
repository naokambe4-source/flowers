<?php
declare(strict_types=1);

namespace App\Exceptions;

/** Sağlayıcı (API) hatası. Mesaj maskelenmiş ve kullanıcıya gösterilmeye uygun tutulur. */
class ProviderException extends \RuntimeException
{
    public function __construct(string $message, public readonly string $providerCode = '', public readonly bool $retryable = false, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
