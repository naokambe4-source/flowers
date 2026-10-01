<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Veriyi silmeden sıralı migration çalıştırır. .sql ve .php (callable döndüren) dosyaları desteklenir.
 * Güncellemelerde yalnız henüz çalışmamış dosyalar uygulanır.
 */
final class Migrator
{
    public function __construct(private readonly Database $db, private readonly string $dir)
    {
    }

    public function ensureTable(): void
    {
        $this->db->pdo()->exec('CREATE TABLE IF NOT EXISTS migrations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL UNIQUE,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function files(): array
    {
        $files = glob($this->dir . '/*.{sql,php}', GLOB_BRACE) ?: [];
        sort($files, SORT_STRING);
        return $files;
    }

    public function applied(): array
    {
        $this->ensureTable();
        return $this->db->column('SELECT name FROM migrations ORDER BY id');
    }

    public function pending(): array
    {
        $applied = array_flip($this->applied());
        return array_values(array_filter($this->files(), static fn ($f) => !isset($applied[basename($f)])));
    }

    /** @return string[] uygulanan dosya adları */
    public function migrate(): array
    {
        $done = [];
        foreach ($this->pending() as $file) {
            $name = basename($file);
            if (str_ends_with($file, '.sql')) {
                foreach (self::splitSql((string) file_get_contents($file)) as $stmt) {
                    $this->db->pdo()->exec($stmt);
                }
            } else {
                $fn = require $file;
                if (!is_callable($fn)) {
                    throw new \RuntimeException('Geçersiz migration: ' . $name);
                }
                $this->db->transaction(static fn (Database $db) => $fn($db));
            }
            $this->db->insert('migrations', ['name' => $name]);
            $done[] = $name;
        }
        return $done;
    }

    public static function splitSql(string $sql): array
    {
        $lines = preg_split('/\R/', $sql) ?: [];
        $clean = [];
        foreach ($lines as $line) {
            $t = ltrim($line);
            if (str_starts_with($t, '--') || $t === '') {
                continue;
            }
            $clean[] = $line;
        }
        $parts = preg_split('/;\s*(?:\R|$)/', implode("\n", $clean)) ?: [];
        return array_values(array_filter(array_map('trim', $parts), static fn ($s) => $s !== ''));
    }
}
