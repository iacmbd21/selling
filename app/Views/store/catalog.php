<div class="catalog-head">
    <div>
        <p class="eyebrow">Shop</p>
        <h1>Products</h1>
    </div>
    <p class="muted"><?= count($products) ?> available</p>
</div>
<form class="catalog-filters" method="get" action="<?= e(url('/products')) ?>">
    <input name="q" value="<?= e($filters['q']) ?>" placeholder="Search name or SKU" aria-label="Search name or SKU">
    <select name="category_id" aria-label="Category">
        <option value="">All categories</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= (int) $category['id'] ?>" <?= (string) $filters['category_id'] === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input name="min_price" value="<?= e($filters['min_price']) ?>" inputmode="decimal" placeholder="Min price" aria-label="Minimum price">
    <input name="max_price" value="<?= e($filters['max_price']) ?>" inputmode="decimal" placeholder="Max price" aria-label="Maximum price">
    <button type="submit">Filter</button>
</form>
<?php if ($products === []): ?>
    <p class="empty">No active products match that search.</p>
<?php else: ?>
    <div class="catalog-grid">
        <?php foreach ($products as $product): ?>
            <?php require BASE_PATH . '/app/Views/store/partials/product_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
