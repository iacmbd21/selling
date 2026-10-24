<?php

declare(strict_types=1);

namespace App\Core;

class Access
{
    public static function canViewOrder(array $order): bool
    {
        if (Gate::allows(Auth::role(), 'orders.view')) {
            return true;
        }
        if (Auth::id() !== null && (int) ($order['user_id'] ?? 0) === Auth::id()) {
            return true;
        }
        return Session::hasOrder((string) $order['order_no']);
    }
}
