<?php

declare(strict_types=1);

namespace PermitSales;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = (Env::get('APP_ENV', 'dev') === 'prod');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('permitsales_php');
        session_start();
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
    }

    public static function csrfToken(): string
    {
        self::start();
        return $_SESSION['_csrf'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::start();
        return is_string($token) && hash_equals($_SESSION['_csrf'] ?? '', $token);
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        self::start();
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        if ($val !== null) {
            unset($_SESSION['_flash'][$key]);
        }
        return $val;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
}
