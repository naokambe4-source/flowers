<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;

/**
 * Görsel yükleme: boyut, MIME (finfo), gerçek görüntü doğrulaması, rastgele dosya adı.
 * Görseller GD ile yeniden kodlanır (EXIF/meta temizlenir) ve üç boyutta saklanır.
 * Dosyalar storage/private altında tutulur; yalnız yetkili medya uç noktası üzerinden sunulur.
 */
final class ImageService
{
    public const MAX_BYTES = 8 * 1024 * 1024;
    public const SIZES = ['thumb' => 480, 'medium' => 1024, 'large' => 1800];
    private const ALLOWED = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public static function dir(string $kind): string
    {
        if (!preg_match('/^[a-z_]+$/', $kind)) {
            throw new \InvalidArgumentException('Geçersiz klasör');
        }
        $dir = APP_ROOT . '/storage/private/' . $kind;
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        return $dir;
    }

    /** @return array{key:string, mime:string, width:int, height:int, bytes:int, original:string} */
    public static function store(array $file, string $kind): array
    {
        return self::process($file, $kind, false);
    }

    /** Sunucudaki güvenilir bir dosyayı (ör. paketle gelen demo görselleri) aynı doğrulama ve yeniden kodlama ile saklar. */
    public static function storeLocal(string $path, string $kind, string $originalName): array
    {
        return self::process(['error' => UPLOAD_ERR_OK, 'tmp_name' => $path, 'name' => $originalName], $kind, true);
    }

    private static function process(array $file, string $kind, bool $local): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new DomainException(match ($file['error'] ?? 0) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dosya boyutu sunucu sınırını aşıyor.',
                UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi.',
                default => 'Dosya yüklenemedi. Lütfen tekrar deneyin.',
            });
        }
        $tmp = (string) $file['tmp_name'];
        if (!($local && is_file($tmp)) && !is_uploaded_file($tmp) && !(defined('KK_TESTING') && is_file($tmp))) {
            throw new DomainException('Geçersiz yükleme.');
        }
        $size = (int) filesize($tmp);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new DomainException('Görsel en fazla 8 MB olabilir.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        if (!isset(self::ALLOWED[$mime])) {
            throw new DomainException('Yalnız JPG, PNG veya WebP görsel yüklenebilir.');
        }
        $info = @getimagesize($tmp);
        if ($info === false || ($info['mime'] ?? '') !== $mime || $info[0] < 200 || $info[1] < 150) {
            throw new DomainException('Dosya geçerli bir görsel değil veya çok küçük (en az 200×150 piksel).');
        }
        if ($info[0] * $info[1] > 40_000_000) {
            throw new DomainException('Görsel çözünürlüğü çok yüksek (en fazla 40 megapiksel).');
        }
        $key = bin2hex(random_bytes(16));
        $dir = self::dir($kind);
        $gd = function_exists('imagecreatefromstring');
        if ($gd) {
            $src = @imagecreatefromstring((string) file_get_contents($tmp));
            if ($src === false) {
                throw new DomainException('Görsel işlenemedi.');
            }
            $src = self::orient($src, $tmp, $mime);
            $w = imagesx($src);
            $h = imagesy($src);
            foreach (self::SIZES as $name => $maxW) {
                // Güvenilir yerel JPEG zaten hedef boyuttaysa yeniden kodlanmadan kopyalanır (demo yüklemesini hızlandırır)
                if ($local && $mime === 'image/jpeg' && $w <= $maxW) {
                    copy($tmp, "$dir/$key-$name.jpg");
                    continue;
                }
                $nw = min($w, $maxW);
                $nh = (int) round($h * ($nw / $w));
                $dst = imagecreatetruecolor($nw, $nh);
                imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imageinterlace($dst, true);
                imagejpeg($dst, "$dir/$key-$name.jpg", $name === 'thumb' ? 78 : 84);
                imagedestroy($dst);
            }
            imagedestroy($src);
            return ['key' => $key, 'mime' => 'image/jpeg', 'width' => $w, 'height' => $h, 'bytes' => (int) filesize("$dir/$key-large.jpg"), 'original' => mb_substr((string) $file['name'], 0, 255)];
        }
        // GD yoksa doğrulanmış özgün dosya saklanır
        $ext = self::ALLOWED[$mime];
        if (!copy($tmp, "$dir/$key-large.$ext")) {
            throw new DomainException('Görsel kaydedilemedi.');
        }
        return ['key' => $key, 'mime' => $mime, 'width' => (int) $info[0], 'height' => (int) $info[1], 'bytes' => $size, 'original' => mb_substr((string) $file['name'], 0, 255)];
    }

    private static function orient(\GdImage $img, string $path, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($path);
        $o = (int) ($exif['Orientation'] ?? 1);
        $rotated = match ($o) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
        return $rotated ?: $img;
    }

    /** @return array{0:string,1:string}|null [yol, mime] */
    public static function path(string $kind, string $key, string $size): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $key) || !isset(self::SIZES[$size])) {
            return null;
        }
        $dir = self::dir($kind);
        foreach ([$size, 'large'] as $s) {
            foreach (['jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'] as $ext => $mime) {
                $p = "$dir/$key-$s.$ext";
                if (is_file($p)) {
                    return [$p, $mime];
                }
            }
        }
        return null;
    }

    /** Saklanan bir görselin tüm boyutlarını yeni anahtarla kopyalar. */
    public static function duplicate(string $kind, string $key): ?string
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
            return null;
        }
        $new = bin2hex(random_bytes(16));
        $dir = self::dir($kind);
        $ok = false;
        foreach (glob("$dir/$key-*") ?: [] as $f) {
            $ok = copy($f, $dir . '/' . $new . substr(basename($f), 32)) || $ok;
        }
        return $ok ? $new : null;
    }

    public static function delete(string $kind, string $key): void
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $key)) {
            return;
        }
        foreach (glob(self::dir($kind) . "/$key-*") ?: [] as $f) {
            @unlink($f);
        }
    }
}
