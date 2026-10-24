<?php
/** @var array $product */
$src = product_image_url($product['image_path'] ?? null);
$detail = url('/products/' . $product['slug']);
?>
<article class="card product-card">
    <div class="product-media">
        <?php if ($src): ?>
            <img src="<?= e($src) ?>" alt="<?= e($product['name']) ?>">
            <button type="button" class="zoom-hit" data-zoom-src="<?= e($src) ?>" data-zoom-alt="<?= e($product['name']) ?>">Zoom</button>
        <?php else: ?>
            <div class="swatch"><?= e(strtoupper(substr($product['name'], 0, 1))) ?></div>
        <?php endif; ?>
    </div>
    <div class="product-body">
        <p class="eyebrow"><?= e($product['category_name']) ?></p>
        <h2 class="product-name"><a href="<?= e($detail) ?>"><?= e($product['name']) ?></a></h2>
        <p class="price"><?= e(taka($product['price'])) ?> <span class="muted">/ <?= e(unit_code($product['unit'] ?? null)) ?></span></p>
        <p class="muted"><?= e(qty_text($product['stock'], $product['unit'] ?? null)) ?> in stock · <?= e($product['sku']) ?></p>
        <div class="card-actions">
            <a class="secondary" href="<?= e($detail) ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                View
            </a>
            <form method="post" action="<?= e(url('/cart')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="quantity" value="1">
                <button class="button" type="submit" <?= (int) $product['stock'] < 1 ? 'disabled' : '' ?>>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
                    Card
                </button>
            </form>
        </div>
    </div>
</article>
