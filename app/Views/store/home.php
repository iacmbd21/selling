<section class="home-stock">
    <div class="section-head">
        <h2>In stock now</h2>
        <a href="<?= e(url('/products')) ?>">Search and filter</a>
    </div>
    <div class="catalog-grid">
        <?php foreach (array_slice($featured, 0, 8) as $product): ?>
            <?php require BASE_PATH . '/app/Views/store/partials/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
