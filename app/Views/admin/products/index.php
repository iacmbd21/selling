<div class="page-head">
    <div>
        <h1>Products</h1>
        <p class="muted">Update a product, or add one with shop photos.</p>
    </div>
    <button type="button" data-open-modal="add-product">Add product</button>
</div>
<?php
$filters = $filters ?? ['name' => '', 'sku' => '', 'price' => '', 'stock' => '', 'status' => '', 'photo' => ''];
$filtering = implode('', $filters) !== '';
$query = array_filter($filters, static fn (string $value): bool => $value !== '');
$pageUrl = static function (int $number) use ($query): string {
    return url('/admin/products?' . http_build_query($query + ['page' => $number]));
};
?>
<?php if ($products === [] && !$filtering): ?>
    <p class="empty">No products yet.</p>
<?php else: ?>
    <form id="product-filters" method="get" action="<?= e(url('/admin/products')) ?>" onsubmit="Array.from(this.elements).forEach((field) => { if (!field.value) field.disabled = true; })"></form>
        <div class="table-wrap">
        <table class="product-table">
            <thead>
                <tr><th>Photo</th><th>Name</th><th>SKU</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr>
                <tr class="filter-row">
                    <th>
                        <select form="product-filters" name="photo" aria-label="Filter by photo" onchange="this.form.requestSubmit()">
                            <option value="">Any</option>
                            <option value="yes" <?= $filters['photo'] === 'yes' ? 'selected' : '' ?>>With photo</option>
                            <option value="no" <?= $filters['photo'] === 'no' ? 'selected' : '' ?>>No photo</option>
                        </select>
                    </th>
                    <th><input form="product-filters" name="name" value="<?= e($filters['name']) ?>" placeholder="Name" aria-label="Filter by name"></th>
                    <th><input form="product-filters" name="sku" value="<?= e($filters['sku']) ?>" placeholder="SKU" aria-label="Filter by SKU"></th>
                    <th><input form="product-filters" name="price" value="<?= e($filters['price']) ?>" inputmode="decimal" placeholder="Price" aria-label="Filter by price"></th>
                    <th><input form="product-filters" name="stock" value="<?= e($filters['stock']) ?>" inputmode="numeric" placeholder="Stock" aria-label="Filter by stock"></th>
                    <th>
                        <select form="product-filters" name="status" aria-label="Filter by status" onchange="this.form.requestSubmit()">
                            <option value="">Any</option>
                            <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </th>
                    <th class="filter-actions">
                        <button form="product-filters" type="submit" class="icon-button" aria-label="Filter">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v6l-4 2v-8L4 5Z"/></svg>
                        </button>
                        <?php if ($filtering): ?><a class="icon-button" href="<?= e(url('/admin/products')) ?>" aria-label="Clear filters">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </a><?php endif; ?>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php if ($products === []): ?>
                <tr><td colspan="7">No products match these filters.</td></tr>
            <?php endif; ?>
            <?php foreach ($products as $product): ?>
                <?php $image = product_image_url($product['image_path'] ?? null); ?>
                <tr>
                    <td>
                        <?php if ($image): ?>
                            <img class="admin-thumb" src="<?= e($image) ?>" alt="">
                        <?php else: ?>
                            <div class="swatch"><?= e(strtoupper(substr($product['name'], 0, 1))) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($product['name']) ?><?php if (!empty($product['featured'])): ?> <span class="muted">Featured</span><?php endif; ?><?php $count = (int) ($product['image_count'] ?? 0); if ($count > 1): ?> <span class="muted"><?= $count ?> photos</span><?php endif; ?><br><span class="muted"><?= e($product['category_name']) ?></span></td>
                    <td><?= e($product['sku']) ?></td>
                    <td><?= e(taka($product['price'])) ?> <span class="muted">/ <?= e(unit_code($product['unit'] ?? null)) ?></span></td>
                    <td><?= e(qty_text($product['stock'], $product['unit'] ?? null)) ?></td>
                    <td><?= e($product['status']) ?></td>
                    <td>
                        <div class="row-actions">
                            <a class="icon-button" href="<?= e(url('/admin/products/' . $product['id'] . '/edit')) ?>" aria-label="Edit <?= e($product['name']) ?>">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4L18 10l-4-4L4 16v4Z"/><path d="m13 6 4 4"/></svg>
                            </a>
                            <form method="post" action="<?= e(url('/admin/products/' . $product['id'] . '/delete')) ?>" onsubmit="return confirm(<?= e(json_encode('Delete ' . $product['name'] . '?')) ?>)">
                                <?= csrf_field() ?>
                                <button type="submit" class="icon-button danger" aria-label="Delete <?= e($product['name']) ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16"/><path d="M9 7V5h6v2"/><path d="M7 7l1 13h8l1-13"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php if ($pages > 1): ?>
        <nav class="pager" aria-label="Product pages">
            <?php if ($page > 1): ?><a class="secondary" href="<?= e($pageUrl($page - 1)) ?>">Previous</a><?php endif; ?>
            <span>Page <?= (int) $page ?> of <?= (int) $pages ?></span>
            <?php if ($page < $pages): ?><a class="secondary" href="<?= e($pageUrl($page + 1)) ?>">Next</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
<dialog id="add-product" class="product-dialog" <?= !empty($errors) ? 'data-open' : '' ?> aria-labelledby="add-product-title">
    <div class="dialog-body">
        <div class="dialog-head">
            <h2 id="add-product-title">Add a product</h2>
            <button type="button" class="secondary" data-close-modal>Close</button>
        </div>
        <?php if (!empty($errors)): ?>
            <div class="alert danger"><?php foreach ($errors as $message): ?><div><?= e($message) ?></div><?php endforeach; ?></div>
        <?php endif; ?>
        <?php
        $editing = null;
        require BASE_PATH . '/app/Views/admin/products/form.php';
        ?>
    </div>
</dialog>
