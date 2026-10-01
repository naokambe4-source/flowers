<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Services\ImportService;

/** CSV/XLSX üye aktarımı: yükle → sütun eşleştir → önizleme + hata raporu → kaydet. */
final class ImportController extends AdminController
{
    private function tmpPath(string $token): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            throw new DomainException('Aktarım oturumu geçersiz. Dosyayı tekrar yükleyin.');
        }
        return APP_ROOT . '/storage/tmp/import_' . $token . '.json';
    }

    public function show(): Response
    {
        return $this->admin('import', ['title' => 'Üye aktarımı', 'stage' => 'upload', 'institutions' => options($this->db()->fetchAll('SELECT id, name FROM institutions WHERE is_active = 1 ORDER BY name'))]);
    }

    public function upload(): Response
    {
        $f = $this->request->file('file');
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
            throw new DomainException('Dosya seçin.');
        }
        if ($f['size'] > 5 * 1024 * 1024) {
            throw new DomainException('Dosya en fazla 5 MB olabilir.');
        }
        $parsed = ImportService::parse($f['tmp_name'], (string) $f['name']);
        $token = bin2hex(random_bytes(16));
        file_put_contents($this->tmpPath($token), json_encode($parsed, JSON_UNESCAPED_UNICODE));
        Session::put('import_token', $token);
        return $this->admin('import', [
            'title' => 'Üye aktarımı — sütun eşleştirme', 'stage' => 'map', 'token' => $token, 'headers' => $parsed['headers'],
            'sample' => array_slice($parsed['rows'], 0, 5), 'total' => count($parsed['rows']), 'mapping' => ImportService::guessMapping($parsed['headers']),
            'institutions' => options($this->db()->fetchAll('SELECT id, name FROM institutions WHERE is_active = 1 ORDER BY name')),
            'defaultInst' => $this->request->int('institution_id') ?: null,
        ]);
    }

    private function load(string $token): array
    {
        if (Session::get('import_token') !== $token || !is_file($this->tmpPath($token))) {
            throw new DomainException('Aktarım oturumu bulunamadı veya süresi doldu. Dosyayı tekrar yükleyin.');
        }
        return json_decode((string) file_get_contents($this->tmpPath($token)), true) ?: [];
    }

    private function validated(string $token): array
    {
        $parsed = $this->load($token);
        $mapping = [];
        foreach ($this->request->arr('map') as $col => $field) {
            if ($field !== '' && isset(ImportService::FIELDS[$field])) {
                $mapping[(int) $col] = $field;
            }
        }
        if (count($mapping) !== count(array_unique($mapping))) {
            throw new DomainException('Aynı alan birden fazla sütunla eşleştirilemez.');
        }
        $inst = $this->request->int('default_institution') ?: null;
        return [(new ImportService($this->db()))->validate($parsed['rows'], $mapping, $inst), $mapping, $inst, $parsed];
    }

    public function preview(): Response
    {
        $token = (string) ($this->request->post['token'] ?? '');
        [$res, $mapping, $inst, $parsed] = $this->validated($token);
        return $this->admin('import', [
            'title' => 'Üye aktarımı — önizleme', 'stage' => 'preview', 'token' => $token, 'valid' => $res['valid'], 'errors' => $res['errors'],
            'mapping' => $mapping, 'defaultInst' => $inst, 'headers' => $parsed['headers'],
            'instNames' => options($this->db()->fetchAll('SELECT id, name FROM institutions')),
        ]);
    }

    public function commit(): Response
    {
        $token = (string) ($this->request->post['token'] ?? '');
        [$res] = $this->validated($token);
        if (!$res['valid']) {
            throw new DomainException('Aktarılacak geçerli satır yok.');
        }
        $out = (new ImportService($this->db()))->commit($res['valid'], $this->uid(), $this->request->bool('send_invites'));
        @unlink($this->tmpPath($token));
        Session::forget('import_token');
        return $this->admin('import', ['title' => 'Üye aktarımı tamamlandı', 'stage' => 'done', 'created' => $out['created'], 'links' => $out['links'], 'skipped' => count($res['errors'])]);
    }
}
