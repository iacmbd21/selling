<?php
use App\Core\Money;

$subtotal = '0.00';
$count = 0;
foreach ($items as $item) {
    $count += (int) $item['quantity'];
    $subtotal = Money::add($subtotal, Money::mul($item['price'], (int) $item['quantity']));
}
?>
<header class="cart-head">
    <h1>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
        Cart
    </h1>
    <?php if ($items !== []): ?><p><?= (int) $count ?> <?= $count === 1 ? 'item' : 'items' ?></p><?php endif; ?>
</header>
<?php if ($items === []): ?>
    <section class="cart-empty">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
        <p>Nothing is in the cart yet.</p>
        <a class="button" href="<?= e(url('/products')) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z"/></svg>
            Browse products
        </a>
    </section>
<?php else: ?>
    <div class="cart-layout">
        <div class="cart-list">
            <?php foreach ($items as $item): ?>
                <?php
                $photo = product_image_url($item['image_path'] ?? null);
                $line = Money::mul($item['price'], (int) $item['quantity']);
                ?>
                <article class="cart-item">
                    <a class="cart-thumb" href="<?= e(url('/products/' . $item['slug'])) ?>">
                        <?php if ($photo): ?>
                            <img src="<?= e($photo) ?>" alt="">
                        <?php else: ?>
                            <span><?= e(strtoupper(substr($item['name'], 0, 1))) ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="cart-copy">
                        <h2><a href="<?= e(url('/products/' . $item['slug'])) ?>"><?= e($item['name']) ?></a></h2>
                        <p><?= e(taka($item['price'])) ?> / <?= e(unit_code($item['unit'] ?? null)) ?> · <?= e(qty_text($item['stock'], $item['unit'] ?? null)) ?> in stock</p>
                    </div>
                    <form method="post" action="<?= e(url('/cart/update')) ?>" class="qty-stepper">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                            <button type="button" data-qty-step="-1" aria-label="Decrease quantity">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12h12"/></svg>
                            </button>
                            <input name="quantity" type="number" min="0" max="20" value="<?= (int) $item['quantity'] ?>" aria-label="Quantity">
                            <button type="button" data-qty-step="1" aria-label="Increase quantity">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 6v12M6 12h12"/></svg>
                            </button>
                            <button type="submit" class="icon-button" aria-label="Update quantity">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                            </button>
                        </form>
                        <strong class="cart-line"><?= e(taka($line)) ?></strong>
                        <form method="post" action="<?= e(url('/cart/remove')) ?>" class="cart-remove">
                            <?= csrf_field() ?>
                            <input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>">
                            <button class="icon-button danger" type="submit" aria-label="Remove <?= e($item['name']) ?>">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V5h6v2M8 7l1 12h6l1-12"/></svg>
                            </button>
                        </form>
                </article>
            <?php endforeach; ?>
        </div>
        <aside class="cart-summary">
            <h2>Summary</h2>
            <div><span>Subtotal</span><strong><?= e(taka($subtotal)) ?></strong></div>
            <p>Delivery is added on the next step from your address.</p>
            <a class="button" href="<?= e(url('/order')) ?>">
                Next
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
            <a class="text-link" href="<?= e(url('/products')) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
                Keep shopping
            </a>
        </aside>
    </div>
<?php endif; ?>
