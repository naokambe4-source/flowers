<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Url;
use App\Core\View;
use App\Exceptions\DomainException;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Onaylı rezervasyon için sunucuda PDF voucher.
 * QR kod yalnız tahmin edilemez doğrulama belirteci içerir; kişisel veri içermez.
 */
final class VoucherService
{
    public static function qrDataUri(string $text): string
    {
        $gd = extension_loaded('gd');
        $options = new QROptions([
            'outputInterface' => $gd ? QRGdImagePNG::class : QRMarkupSVG::class,
            'outputBase64' => true,
            'scale' => 6,
            'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M,
            'addQuietzone' => true,
        ]);
        return (new QRCode($options))->render($text);
    }

    public static function render(int $bookingId): string
    {
        $db = App::db();
        $b = $db->fetch('SELECT b.*, h.name AS hotel_name, h.address, h.check_in_time, h.check_out_time, r.name AS region_name
                         FROM bookings b JOIN hotels h ON h.id = b.hotel_id LEFT JOIN regions r ON r.id = h.region_id WHERE b.id = ?', [$bookingId]);
        if (!$b || !in_array($b['status'], ['confirmed', 'completed'], true) || !$b['verify_token']) {
            throw new DomainException('Voucher yalnız onaylanmış rezervasyonlar için oluşturulabilir.');
        }
        $rooms = $db->fetchAll('SELECT * FROM booking_rooms WHERE booking_id = ? ORDER BY id', [$bookingId]);
        $guests = $db->fetchAll('SELECT * FROM booking_guests WHERE booking_id = ? ORDER BY booking_room_id, is_lead DESC, id', [$bookingId]);
        $terms = json_decode((string) $b['terms_snapshot'], true) ?: [];
        $verifyUrl = Url::absolute('/voucher/dogrula/' . $b['verify_token']);
        $logo = null;
        $logoKey = SettingsService::get('site.logo');
        if ($logoKey !== '' && ($p = ImageService::path('site', $logoKey, 'thumb'))) {
            $logo = 'data:' . $p[1] . ';base64,' . base64_encode((string) file_get_contents($p[0]));
        }
        $html = View::partial('pdf/voucher', [
            'b' => $b, 'rooms' => $rooms, 'guests' => $guests, 'terms' => $terms, 'qr' => self::qrDataUri($verifyUrl),
            'verifyUrl' => $verifyUrl, 'logo' => $logo, 'siteName' => SettingsService::get('site.name', 'Kurumsal Konaklama'),
            'supportPhone' => SettingsService::get('contact.phone'), 'supportEmail' => SettingsService::get('contact.email'),
        ]);
        $opt = new Options();
        $opt->set('defaultFont', 'DejaVu Sans');
        $opt->set('isRemoteEnabled', false);
        $opt->set('isPhpEnabled', false);
        $opt->setChroot([APP_ROOT . '/vendor/dompdf/dompdf']);
        $tmp = APP_ROOT . '/storage/tmp';
        $opt->setTempDir($tmp);
        $opt->setFontCache($tmp);
        $pdf = new Dompdf($opt);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return (string) $pdf->output();
    }
}
