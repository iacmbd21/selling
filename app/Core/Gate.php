<?php

declare(strict_types=1);

namespace App\Core;

class Gate
{
    private const MAP = [
        'super_admin' => ['*'],
        'admin' => [
            'dashboard.view',
            'products.manage',
            'orders.view',
            'orders.manage',
            'orders.return',
            'payments.collect',
            'payments.submit',
            'payments.verify',
            'payments.refund',
            'coupons.manage',
            'inventory.manage',
            'invoices.view',
            'reports.view',
            'users.manage',
            'backup.manage',
            'customers.view',
            'reviews.moderate',
            'shipping.manage',
            'banners.manage',
            'audit.view',
            'settings.manage',
        ],
        'manager' => [
            'dashboard.view',
            'products.manage',
            'orders.view',
            'orders.manage',
            'orders.return',
            'payments.collect',
            'payments.submit',
            'payments.verify',
            'payments.refund',
            'coupons.manage',
            'inventory.manage',
            'invoices.view',
            'reports.view',
            'customers.view',
            'reviews.moderate',
            'shipping.manage',
            'banners.manage',
        ],
        'order_manager' => [
            'dashboard.view',
            'orders.view',
            'orders.manage',
            'orders.return',
            'invoices.view',
            'customers.view',
        ],
        'inventory_manager' => [
            'dashboard.view',
            'products.manage',
            'inventory.manage',
            'reports.view',
        ],
        'accountant' => [
            'dashboard.view',
            'payments.collect',
            'payments.submit',
            'payments.verify',
            'payments.refund',
            'reports.view',
            'invoices.view',
            'audit.view',
        ],
        'staff' => [
            'dashboard.view',
            'orders.view',
            'orders.manage',
            'payments.collect',
            'payments.submit',
            'invoices.view',
            'customers.view',
        ],
        'customer' => [
            'account.view',
        ],
    ];

    public static function allows(?string $role, string $permission): bool
    {
        $permissions = self::MAP[$role] ?? [];
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
