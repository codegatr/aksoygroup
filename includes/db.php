<?php
/**
 * AKSOY GROUP — Veritabanı Wrapper
 * PDO singleton; prepared statement kısayolları.
 * @package AksoyHolding\Core
 */

declare(strict_types=1);

final class DB
{
    private static ?PDO $pdo = null;

    /** @throws RuntimeException */
    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST, DB_NAME, DB_CHARSET
            );
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+03:00'",
            ]);
            return self::$pdo;
        } catch (PDOException $e) {
            error_log('[DB] ' . $e->getMessage());
            if (defined('AG_DEBUG') && AG_DEBUG) {
                throw new RuntimeException('DB bağlantı hatası: ' . $e->getMessage());
            }
            throw new RuntimeException('Veritabanına şu anda ulaşılamıyor.');
        }
    }

    /** Tek satır döndürür; yoksa null. */
    public static function row(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Çoklu satır. */
    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Tek skaler değer (ilk sütun). */
    public static function scalar(string $sql, array $params = []): mixed
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();
        return $val === false ? null : $val;
    }

    /** INSERT / UPDATE / DELETE; etkilenen satır sayısı döner. */
    public static function exec(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** INSERT yapar, lastInsertId döner. */
    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $place = array_map(fn($c) => ':' . $c, $cols);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`,`', $cols),
            implode(',', $place)
        );
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($data);
        return (int)self::pdo()->lastInsertId();
    }

    /** UPDATE WHERE id = ? kısayolu; etkilenen satır sayısı. */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = [];
        foreach (array_keys($data) as $col) {
            $set[] = "`$col` = :_$col";
        }
        $params = [];
        foreach ($data as $k => $v) {
            $params['_' . $k] = $v;
        }
        $params = array_merge($params, $whereParams);
        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $set), $where);
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Transaction wrapper. */
    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** Tablo var mı? */
    public static function tableExists(string $table): bool
    {
        $sql = "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1";
        return (bool)self::scalar($sql, [$table]);
    }

    /** Tablodaki sütun var mı? (idempotent migration için). */
    public static function columnExists(string $table, string $column): bool
    {
        $sql = "SELECT 1 FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1";
        return (bool)self::scalar($sql, [$table, $column]);
    }
}
