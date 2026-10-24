<?php

use App\Core\Auth;
use App\Core\Gate;
use App\Core\Session;

$currentUser = Auth::user();
$success = Session::pull('success');
$error = Session::pull('error');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$link = static function (string $href, string $label, string $permission) use ($currentUser, $path): string {
    if (!Gate::allows($currentUser['role'] ?? null, $permission)) {
        return '';
    }
    $active = str_contains($path, $href) ? ' class="active"' : '';
    if ($href === '/admin' && $path !== rtrim(base_href() . '/admin', '/')) {
        $active = '';
    }
    return '<a' . $active . ' href="' . e(url($href)) . '">' . e($label) . '</a>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Admin') ?> · Today Sell</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/app.css')) ?>?v=<?= (int) filemtime(BASE_PATH . '/public/assets/app.css') ?>">
</head>
<body class="admin-body">
<aside class="sidebar">
    <a class="brand" href="<?= e(url('/admin')) ?>">Today Sell</a>
    <p class="who"><?= e($currentUser['name'] ?? '') ?><span><?= e($currentUser['role'] ?? '') ?></span></p>
    <?= $link('/admin', 'Dashboard', 'dashboard.view') ?>
    <?= $link('/admin/orders', 'Orders', 'orders.view') ?>
    <?= $link('/admin/payments', 'Payments', 'payments.submit') ?>
    <?= $link('/admin/products', 'Products', 'products.manage') ?>
    <?= $link('/admin/inventory', 'Inventory', 'inventory.manage') ?>
    <?= $link('/admin/coupons', 'Coupons', 'coupons.manage') ?>
    <?= $link('/admin/reports', 'Reports', 'reports.view') ?>
    <?= $link('/admin/customers', 'Customers', 'customers.view') ?>
    <?= $link('/admin/reviews', 'Reviews', 'reviews.moderate') ?>
    <?= $link('/admin/shipping', 'Shipping', 'shipping.manage') ?>
    <?= $link('/admin/audit', 'Audit log', 'audit.view') ?>
    <?= $link('/admin/users', 'Staff', 'users.manage') ?>
    <?= $link('/admin/backup', 'Backup', 'backup.manage') ?>
    <a href="<?= e(url('/')) ?>">View shop</a>
    <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?><button type="submit">Logout</button></form>
</aside>
<main class="admin-main">
    <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
    <?= $content ?>
</main>
<script src="<?= e(url('/assets/app.js')) ?>?v=<?= (int) filemtime(BASE_PATH . '/public/assets/app.js') ?>"></script>
</body>
</html>
