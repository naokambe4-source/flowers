<?php
declare(strict_types=1);

namespace App\Exceptions;

class HttpException extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message !== '' ? $message : self::defaultMessage($status), $status);
    }

    public static function defaultMessage(int $status): string
    {
        return match ($status) {
            400 => 'Geçersiz istek.',
            401 => 'Bu sayfayı görmek için giriş yapmalısınız.',
            403 => 'Bu işlem için yetkiniz bulunmuyor.',
            404 => 'Aradığınız sayfa bulunamadı.',
            405 => 'Bu adres bu yöntemle kullanılamaz.',
            419 => 'Oturum güvenlik anahtarının süresi doldu. Sayfayı yenileyip tekrar deneyin.',
            429 => 'Çok fazla deneme yapıldı. Lütfen biraz bekleyin.',
            503 => 'Hizmet geçici olarak kullanılamıyor.',
            default => 'Beklenmeyen bir hata oluştu.',
        };
    }
}
