<?php

declare(strict_types=1);

namespace App\Core;

class Money
{
    public static function toPaisa(string|int|float $amount): int
    {
        $normalized = number_format((float) $amount, 2, '.', '');
        [$whole, $fraction] = explode('.', $normalized);
        $sign = str_starts_with($normalized, '-') ? -1 : 1;
        return $sign * ((int) ltrim($whole, '-') * 100 + (int) $fraction);
    }

    public static function format(int $paisa): string
    {
        $sign = $paisa < 0 ? '-' : '';
        $paisa = abs($paisa);
        return $sign . intdiv($paisa, 100) . '.' . str_pad((string) ($paisa % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function normalize(string|int|float $amount): string
    {
        return self::format(self::toPaisa($amount));
    }

    public static function add(string|int|float ...$amounts): string
    {
        $sum = 0;
        foreach ($amounts as $amount) {
            $sum += self::toPaisa($amount);
        }
        return self::format($sum);
    }

    public static function sub(string|int|float $left, string|int|float $right): string
    {
        return self::format(self::toPaisa($left) - self::toPaisa($right));
    }

    public static function mul(string|int|float $unit, int $quantity): string
    {
        return self::format(self::toPaisa($unit) * $quantity);
    }

    public static function percent(string|int|float $amount, string|int|float $percent): string
    {
        $discount = (int) round(self::toPaisa($amount) * ((float) $percent) / 100);
        return self::format($discount);
    }

    public static function comp(string|int|float $left, string|int|float $right): int
    {
        return self::toPaisa($left) <=> self::toPaisa($right);
    }
}
