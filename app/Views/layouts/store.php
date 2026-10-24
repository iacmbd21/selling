<?php

use App\Core\Auth;
use App\Core\Gate;
use App\Core\Session;
use App\Services\CartService;

$currentUser = Auth::user();
$success = Session::pull('success');
$error = Session::pull('error');
$cartCount = 0;
try {
    $cartCount = (new CartService())->count();
} catch (Throwable) {
    $cartCount = 0;
}
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = base_href();
$here = ($basePath !== '' && str_starts_with($requestPath, $basePath))
    ? substr($requestPath, strlen($basePath))
    : $requestPath;
$here = '/' . trim((string) $here, '/');
$shop = require BASE_PATH . '/config/app.php';
$shopEmail = (string) ($shop['email'] ?? '');
$shopPhone = (string) ($shop['phone'] ?? '');
$shopSocial = is_array($shop['social'] ?? null) ? $shop['social'] : [];
$navOn = static function (string $path) use ($here): string {
    $match = $path === '/'
        ? $here === '/'
        : ($here === $path || str_starts_with($here, $path . '/'));
    return $match ? ' aria-current="page"' : '';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Today Sell') ?> · Today Sell</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,650&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/app.css')) ?>?v=<?= (int) filemtime(BASE_PATH . '/public/assets/app.css') ?>">
</head>
<body>
<header class="site-header">
    <div class="utility-bar">
        <div class="utility-contact">
            <?php if ($shopEmail !== ''): ?>
                <a href="mailto:<?= e($shopEmail) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>
                    <?= e($shopEmail) ?>
                </a>
            <?php endif; ?>
            <?php if ($shopPhone !== ''): ?>
                <a href="tel:<?= e(preg_replace('/\D+/', '', $shopPhone) ?? '') ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h4l1 4-2 1a12 12 0 0 0 6 6l1-2 4 1v4c0 1-1 2-2 2A16 16 0 0 1 5 5c0-1 1-2 2-2Z"/></svg>
                    <?= e($shopPhone) ?>
                </a>
            <?php endif; ?>
        </div>
        <div class="utility-social">
            <?php if (!empty($shopSocial['facebook'])): ?>
                <a href="<?= e($shopSocial['facebook']) ?>" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h2.6l.4-3H13v-2c0-.6.4-1 1-1Z"/></svg></a>
            <?php endif; ?>
            <?php if (!empty($shopSocial['instagram'])): ?>
                <a href="<?= e($shopSocial['instagram']) ?>" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="4" width="16" height="16" rx="4"/><circle cx="12" cy="12" r="3.5"/><circle cx="17.5" cy="6.5" r="0.8" fill="currentColor" stroke="none"/></svg></a>
            <?php endif; ?>
            <?php if (!empty($shopSocial['whatsapp'])): ?>
                <a href="<?= e($shopSocial['whatsapp']) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4a8 8 0 0 0-6.9 12L4 20l4.2-1.1A8 8 0 1 0 12 4Z"/><path d="M9.2 8.8c.2-.4.4-.4.6-.4h.5c.2 0 .4 0 .5.4.2.5.6 1.6.6 1.7.1.2 0 .3-.1.5l-.3.4c-.1.1-.2.3 0 .5.3.5.8 1.1 1.6 1.5.2.1.4.1.5-.1l.4-.5c.1-.2.3-.1.5-.1.2 0 1.2.6 1.4.7.2.1.3.2.3.4 0 .5-.4 1.4-1.1 1.6-.6.2-1.4.1-2.4-.3-1.2-.5-2.2-1.4-3-2.6-.7-1.1-.9-2-.7-2.6.1-.3.3-.6.3-.7Z"/></svg></a>
            <?php endif; ?>
            <?php if (!empty($shopSocial['youtube'])): ?>
                <a href="<?= e($shopSocial['youtube']) ?>" target="_blank" rel="noopener" aria-label="YouTube"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8.5A2.5 2.5 0 0 1 7.5 6h9A2.5 2.5 0 0 1 19 8.5v7a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 5 15.5v-7Z"/><path d="m11 10 4 2-4 2v-4Z" fill="currentColor" stroke="none"/></svg></a>
            <?php endif; ?>
            <?php if (!empty($shopSocial['x'])): ?>
                <a href="<?= e($shopSocial['x']) ?>" target="_blank" rel="noopener" aria-label="X"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h4.2l3.1 4.3L16.2 5H19l-5.2 6.4L19.2 19h-4.2l-3.4-4.7L7.6 19H5l5.6-6.9L5 5Z"/></svg></a>
            <?php endif; ?>
        </div>
    </div>
<header class="topbar">
    <a class="brand" href="<?= e(url('/')) ?>">
        <span class="brand-mark" aria-hidden="true">T</span>
        Today Sell
    </a>
    <button type="button" class="nav-toggle" data-nav-toggle aria-expanded="false" aria-controls="site-nav">Menu</button>
    <nav id="site-nav">
        <a class="nav-link" href="<?= e(url('/shop')) ?>"<?= $navOn('/shop') . $navOn('/products') ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z"/></svg>
            Shop
        </a>
        <a class="nav-link" href="<?= e(url('/order')) ?>"<?= $navOn('/order') ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h10l1 4H6l1-4Z"/><path d="M6 8h12l-1 12H7L6 8Z"/></svg>
            Checkout
        </a>
        <a class="nav-link" href="<?= e(url('/track-order')) ?>"<?= $navOn('/track-order') ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v5l3 2"/></svg>
            Track
        </a>
        <a class="nav-link" href="<?= e(url('/contact')) ?>"<?= $navOn('/contact') ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 6h14v10H8l-3 3V6Z"/></svg>
            Contact
        </a>
        <span class="nav-divider" aria-hidden="true"></span>
        <a class="nav-cart" href="<?= e(url('/cart')) ?>"<?= $navOn('/cart') ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
            Cart
            <?php if ($cartCount): ?><span class="nav-count"><?= (int) $cartCount ?></span><?php endif; ?>
        </a>
        <?php if ($currentUser && $currentUser['role'] === 'customer'): ?>
            <a class="nav-link" href="<?= e(url('/account/orders')) ?>"<?= $navOn('/account') ?>>My orders</a>
            <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button type="submit">Logout</button></form>
        <?php elseif ($currentUser && Gate::allows($currentUser['role'], 'dashboard.view')): ?>
            <a class="nav-link" href="<?= e(url('/admin')) ?>"<?= $navOn('/admin') ?>>Admin</a>
            <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?><button type="submit">Logout</button></form>
        <?php else: ?>
            <a class="nav-link" href="<?= e(url('/login')) ?>"<?= $navOn('/login') ?>>Login</a>
            <a class="nav-link nav-register" href="<?= e(url('/register')) ?>"<?= $navOn('/register') ?>>Register</a>
        <?php endif; ?>
    </nav>
</header>
</header>
<main class="wrap">
    <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
    <?= $content ?>
</main>
<footer class="site-footer">
    <p>Cash on delivery. The order total is calculated on the server.</p>
    <p><a href="<?= e(url('/pages/delivery')) ?>">Delivery</a> · <a href="<?= e(url('/pages/returns')) ?>">Returns</a> · <a href="<?= e(url('/pages/privacy')) ?>">Privacy</a> · <a href="<?= e(url('/pages/terms')) ?>">Terms</a> · <a href="<?= e(url('/contact')) ?>">Contact</a> · <a href="<?= e(url('/track-order')) ?>">Track order</a></p>
</footer>
<dialog id="image-zoom" class="zoom-dialog" aria-label="Zoomed product photo">
    <div class="zoom-toolbar">
        <p class="zoom-caption"></p>
        <button type="button" data-zoom-out aria-label="Zoom out">−</button>
        <button type="button" data-zoom-in aria-label="Zoom in">+</button>
        <button type="button" data-close-modal>Close</button>
    </div>
    <div class="zoom-stage" data-zoom-stage>
        <img alt="">
    </div>
</dialog>
<script src="<?= e(url('/assets/app.js')) ?>?v=<?= (int) filemtime(BASE_PATH . '/public/assets/app.js') ?>"></script>
</body>
</html>
