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
