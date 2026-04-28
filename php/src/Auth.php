<?php

declare(strict_types=1);

namespace PermitSales;

final class Auth
{
    /**
     * @return array<string,mixed>|null
     */
    public static function user(): ?array
    {
        $id = Session::get('uid');
        if (!is_string($id) || $id === '') {
            return null;
        }
        return Database::one(
            'SELECT u.id, u.email, u.full_name, u.phone, u.is_active, u.last_login_at, r.name AS role
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.id = :id AND u.deleted_at IS NULL AND u.is_active = TRUE',
            ['id' => $id]
        );
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && ($u['role'] ?? null) === 'admin';
    }

    public static function login(string $userId): void
    {
        Session::start();
        session_regenerate_id(true);
        Session::set('uid', $userId);
        Database::exec(
            'UPDATE users SET last_login_at = NOW() WHERE id = :id',
            ['id' => $userId]
        );
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function requireUser(): array
    {
        $u = self::user();
        if ($u === null) {
            header('Location: /login');
            exit;
        }
        return $u;
    }

    public static function requireAdmin(): array
    {
        $u = self::requireUser();
        if (($u['role'] ?? null) !== 'admin') {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        return $u;
    }
}
