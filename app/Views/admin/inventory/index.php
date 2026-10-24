<h1>Inventory</h1>
<?php if ($issues !== []): ?>
    <div class="alert danger"><?php foreach ($issues as $issue): ?><div><?= e($issue) ?></div><?php endforeach; ?></div>
<?php else: ?>
    <div class="alert success">Stock matches the inventory movement records.</div>
<?php endif; ?>
<table>
    <thead><tr><th>Product</th><th>SKU</th><th>Stock</th><th>Movement stock</th><th>Units sold</th><th>Set stock</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= e($row['name']) ?></td>
            <td><?= e($row['sku']) ?></td>
            <td><?= e(qty_text($row['stock'], $row['unit'] ?? null)) ?></td>
            <td><?= $row['movement_stock'] === null ? '—' : (int) $row['movement_stock'] ?></td>
            <td><?= (int) $row['sold_units'] ?></td>
            <td>
                <form method="post" action="<?= e(url('/admin/inventory')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $row['id'] ?>">
                    <input name="stock" value="<?= (int) $row['stock'] ?>" required>
                    <button type="submit">Update</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
