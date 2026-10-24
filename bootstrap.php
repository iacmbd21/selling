<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = BASE_PATH . '/app/' . $relative . '.php';
    if (is_file($file)) {
        require $file;
    }
});

load_env(BASE_PATH . '/.env');
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Dhaka') ?: 'Asia/Dhaka');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $value = trim($value, "\"'");
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env(string $key, ?string $default = null): ?string
{
    if (array_key_exists($key, $_ENV)) {
        return (string) $_ENV[$key];
    }
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return (string) $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_href(): string
{
    $configured = env('APP_URL', '');
    if ($configured) {
        $path = parse_url($configured, PHP_URL_PATH) ?: '';
        return rtrim((string) $path, '/');
    }
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return rtrim($script, '/');
}

function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    if ($path === '/') {
        return base_href() . '/';
    }
    return base_href() . $path;
}

function taka(mixed $amount): string
{
    return '৳' . number_format((float) $amount, 2);
}

function product_units(): array
{
    return [
        'pc' => 'Piece (pc)',
        'kg' => 'Kilogram (kg)',
        'g' => 'Gram (g)',
        'l' => 'Litre (l)',
        'ml' => 'Millilitre (ml)',
        'dozen' => 'Dozen',
    ];
}

function unit_code(?string $unit): string
{
    $unit = strtolower(trim((string) $unit));
    return array_key_exists($unit, product_units()) ? $unit : 'pc';
}

function qty_text(mixed $quantity, ?string $unit): string
{
    return (int) $quantity . ' ' . unit_code($unit);
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(\App\Core\Csrf::token()) . '">';
}

function str_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function redirect(string $path): never
{
    \App\Core\Response::redirect(url($path));
}

function product_image_url(?string $path): ?string
{
    if ($path === null || !preg_match('#^uploads/products/[a-f0-9]{32}\.(jpg|png|webp|gif)$#', $path)) {
        return null;
    }
    if (!is_file(BASE_PATH . '/public/' . $path)) {
        return null;
    }
    return url('/' . $path);
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'item';
}
