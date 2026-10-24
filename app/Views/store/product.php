<?php
$photos = [];
foreach ($gallery ?? [] as $photo) {
    $src = product_image_url($photo['path'] ?? null);
    if ($src) {
        $photos[] = $src;
    }
}
$active = $photos[0] ?? null;
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$shareUrl = $scheme . '://' . $host . url('/products/' . $product['slug']);
$shareText = $product['name'] . ' — ' . taka($product['price']) . ' / ' . unit_code($product['unit'] ?? null);
?>
<p class="eyebrow"><a href="<?= e(url('/products')) ?>">Products</a></p>
<article class="detail-layout">
    <div>
        <?php if ($active): ?>
            <button type="button" class="zoom-frame" data-zoom-src="<?= e($active) ?>" data-zoom-alt="<?= e($product['name']) ?>">
                <img src="<?= e($active) ?>" alt="<?= e($product['name']) ?>">
                <span class="zoom-hit">Zoom</span>
            </button>
            <?php if (count($photos) > 1): ?>
                <div class="thumb-row">
                    <?php foreach ($photos as $index => $src): ?>
                        <button type="button" data-gallery-src="<?= e($src) ?>" aria-label="Photo <?= (int) $index + 1 ?>" <?= $index === 0 ? 'aria-current="true"' : '' ?>>
                            <img src="<?= e($src) ?>" alt="">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="zoom-frame swatch-frame"><div class="swatch large"><?= e(strtoupper(substr($product['name'], 0, 1))) ?></div></div>
        <?php endif; ?>
        <div class="share-row" data-share-url="<?= e($shareUrl) ?>" data-share-title="<?= e($product['name']) ?>">
            <button type="button" class="share-copy" data-copy-link="<?= e($shareUrl) ?>" aria-label="Copy link">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M4 16V6a2 2 0 0 1 2-2h10"/></svg>
            </button>
            <a class="share-whatsapp" href="<?= e('https://wa.me/?text=' . rawurlencode($shareText . ' ' . $shareUrl)) ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.5a8.5 8.5 0 0 0-7.3 12.8L4 20.5l4.4-.8A8.5 8.5 0 1 0 12 3.5Zm4.6 12.1c-.2.5-1.1.9-1.5 1-.4.1-.9.1-1.4-.1-.3-.1-.7-.2-1.2-.5-2.1-1.1-3.5-3.2-3.6-3.4-.1-.2-1-1.3-1-2.5s.6-1.8.9-2c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .5.4.2.5.7 1.7.7 1.8.1.1.1.3 0 .4-.1.2-.2.3-.3.5l-.2.3c-.1.1-.2.2-.1.4.1.2.6 1 1.3 1.6.9.8 1.6 1 1.8 1.1.2.1.4.1.5-.1l.4-.6c.1-.2.3-.2.5-.1.2.1 1.4.7 1.6.8.2.1.4.2.4.3.1.3 0 .9-.2 1.4Z"/></svg>
            </a>
            <a class="share-facebook" href="<?= e('https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($shareUrl)) ?>" target="_blank" rel="noopener" aria-label="Share on Facebook">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h2.6l.4-3H13v-2c0-.6.4-1 1-1Z"/></svg>
            </a>
            <button type="button" class="share-native" data-native-share hidden aria-label="Share">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="6" cy="12" r="2"/><circle cx="17" cy="7" r="2"/><circle cx="17" cy="17" r="2"/><path d="m8 11 7-3M8 13l7 3"/></svg>
            </button>
            <p class="share-note" data-share-note hidden>Link copied</p>
        </div>
    </div>
    <div class="detail-copy">
        <p class="muted"><?= e($product['category_name']) ?> · <?= e($product['sku']) ?></p>
        <h1 class="product-name"><?= e($product['name']) ?></h1>
        <p class="price"><?= e(taka($product['price'])) ?> <span class="muted">/ <?= e(unit_code($product['unit'] ?? null)) ?></span></p>
        <p><?= nl2br(e($product['description'] ?: 'Available for delivery.')) ?></p>
        <dl class="spec-list">
            <div><dt>Unit</dt><dd><?= e(product_units()[unit_code($product['unit'] ?? null)] ?? 'Piece (pc)') ?></dd></div>
            <div><dt>In stock</dt><dd><?= e(qty_text($product['stock'], $product['unit'] ?? null)) ?></dd></div>
            <div><dt>SKU</dt><dd><?= e($product['sku']) ?></dd></div>
        </dl>
        <form method="post" action="<?= e(url('/cart')) ?>" class="detail-buy">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <label class="qty inline">
                <span>Qty (<?= e(unit_code($product['unit'] ?? null)) ?>)</span>
                <input type="number" name="quantity" min="1" max="20" value="1">
            </label>
            <button type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
                Add to cart
            </button>
        </form>
    </div>
</article>
<section class="reviews">
    <h2>Reviews</h2>
    <?php if (($reviews ?? []) === []): ?>
        <p class="muted">No published reviews yet.</p>
    <?php else: ?>
        <?php foreach ($reviews as $review): ?>
            <article class="card review-card">
                <strong><?= e($review['name']) ?></strong> · <?= (int) $review['rating'] ?>/5
                <p><?= e($review['comment']) ?></p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (\App\Core\Auth::check()): ?>
        <form method="post" action="<?= e(url('/products/' . $product['slug'] . '/reviews')) ?>" class="stack">
            <?= csrf_field() ?>
            <label><span>Rating</span><input type="number" name="rating" min="1" max="5" value="5" required></label>
            <label><span>Comment</span><textarea name="comment" rows="3"></textarea></label>
            <button type="submit">Submit review</button>
        </form>
    <?php else: ?>
        <p class="hint"><a href="<?= e(url('/login')) ?>">Sign in</a> to review a delivered purchase.</p>
    <?php endif; ?>
</section>
