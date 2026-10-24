<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    private static ?array $cached = null;

    public static function attempt(string $email, string $password, array $roles): bool
    {
        if ((int) ($_SESSION['login_locked_until'] ?? 0) > time()) {
            return false;
        }
        $user = (new User())->findByEmail($email);
        if (!$user || $user['status'] !== 'active') {
            self::failLogin();
            return false;
        }
        if (!in_array($user['role'], $roles, true)) {
            self::failLogin();
            return false;
        }
        if (!password_verify($password, (string) $user['password'])) {
            self::failLogin();
            return false;
        }
        unset($_SESSION['login_fails'], $_SESSION['login_locked_until']);
        self::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        self::$cached = $user;
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        self::$cached = null;
        self::regenerate();
    }

    private static function failLogin(): void
    {
        $_SESSION['login_fails'] = (int) ($_SESSION['login_fails'] ?? 0) + 1;
        if ((int) $_SESSION['login_fails'] >= 5) {
            $_SESSION['login_locked_until'] = time() + 900;
        }
    }

    private static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    public static function user(): ?array
    {
        $id = $_SESSION['user_id'] ?? null;
        if (!$id) {
            self::$cached = null;
            return null;
        }
        if (self::$cached && (int) self::$cached['id'] === (int) $id) {
            return self::$cached;
        }
        $user = (new User())->find((int) $id);
        if (!$user || $user['status'] !== 'active') {
            unset($_SESSION['user_id']);
            self::$cached = null;
            return null;
        }
        self::$cached = $user;
        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function forget(): void
    {
        self::$cached = null;
    }
}
