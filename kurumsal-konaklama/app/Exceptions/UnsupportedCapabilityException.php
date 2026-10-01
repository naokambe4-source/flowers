<?php
declare(strict_types=1);

namespace App\Exceptions;

/** Sağlayıcının desteklemediği bir işlem çağrıldı. Asla başarılı gibi davranılmaz. */
class UnsupportedCapabilityException extends ProviderException
{
}
