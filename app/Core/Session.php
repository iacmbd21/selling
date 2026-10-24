<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function rememberOrder(string $orderNo): void
    {
        $recent = $_SESSION['recent_orders'] ?? [];
        $recent[] = $orderNo;
        $_SESSION['recent_orders'] = array_slice(array_values(array_unique($recent)), -10);
    }

    public static function hasOrder(string $orderNo): bool
    {
        return in_array($orderNo, $_SESSION['recent_orders'] ?? [], true);
    }
}
