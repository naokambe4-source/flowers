<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/** PDO sarmalayıcı: yalnız prepared statement, transaction ve basit yardımcılar. */
final class Database
{
    private PDO $pdo;
    private int $txDepth = 0;

    public function __construct(array $cfg)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $cfg['host'] ?? 'localhost',
            (int) ($cfg['port'] ?? 3306),
            $cfg['name'] ?? '',
            $cfg['charset'] ?? 'utf8mb4',
        );
        $this->pdo = new PDO($dsn, (string) ($cfg['user'] ?? ''), (string) ($cfg['pass'] ?? ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_TIMEOUT => 10,
        ]);
        $this->pdo->exec("SET time_zone = '+03:00', sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $name = is_int($key) ? $key + 1 : (str_starts_with($key, ':') ? $key : ':' . $key);
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($name, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function value(string $sql, array $params = []): mixed
    {
        $v = $this->query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public function column(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            self::ident($table),
            implode(', ', array_map([self::class, 'ident'], $cols)),
            implode(', ', array_map(static fn ($c) => ':' . $c, $cols)),
        );
        $this->query($sql, $data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        if (!$data) {
            return 0;
        }
        $set = [];
        $params = [];
        foreach ($data as $col => $val) {
            $set[] = self::ident($col) . ' = :s_' . $col;
            $params['s_' . $col] = $val;
        }
        [$whereSql, $whereParams] = $this->whereClause($where);
        $sql = 'UPDATE ' . self::ident($table) . ' SET ' . implode(', ', $set) . ' WHERE ' . $whereSql;
        return $this->query($sql, $params + $whereParams)->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        [$whereSql, $params] = $this->whereClause($where);
        return $this->query('DELETE FROM ' . self::ident($table) . ' WHERE ' . $whereSql, $params)->rowCount();
    }

    private function whereClause(array $where): array
    {
        if (!$where) {
            throw new \InvalidArgumentException('Koşulsuz güncelleme/silme engellendi.');
        }
        $parts = [];
        $params = [];
        foreach ($where as $col => $val) {
            if ($val === null) {
                $parts[] = self::ident($col) . ' IS NULL';
                continue;
            }
            $parts[] = self::ident($col) . ' = :w_' . $col;
            $params['w_' . $col] = $val;
        }
        return [implode(' AND ', $parts), $params];
    }

    public static function ident(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException('Geçersiz tanımlayıcı: ' . $name);
        }
        return '`' . $name . '`';
    }

    /** IN (...) için yer tutucu ve parametre üretir. */
    public static function in(array $values, string $prefix = 'in'): array
    {
        $values = array_values($values);
        if (!$values) {
            return ['(NULL)', []];
        }
        $ph = [];
        $params = [];
        foreach ($values as $i => $v) {
            $ph[] = ':' . $prefix . $i;
            $params[$prefix . $i] = $v;
        }
        return ['(' . implode(', ', $ph) . ')', $params];
    }

    /**
     * İç içe çağrılarda tek transaction kullanır.
     * @template T
     * @param callable(Database):T $fn
     * @return T
     */
    public function transaction(callable $fn): mixed
    {
        if ($this->txDepth === 0) {
            $this->pdo->beginTransaction();
        }
        $this->txDepth++;
        try {
            $result = $fn($this);
            $this->txDepth--;
            if ($this->txDepth === 0) {
                $this->pdo->commit();
            }
            return $result;
        } catch (\Throwable $e) {
            $this->txDepth--;
            if ($this->txDepth === 0 && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    public function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
