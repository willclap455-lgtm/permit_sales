<?php

declare(strict_types=1);

namespace PermitSales;

final class Request
{
    public static function input(string $key, ?string $default = null): ?string
    {
        $v = $_POST[$key] ?? $_GET[$key] ?? null;
        if ($v === null) {
            return $default;
        }
        if (is_array($v)) {
            return $default;
        }
        return trim((string) $v);
    }

    public static function required(string $key): string
    {
        $v = self::input($key);
        if ($v === null || $v === '') {
            throw new ValidationException("Missing required field: {$key}");
        }
        return $v;
    }

    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public static function checkCsrf(): void
    {
        if (!self::isPost()) {
            return;
        }
        $token = self::input('_csrf');
        if (!Session::verifyCsrf($token)) {
            http_response_code(419);
            echo 'CSRF token mismatch';
            exit;
        }
    }
}

final class ValidationException extends \RuntimeException
{
}
