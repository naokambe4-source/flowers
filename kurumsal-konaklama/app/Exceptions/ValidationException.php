<?php
declare(strict_types=1);

namespace App\Exceptions;

/** Form doğrulama hatası: kullanıcıya alan bazlı mesajlar döner. */
class ValidationException extends \RuntimeException
{
    public function __construct(public readonly array $errors, string $message = 'Lütfen işaretli alanları kontrol edin.')
    {
        parent::__construct($message);
    }
}
