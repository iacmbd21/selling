<?php
/** @var array|null $editing */
/** @var array $categories */
$editing = $editing ?? null;
$photos = $photos ?? [];
$isEdit = $editing !== null;
$action = $isEdit ? url('/admin/products/' . (int) $editing['id']) : url('/admin/products');
?>
<form method="post" action="<?= e($action) ?>" class="stack" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label><span>Name</span><input name="name" required value="<?= e($editing['name'] ?? '') ?>"></label>
    <label><span>SKU</span><input name="sku" required value="<?= e($editing['sku'] ?? '') ?>"></label>
    <label><span>Unit</span>
        <select name="unit">
            <?php foreach (product_units() as $code => $label): ?>
                <option value="<?= e($code) ?>" <?= unit_code($editing['unit'] ?? 'pc') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label><span>Price per unit</span><input name="price" required value="<?= e($editing['price'] ?? '') ?>"></label>
    <?php if (!$isEdit): ?>
        <label><span>Opening stock</span><input name="stock" required value="0"></label>
    <?php endif; ?>
    <p class="hint">Stock and orders are counted in the selected unit. Two kilograms of a kg product cost twice the price per kg.</p>
    <label><span>Category</span>
        <select name="category_id">
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>" <?= $isEdit && (int) $editing['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label><span>Description</span><textarea name="description" rows="3"><?= e($editing['description'] ?? '') ?></textarea></label>
    <label><span>Status</span>
        <select name="status">
            <option value="active" <?= ($editing['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= ($editing['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </label>
    <label class="check"><input type="checkbox" name="featured" value="1" <?= !empty($editing['featured']) ? 'checked' : '' ?>> Featured</label>
    <?php if ($photos !== []): ?>
        <div class="photo-grid">
            <?php foreach ($photos as $photo): ?>
                <?php $src = product_image_url($photo['path'] ?? null); ?>
                <?php if ($src): ?>
                    <label class="photo-choice">
                        <img src="<?= e($src) ?>" alt="">
                        <span class="check"><input type="checkbox" name="remove_images[]" value="<?= (int) $photo['id'] ?>"> Remove</span>
                    </label>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <label>
        <span>Photos</span>
        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
        <span class="file-picks muted"></span>
    </label>
    <p class="hint">Select one or more photos. JPG, PNG, WEBP, or GIF. Up to 6 photos, 2 MB each.<?php if ($isEdit): ?> Stock is changed from Inventory.<?php endif; ?></p>
    <button type="submit"><?= $isEdit ? 'Save product' : 'Add product' ?></button>
</form>
