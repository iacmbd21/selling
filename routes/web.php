<?php

declare(strict_types=1);

use App\Controllers\Admin\InvoiceController;
use App\Controllers\Customer\AccountController;
use App\Controllers\Store\AuthController;
use App\Controllers\Store\CartController;
use App\Controllers\Store\CatalogController;
use App\Controllers\Store\CheckoutController;
use App\Controllers\Store\PageController;
use App\Controllers\Store\ReviewController;
use App\Middleware\AuthMiddleware;

$router->get('/', [CatalogController::class, 'home']);
$router->get('/products', [CatalogController::class, 'index']);
$router->get('/shop', [CatalogController::class, 'index']);
$router->get('/search', [CatalogController::class, 'index']);
$router->get('/category/{slug}', [CatalogController::class, 'category']);
$router->get('/products/{slug}', [CatalogController::class, 'show']);
$router->get('/product/{slug}', [CatalogController::class, 'show']);
$router->post('/products/{slug}/reviews', [ReviewController::class, 'store'], [AuthMiddleware::class . ':web']);
$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart', [CartController::class, 'add']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->get('/order', [CheckoutController::class, 'create']);
$router->get('/checkout', [CheckoutController::class, 'create']);
$router->post('/order', [CheckoutController::class, 'store']);
$router->post('/checkout', [CheckoutController::class, 'store']);
$router->get('/track-order', [PageController::class, 'trackForm']);
$router->post('/track-order', [PageController::class, 'track']);
$router->get('/contact', [PageController::class, 'contactForm']);
$router->post('/contact', [PageController::class, 'contact']);
$router->get('/pages/{slug}', [PageController::class, 'page']);
$router->get('/orders/{orderNo}', [CheckoutController::class, 'show']);
$router->get('/invoices/{invoiceNo}', [InvoiceController::class, 'show']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/forgot-password', [AuthController::class, 'showForgot']);
$router->post('/forgot-password', [AuthController::class, 'forgot']);
$router->get('/reset-password', [AuthController::class, 'showReset']);
$router->post('/reset-password', [AuthController::class, 'reset']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class . ':web']);
$router->get('/account/orders', [AccountController::class, 'orders'], [AuthMiddleware::class . ':web']);
