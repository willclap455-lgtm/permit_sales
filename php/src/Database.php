<?php

declare(strict_types=1);

namespace PermitSales;

use PDO;

/**
 * Wrapper around a string of raw bytes that should be sent to PostgreSQL as
 * a binary BYTEA, not a UTF-8 TEXT value.
 *
 * PDO's default `execute($params)` path binds every value as `PARAM_STR`,
 * which makes libpq encode the value with the connection's client_encoding.
 * For random bytes (AES-GCM ciphertext, IVs, auth tags) that nearly always
 * fails with `invalid byte sequence for encoding "UTF8"`. Wrapping the value
 * in `BinaryParam` tells `Database::exec()` / `::all()` / `::one()` to bind
 * it as `PARAM_LOB` instead, which uses the binary protocol.
 */
final class BinaryParam
{
    public function __construct(public readonly string $bytes)
    {
    }
}

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $url = trim(Env::require('DATABASE_URL'));
        $parts = parse_url($url);
        if ($parts === false || !isset($parts['scheme']) || !str_starts_with($parts['scheme'], 'postgres')) {
            throw new \RuntimeException(
                'DATABASE_URL must be a postgres://… URL. Got: '
                . self::redactUrl($url) . "\n"
                . self::parseHint($url) . "\n\n"
                . Env::diagnostics()
            );
        }

        $host = $parts['host'] ?? 'localhost';
        $port = (string) ($parts['port'] ?? 5432);
        $user = isset($parts['user']) ? urldecode($parts['user']) : 'postgres';
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
        $stmt = self::prepareAndBind($sql, $params);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function one(string $sql, array $params = []): ?array
    {
        $stmt = self::prepareAndBind($sql, $params);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @param array<string,mixed> $params
     */
    public static function exec(string $sql, array $params = []): int
    {
        $stmt = self::prepareAndBind($sql, $params);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Prepare a statement and bind each parameter with the right PDO type.
     *
     * Default PDO behaviour is to treat every `execute($params)` value as
     * `PARAM_STR`, which makes Postgres validate the bytes as UTF-8 even
     * when the destination column is BYTEA. Random binary (e.g. AES-GCM
     * ciphertext) then fails with `invalid byte sequence for encoding
     * "UTF8"`. Wrapping such values in `BinaryParam` routes them through
     * `PARAM_LOB` so libpq sends them on the binary path.
     *
     * @param array<string,mixed> $params
     */
    private static function prepareAndBind(string $sql, array $params): \PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        foreach ($params as $name => $value) {
            $key = is_int($name) ? $name + 1 : ':' . ltrim((string) $name, ':');
            if ($value instanceof BinaryParam) {
                $stmt->bindValue($key, $value->bytes, PDO::PARAM_LOB);
                continue;
            }
            if (is_bool($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_BOOL);
                continue;
            }
            if ($value === null) {
                $stmt->bindValue($key, null, PDO::PARAM_NULL);
                continue;
            }
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
                continue;
            }
            $stmt->bindValue($key, (string) $value, PDO::PARAM_STR);
        }
        return $stmt;
    }

    private static function parseHint(string $url): string
    {
        if ($url === '') {
            return 'Hint: DATABASE_URL is empty.';
        }
        if (!str_starts_with($url, 'postgres://') && !str_starts_with($url, 'postgresql://')) {
            return 'Hint: the value must start with `postgres://` or `postgresql://`.';
        }
        // PHP\'s parse_url() treats `#` as a URL fragment delimiter, so a
        // password containing `#` (or any other URL-reserved character)
        // will make it return false. The fix is to URL-encode it: `#` →
        // `%23`, `@` → `%40`, `/` → `%2F`, `:` → `%3A`, etc.
        if (preg_match('/:\/\/[^@\/]*[#?][^@\/]*@/', $url)) {
            return 'Hint: your username or password appears to contain `#` or `?`. '
                . 'These are URL-reserved characters and must be percent-encoded — '
                . 'e.g. replace `#` with `%23` and `?` with `%3F` in DATABASE_URL.';
        }
        return 'Hint: percent-encode any `#`, `@`, `/`, `:`, `?`, `&`, `+`, or space '
            . 'characters that appear inside the username or password.';
    }

    private static function redactUrl(string $url): string
    {
        // Mask the password in `scheme://user:pass@host…` before echoing
        // the value back in an error. Done with plain string ops rather
        // than a regex so we don't have to worry about a `#` (which is
        // legal inside a URL password) interacting with PCRE delimiters.
        $masked = $url;
        $schemeEnd = strpos($masked, '://');
        if ($schemeEnd !== false) {
            $authStart = $schemeEnd + 3;
            $atPos = strpos($masked, '@', $authStart);
            if ($atPos !== false) {
                $userInfo = substr($masked, $authStart, $atPos - $authStart);
                $colon = strpos($userInfo, ':');
                if ($colon !== false) {
                    $masked = substr($masked, 0, $authStart)
                        . substr($userInfo, 0, $colon)
                        . ':***'
                        . substr($masked, $atPos);
                }
            }
        }
        // var_export so trailing/leading whitespace, BOM bytes, or
        // unprintable characters are obvious to the operator.
        return var_export($masked, true);
    }
}
