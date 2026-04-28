<?php

declare(strict_types=1);

namespace PermitSales;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $url = Env::require('DATABASE_URL');
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme']) || !str_starts_with($parts['scheme'], 'postgres')) {
            throw new \RuntimeException('DATABASE_URL must be a postgres://… URL');
        }

        $host = $parts['host'] ?? 'localhost';
        $port = (string) ($parts['port'] ?? 5432);
        $user = $parts['user'] ?? 'postgres';
        $pass = isset($parts['pass']) ? urldecode($parts['pass']) : '';
        $db = ltrim($parts['path'] ?? '/postgres', '/');

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db);

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$pdo;
    }

    /**
     * @param array<string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function one(string $sql, array $params = []): ?array
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @param array<string,mixed> $params
     */
    public static function exec(string $sql, array $params = []): int
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
