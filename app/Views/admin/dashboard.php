<h1>Dashboard</h1>
<div class="stats">
    <article><strong><?= (int) $ordersToday ?></strong><span>Orders today</span></article>
    <article><strong><?= (int) $unpaidCod ?></strong><span>Unpaid cash orders</span></article>
    <article><strong><?= (int) $pendingPayments ?></strong><span>Payments to verify</span></article>
    <article><strong><?= (int) $lowStock ?></strong><span>Low stock</span></article>
</div>
<canvas id="sales-chart" height="90"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const trend = <?= json_encode($trend ?? [], JSON_THROW_ON_ERROR) ?>;
new Chart(document.getElementById('sales-chart'), {
    type: 'line',
    data: {
        labels: trend.map((row) => row.day),
        datasets: [{ label: 'Sales (BDT)', data: trend.map((row) => row.total), borderColor: '#0f6e56', tension: 0.2 }]
    },
    options: { plugins: { legend: { display: false } } }
});
</script>
<h2>Recent orders</h2>
<table>
    <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Payment</th></tr></thead>
    <tbody>
    <?php foreach ($recent as $order): ?>
        <tr>
            <td><a href="<?= e(url('/admin/orders/' . $order['id'])) ?>"><?= e($order['order_no']) ?></a></td>
            <td><?= e($order['customer_name']) ?></td>
            <td><?= e(taka($order['total'])) ?></td>
            <td><?= e($order['status']) ?></td>
            <td><?= e(str_replace('_', ' ', $order['payment_status'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
