<h1>Coupons</h1>
<table>
    <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Minimum</th><th>Uses</th><th>Window</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($coupons as $coupon): ?>
        <tr>
            <td><?= e($coupon['code']) ?></td>
            <td><?= e($coupon['type']) ?></td>
            <td><?= e($coupon['value']) ?></td>
            <td><?= e(taka($coupon['min_order'])) ?></td>
            <td><?= (int) $coupon['used_count'] ?><?= $coupon['max_uses'] !== null ? ' / ' . (int) $coupon['max_uses'] : '' ?></td>
            <td><?= e($coupon['starts_at'] ?: 'Now') ?> → <?= e($coupon['expires_at'] ?: 'Open') ?></td>
            <td><?= e($coupon['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<h2>New coupon</h2>
<?php if (!empty($errors)): ?><div class="alert danger"><?php foreach ($errors as $message): ?><div><?= e($message) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" action="<?= e(url('/admin/coupons')) ?>" class="stack">
    <?= csrf_field() ?>
    <label><span>Code</span><input name="code" required></label>
    <label><span>Type</span><select name="type"><option value="percent">Percent</option><option value="fixed">Fixed amount</option></select></label>
    <label><span>Value</span><input name="value" required></label>
    <label><span>Minimum order</span><input name="min_order" value="0"></label>
    <label><span>Maximum uses</span><input name="max_uses" placeholder="Leave empty for unlimited"></label>
    <label><span>Starts</span><input type="datetime-local" name="starts_at"></label>
    <label><span>Expires</span><input type="datetime-local" name="expires_at"></label>
    <button type="submit">Save coupon</button>
</form>
