<?php

declare(strict_types=1);

namespace PermitSales\Controllers;

use PermitSales\Auth;
use PermitSales\Database;
use PermitSales\Request;
use PermitSales\Session;
use PermitSales\ValidationException;
use PermitSales\View;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: /dashboard');
            return;
        }
        View::render('auth/login', ['title' => 'Log in — PermitSales']);
    }

    public function login(): void
    {
        Request::checkCsrf();
        try {
            $email = strtolower(Request::required('email'));
            $password = Request::required('password');
        } catch (ValidationException $e) {
            Session::flash('error', $e->getMessage());
            header('Location: /login');
            return;
        }

        $user = Database::one(
            'SELECT id, password_hash, is_active, deleted_at
               FROM users WHERE email = :email',
            ['email' => $email]
        );

        if (!$user || $user['deleted_at'] !== null || !$user['is_active']
            || !password_verify($password, $user['password_hash'])) {
            Session::flash('error', 'Invalid email or password.');
            header('Location: /login');
            return;
        }

        Auth::login((string) $user['id']);
        Session::flash('success', 'Welcome back.');
        header('Location: /dashboard');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            header('Location: /dashboard');
            return;
        }
        View::render('auth/register', ['title' => 'Create account — PermitSales']);
    }

    public function register(): void
    {
        Request::checkCsrf();
        try {
            $name = Request::required('full_name');
            $email = strtolower(Request::required('email'));
            $phone = Request::input('phone');
            $password = Request::required('password');
            $confirm = Request::required('password_confirm');
        } catch (ValidationException $e) {
            Session::flash('error', $e->getMessage());
            header('Location: /register');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Please enter a valid email address.');
            header('Location: /register');
            return;
        }
        if (strlen($password) < 8) {
            Session::flash('error', 'Password must be at least 8 characters.');
            header('Location: /register');
            return;
        }
        if (!hash_equals($password, $confirm)) {
            Session::flash('error', 'Passwords do not match.');
            header('Location: /register');
            return;
        }

        $existing = Database::one('SELECT id FROM users WHERE email = :e', ['e' => $email]);
        if ($existing !== null) {
            Session::flash('error', 'An account with that email already exists.');
            header('Location: /register');
            return;
        }

        $role = Database::one("SELECT id FROM roles WHERE name = 'user'");
        if ($role === null) {
            Session::flash('error', 'Role table not seeded; run init_schema.sql.');
            header('Location: /register');
            return;
        }

        $row = Database::one(
            'INSERT INTO users (role_id, email, password_hash, full_name, phone)
             VALUES (:role, :email, :pw, :name, :phone)
             RETURNING id',
            [
                'role'  => $role['id'],
                'email' => $email,
                'pw'    => password_hash($password, PASSWORD_BCRYPT),
                'name'  => $name,
                'phone' => $phone ?: null,
            ]
        );

        Auth::login((string) $row['id']);
        Session::flash('success', 'Account created. Add a vehicle to get started.');
        header('Location: /dashboard');
    }

    public function logout(): void
    {
        Request::checkCsrf();
        Auth::logout();
        header('Location: /');
    }
}
