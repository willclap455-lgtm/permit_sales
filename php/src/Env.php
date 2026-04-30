<?php

declare(strict_types=1);

namespace PermitSales;

final class Env
{
    private static bool $loaded = false;

    /**
     * Paths the loader actually tried, with the outcome for each. Useful for
     * surfacing diagnostics in dev mode when something is missing or
     * malformed.
     *
     * @var array<int, array{path:string, status:string, detail?:string}>
     */
    private static array $loadAttempts = [];

    /**
     * Load environment variables from one or more candidate paths.
     *
     * The first candidate that exists is read. Reading a file that exists
     * but is unreadable, contains a UTF-8 BOM, has CRLF line endings, has
     * inline `#` comments, or has mismatched quoting is handled gracefully
     * — those should not silently produce an empty environment, which is
     * what made this hard to diagnose on IIS.
     *
     * @param string|array<int,string> $paths
     */
    public static function load(string|array $paths): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $candidates = is_array($paths) ? $paths : [$paths];

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                self::$loadAttempts[] = [
                    'path'   => $path,
                    'status' => 'missing',
                ];
                continue;
            }
            if (!is_readable($path)) {
                self::$loadAttempts[] = [
                    'path'   => $path,
                    'status' => 'unreadable',
                    'detail' => 'file exists but the PHP process cannot read it (check NTFS ACLs / IIS app pool identity)',
                ];
                continue;
            }

            $contents = @file_get_contents($path);
            if ($contents === false) {
                self::$loadAttempts[] = [
                    'path'   => $path,
                    'status' => 'unreadable',
                    'detail' => 'file_get_contents() failed',
                ];
                continue;
            }

            // Strip a UTF-8 BOM that Windows editors (Notepad, some PowerShell
            // redirects) like to prepend. Without this, the very first key in
            // the file ends up named "\xEF\xBB\xBFDATABASE_URL" and never
            // matches getenv('DATABASE_URL').
            if (str_starts_with($contents, "\xEF\xBB\xBF")) {
                $contents = substr($contents, 3);
            }

            // Normalize CRLF / CR line endings before splitting.
            $contents = str_replace(["\r\n", "\r"], "\n", $contents);

            $count = self::parseInto($contents);

            self::$loadAttempts[] = [
                'path'   => $path,
                'status' => 'loaded',
                'detail' => $count . ' variable(s)',
            ];
            return;
        }
    }

    private static function parseInto(string $contents): int
    {
        $count = 0;
        foreach (explode("\n", $contents) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            // Allow a leading "export " for compatibility with shells.
            if (str_starts_with($line, 'export ')) {
                $line = substr($line, 7);
                $line = ltrim($line);
            }

            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            $key = trim(substr($line, 0, $eq));
            $value = substr($line, $eq + 1);

            if ($key === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }

            $value = self::parseValue($value);

            // Don't clobber values supplied by the real environment / IIS
            // FastCGI <environmentVariables>. That intentionally lets
            // operators override .env from the server config.
            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
            $count++;
        }
        return $count;
    }

    private static function parseValue(string $value): string
    {
        $value = ltrim($value);

        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $quote = $value[0];
            $end = strpos($value, $quote, 1);
            if ($end !== false) {
                $inner = substr($value, 1, $end - 1);
                if ($quote === '"') {
                    $inner = strtr($inner, [
                        '\\n'  => "\n",
                        '\\r'  => "\r",
                        '\\t'  => "\t",
                        '\\"'  => '"',
                        '\\\\' => '\\',
                    ]);
                }
                return $inner;
            }
            // Mismatched quotes — fall through and treat the rest as a raw
            // value rather than silently dropping the leading quote.
        }

        // Strip an unquoted trailing inline comment (`KEY=value # note`).
        // We only strip if the `#` is preceded by whitespace, so `#`
        // characters inside passwords (`p@ss#word`) are preserved as long
        // as they are not space-separated.
        if (preg_match('/^(.*?)\s+#.*$/', $value, $m)) {
            $value = $m[1];
        }

        return rtrim($value);
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $val = getenv($key);
        if ($val === false || $val === '') {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? null;
            if (!is_string($val) || $val === '') {
                return $default;
            }
        }
        return $val;
    }

    public static function require(string $key): string
    {
        $val = self::get($key);
        if ($val === null) {
            throw new \RuntimeException(
                "Missing required environment variable: {$key}.\n"
                . self::diagnostics()
            );
        }
        return $val;
    }

    /**
     * @return array<int, array{path:string, status:string, detail?:string}>
     */
    public static function loadAttempts(): array
    {
        return self::$loadAttempts;
    }

    public static function diagnostics(): string
    {
        if (self::$loadAttempts === []) {
            return 'Env::load() was never called.';
        }
        $lines = ['.env loader tried the following paths:'];
        foreach (self::$loadAttempts as $attempt) {
            $line = '  - ' . $attempt['path'] . ' [' . $attempt['status'] . ']';
            if (!empty($attempt['detail'])) {
                $line .= ' (' . $attempt['detail'] . ')';
            }
            $lines[] = $line;
        }
        return implode("\n", $lines);
    }
}
