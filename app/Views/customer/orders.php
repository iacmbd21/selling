<h1>My orders</h1>
<?php if ($orders === []): ?>
    <p class="empty">No orders yet. <a href="<?= e(url('/order')) ?>">Place an order</a>.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Order</th><th>Total</th><th>Status</th><th>Payment</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><a href="<?= e(url('/orders/' . $order['order_no'])) ?>"><?= e($order['order_no']) ?></a></td>
                <td><?= e(taka($order['total'])) ?></td>
                <td><?= e($order['status']) ?></td>
                <td><?= e(str_replace('_', ' ', $order['payment_status'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
