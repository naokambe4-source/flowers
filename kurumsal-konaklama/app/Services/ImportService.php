<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\DomainException;

/**
 * CSV / XLSX üye aktarımı: dosya → önizleme → sütun eşleştirme → satır doğrulama
 * (zorunlu alanlar, e-posta biçimi, dosya içi ve veritabanında mükerrer e-posta) → hata raporu → kayıt.
 */
final class ImportService
{
    public const FIELDS = [
        'first_name' => 'Ad', 'last_name' => 'Soyad', 'email' => 'E-posta', 'phone' => 'Telefon',
        'department' => 'Departman', 'institution' => 'Kurum adı', 'membership_type' => 'Üyelik tipi',
    ];
    public const MAX_ROWS = 2000;

    public function __construct(private readonly Database $db)
    {
    }

    /** @return array{headers:array, rows:array} */
    public static function parse(string $path, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $rows = match ($ext) {
            'csv', 'txt' => self::parseCsv($path),
            'xlsx' => self::parseXlsx($path),
            default => throw new DomainException('Yalnız CSV veya XLSX dosyası yükleyin.'),
        };
        $rows = array_values(array_filter($rows, static fn ($r) => implode('', array_map('trim', array_map('strval', $r))) !== ''));
        if (count($rows) < 2) {
            throw new DomainException('Dosyada başlık satırı ve en az bir veri satırı bulunmalıdır.');
        }
        if (count($rows) - 1 > self::MAX_ROWS) {
            throw new DomainException('Tek seferde en fazla ' . self::MAX_ROWS . ' satır aktarılabilir.');
        }
        $headers = array_map(static fn ($h) => trim((string) $h), array_shift($rows));
        $width = count($headers);
        $rows = array_map(static fn ($r) => array_slice(array_pad(array_map(static fn ($v) => trim((string) $v), $r), $width, ''), 0, $width), $rows);
        return ['headers' => $headers, 'rows' => $rows];
    }

    private static function parseCsv(string $path): array
    {
        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1254');
        }
        $first = strtok($content, "\n") ?: '';
        $delim = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        $rows = [];
        $fh = fopen('php://temp', 'w+');
        fwrite($fh, $content);
        rewind($fh);
        while (($r = fgetcsv($fh, 0, $delim, '"', '\\')) !== false) {
            $rows[] = $r;
        }
        fclose($fh);
        return $rows;
    }

    private static function parseXlsx(string $path): array
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new DomainException('Sunucuda ZipArchive eklentisi yok; dosyayı CSV olarak kaydedip yükleyin.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new DomainException('XLSX dosyası açılamadı.');
        }
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sx = @simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);
            foreach ($sx?->si ?? [] as $si) {
                $text = '';
                if (isset($si->t)) {
                    $text = (string) $si->t;
                }
                foreach ($si->r ?? [] as $run) {
                    $text .= (string) $run->t;
                }
                $shared[] = $text;
            }
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheetXml === false) {
            throw new DomainException('XLSX dosyasında ilk sayfa bulunamadı.');
        }
        $sx = @simplexml_load_string($sheetXml, \SimpleXMLElement::class, LIBXML_NONET);
        if (!$sx) {
            throw new DomainException('XLSX dosyası okunamadı.');
        }
        $rows = [];
        foreach ($sx->sheetData->row ?? [] as $row) {
            $r = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $colLetters = preg_replace('/\d+/', '', $ref) ?? 'A';
                $idx = 0;
                foreach (str_split($colLetters) as $ch) {
                    $idx = $idx * 26 + (ord($ch) - 64);
                }
                $idx--;
                $type = (string) $c['t'];
                $val = match ($type) {
                    's' => $shared[(int) $c->v] ?? '',
                    'inlineStr' => (string) ($c->is->t ?? ''),
                    default => (string) $c->v,
                };
                $r[$idx] = $val;
            }
            if ($r) {
                $max = max(array_keys($r));
                $full = [];
                for ($i = 0; $i <= $max; $i++) {
                    $full[] = $r[$i] ?? '';
                }
                $rows[] = $full;
            }
        }
        return $rows;
    }

    /** Başlık adlarından otomatik sütun eşleştirme önerisi. */
    public static function guessMapping(array $headers): array
    {
        $aliases = [
            'first_name' => ['ad', 'adı', 'isim', 'first name', 'firstname'],
            'last_name' => ['soyad', 'soyadı', 'last name', 'lastname', 'surname'],
            'email' => ['e-posta', 'eposta', 'email', 'e-mail', 'mail'],
            'phone' => ['telefon', 'tel', 'gsm', 'cep', 'phone'],
            'department' => ['departman', 'birim', 'müdürlük', 'department'],
            'institution' => ['kurum', 'kurum adı', 'institution'],
            'membership_type' => ['üyelik tipi', 'uyelik tipi', 'tip'],
        ];
        $map = [];
        foreach ($headers as $i => $h) {
            $n = mb_strtolower(trim($h));
            foreach ($aliases as $field => $list) {
                if (in_array($n, $list, true) && !in_array($field, $map, true)) {
                    $map[$i] = $field;
                }
            }
        }
        return $map;
    }

    /**
     * @param array<int,string> $mapping sütun indeksi → alan
     * @return array{valid:array, errors:array}
     */
    public function validate(array $rows, array $mapping, ?int $defaultInstitutionId): array
    {
        $fields = array_flip(array_filter($mapping));
        foreach (['first_name', 'last_name', 'email'] as $req) {
            if (!isset($fields[$req])) {
                throw new DomainException('“' . self::FIELDS[$req] . '” alanı bir sütunla eşleştirilmelidir.');
            }
        }
        $institutions = [];
        foreach ($this->db->fetchAll('SELECT id, name FROM institutions WHERE is_active = 1') as $i) {
            $institutions[mb_strtolower(trim($i['name']))] = (int) $i['id'];
        }
        $emails = array_map(static fn ($r) => mb_strtolower(trim((string) ($r[$fields['email']] ?? ''))), $rows);
        $existing = [];
        foreach (array_chunk(array_filter(array_unique($emails)), 500) as $chunk) {
            [$in, $p] = Database::in($chunk, 'e');
            foreach ($this->db->column("SELECT email FROM users WHERE email IN $in", $p) as $e) {
                $existing[$e] = true;
            }
        }
        $seen = [];
        $valid = [];
        $errors = [];
        foreach ($rows as $i => $r) {
            $line = $i + 2;
            $get = static fn (string $f) => isset($fields[$f]) ? trim((string) ($r[$fields[$f]] ?? '')) : '';
            $email = mb_strtolower($get('email'));
            $rowErr = [];
            if (mb_strlen($get('first_name')) < 2) {
                $rowErr[] = 'Ad eksik';
            }
            if (mb_strlen($get('last_name')) < 2) {
                $rowErr[] = 'Soyad eksik';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErr[] = 'E-posta geçersiz';
            } elseif (isset($existing[$email])) {
                $rowErr[] = 'E-posta sistemde kayıtlı';
            } elseif (isset($seen[$email])) {
                $rowErr[] = 'Dosyada mükerrer e-posta (satır ' . $seen[$email] . ')';
            }
            $instId = $defaultInstitutionId;
            if ($get('institution') !== '') {
                $instId = $institutions[mb_strtolower($get('institution'))] ?? null;
                if ($instId === null) {
                    $rowErr[] = 'Kurum bulunamadı: ' . $get('institution');
                }
            }
            if ($instId === null) {
                $rowErr[] = 'Kurum belirtilmedi';
            }
            $phone = $get('phone');
            if ($phone !== '' && strlen(preg_replace('/\D+/', '', $phone) ?? '') < 10) {
                $rowErr[] = 'Telefon geçersiz';
            }
            if ($rowErr) {
                $errors[] = ['line' => $line, 'email' => $email, 'errors' => $rowErr];
            } else {
                $valid[] = [
                    'first_name' => mb_substr($get('first_name'), 0, 80), 'last_name' => mb_substr($get('last_name'), 0, 80),
                    'email' => $email, 'phone' => $phone !== '' ? mb_substr($phone, 0, 30) : null,
                    'department' => $get('department') !== '' ? mb_substr($get('department'), 0, 150) : null,
                    'institution_id' => $instId, 'membership_type' => in_array($get('membership_type'), ['personel', 'uye', 'yonetici', 'misafir_kurum'], true) ? $get('membership_type') : 'personel',
                ];
            }
            if ($email !== '' && !isset($seen[$email])) {
                $seen[$email] = $line;
            }
        }
        return ['valid' => $valid, 'errors' => $errors];
    }

    /** @return array{created:int, links:array} */
    public function commit(array $valid, int $adminId, bool $sendInvites): array
    {
        $roleId = (int) $this->db->value("SELECT id FROM roles WHERE slug = 'member'");
        $created = [];
        $this->db->transaction(function (Database $db) use ($valid, $adminId, $roleId, &$created): void {
            foreach ($valid as $v) {
                if ($db->value('SELECT 1 FROM users WHERE email = ?', [$v['email']])) {
                    continue;
                }
                $id = $db->insert('users', $v + ['role_id' => $roleId, 'status' => 'active', 'created_by' => $adminId, 'password_changed_at' => date('Y-m-d H:i:s')]);
                $created[] = ['id' => $id, 'email' => $v['email'], 'first_name' => $v['first_name']];
            }
        });
        $links = [];
        if ($sendInvites) {
            foreach ($created as $u) {
                $r = AuthService::sendPasswordLink($u, 'invite');
                if (!$r['sent']) {
                    $links[] = ['email' => $u['email'], 'link' => $r['link']];
                }
            }
        }
        AuditService::log('users.import', 'user', null, null, ['created' => count($created)]);
        return ['created' => count($created), 'links' => $links];
    }
}
