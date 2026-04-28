<?php

declare(strict_types=1);

namespace PermitSales;

final class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $eq = strpos($line, '=');
            if ($eq === false) {
                continue;
            }
            $key = trim(substr($line, 0, $eq));
            $value = trim(substr($line, $eq + 1));
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $quote = $value[0];
                if (str_ends_with($value, $quote)) {
                    $value = substr($value, 1, -1);
                }
            }
            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $val = getenv($key);
        if ($val === false || $val === '') {
            return $default;
        }
        return $val;
    }

    public static function require(string $key): string
    {
        $val = self::get($key);
        if ($val === null) {
            throw new \RuntimeException("Missing required environment variable: {$key}");
        }
        return $val;
    }
}
