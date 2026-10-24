<h1>Orders</h1>
<form class="filters" method="get" action="<?= e(url('/admin/orders')) ?>">
    <input name="q" value="<?= e($filters['q']) ?>" placeholder="Order, name, or mobile">
    <select name="status">
        <option value="">Any status</option>
        <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled','returned'] as $status): ?>
            <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="payment_status">
        <option value="">Any payment</option>
        <?php foreach (['pending','submitted','verified','failed','refunded','partially_refunded'] as $status): ?>
            <option value="<?= e($status) ?>" <?= $filters['payment_status'] === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $status)) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Filter</button>
</form>
<table>
    <thead><tr><th>Order</th><th>Customer</th><th>Mobile</th><th>Total</th><th>Status</th><th>Payment</th></tr></thead>
    <tbody>
    <?php foreach ($orders as $order): ?>
        <tr>
            <td><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>"><?= e($order['order_no']) ?></a></td>
            <td><?= e($order['customer_name']) ?></td>
            <td><?= e($order['mobile']) ?></td>
            <td><?= e(taka($order['total'])) ?></td>
            <td><?= e($order['status']) ?></td>
            <td><?= e(str_replace('_', ' ', $order['payment_status'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
