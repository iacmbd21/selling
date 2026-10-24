<?php

declare(strict_types=1);

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\BackupController;
use App\Controllers\Admin\CouponController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\InventoryController;
use App\Controllers\Admin\OrderController;
use App\Controllers\Admin\PaymentController;
use App\Controllers\Admin\ProductController;
use App\Controllers\Admin\AuditController;
use App\Controllers\Admin\CustomerController;
use App\Controllers\Admin\ReportController;
use App\Controllers\Admin\ReviewController as AdminReviewController;
use App\Controllers\Admin\ShippingController;
use App\Controllers\Admin\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

$admin = AuthMiddleware::class . ':admin';
$permit = static fn (string $permission): array => [$admin, RoleMiddleware::class . ':' . $permission];

$router->get('/admin/login', [AuthController::class, 'show']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout'], [$admin]);

$router->get('/admin', [DashboardController::class, 'index'], $permit('dashboard.view'));
$router->get('/admin/products', [ProductController::class, 'index'], $permit('products.manage'));
$router->post('/admin/products', [ProductController::class, 'store'], $permit('products.manage'));
$router->get('/admin/products/{id}/edit', [ProductController::class, 'edit'], $permit('products.manage'));
$router->post('/admin/products/{id}/delete', [ProductController::class, 'destroy'], $permit('products.manage'));
$router->post('/admin/products/{id}', [ProductController::class, 'update'], $permit('products.manage'));

$router->get('/admin/orders', [OrderController::class, 'index'], $permit('orders.view'));
$router->get('/admin/orders/{id}', [OrderController::class, 'show'], $permit('orders.view'));
$router->post('/admin/orders/{id}/confirm', [OrderController::class, 'confirm'], $permit('orders.manage'));
$router->post('/admin/orders/{id}/process', [OrderController::class, 'process'], $permit('orders.manage'));
$router->post('/admin/orders/{id}/ship', [OrderController::class, 'ship'], $permit('orders.manage'));
$router->post('/admin/orders/{id}/deliver', [OrderController::class, 'deliver'], $permit('orders.manage'));
$router->post('/admin/orders/{id}/cancel', [OrderController::class, 'cancel'], $permit('orders.manage'));
$router->post('/admin/orders/{id}/return', [OrderController::class, 'returnOrder'], $permit('orders.return'));

$router->get('/admin/payments', [PaymentController::class, 'index'], $permit('payments.submit'));
$router->post('/admin/orders/{orderId}/collect', [PaymentController::class, 'collect'], $permit('payments.collect'));
$router->post('/admin/orders/{orderId}/payment', [PaymentController::class, 'submit'], $permit('payments.submit'));
$router->post('/admin/payments/{paymentId}/verify', [PaymentController::class, 'verify'], $permit('payments.verify'));

$router->get('/admin/coupons', [CouponController::class, 'index'], $permit('coupons.manage'));
$router->post('/admin/coupons', [CouponController::class, 'store'], $permit('coupons.manage'));
$router->get('/admin/inventory', [InventoryController::class, 'index'], $permit('inventory.manage'));
$router->post('/admin/inventory', [InventoryController::class, 'adjust'], $permit('inventory.manage'));
$router->get('/admin/reports', [ReportController::class, 'index'], $permit('reports.view'));
$router->get('/admin/customers', [CustomerController::class, 'index'], $permit('customers.view'));
$router->get('/admin/reviews', [AdminReviewController::class, 'index'], $permit('reviews.moderate'));
$router->post('/admin/reviews/{id}', [AdminReviewController::class, 'moderate'], $permit('reviews.moderate'));
$router->get('/admin/shipping', [ShippingController::class, 'index'], $permit('shipping.manage'));
$router->get('/admin/audit', [AuditController::class, 'index'], $permit('audit.view'));
$router->get('/admin/users', [UserController::class, 'index'], $permit('users.manage'));
$router->post('/admin/users', [UserController::class, 'store'], $permit('users.manage'));
$router->get('/admin/backup', [BackupController::class, 'index'], $permit('backup.manage'));
$router->post('/admin/backup', [BackupController::class, 'create'], $permit('backup.manage'));
$router->post('/admin/backup/restore', [BackupController::class, 'restore'], $permit('backup.manage'));
