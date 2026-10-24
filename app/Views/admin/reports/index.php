<h1>Reports</h1>
<?php if ($report['ok']): ?>
    <div class="alert success">Sales, payments, invoices, and inventory reconcile with the source records.</div>
<?php else: ?>
    <div class="alert danger"><?php foreach ($report['issues'] as $issue): ?><div><?= e($issue) ?></div><?php endforeach; ?></div>
<?php endif; ?>
<div class="stats">
    <article><strong><?= e(taka($report['sales_total'])) ?></strong><span>Net sales</span></article>
    <article><strong><?= e(taka($report['paid_total'])) ?></strong><span>Paid orders</span></article>
</div>
<h2>Sales</h2>
<table>
    <thead><tr><th>Day</th><th>Orders</th><th>Total</th></tr></thead>
    <tbody>
    <?php foreach ($report['sales'] as $row): ?>
        <tr><td><?= e($row['day']) ?></td><td><?= (int) $row['orders'] ?></td><td><?= e(taka($row['total'])) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
<h2>Payments</h2>
<table>
    <thead><tr><th>Method</th><th>Status</th><th>Count</th><th>Amount</th></tr></thead>
    <tbody>
    <?php foreach ($report['payments'] as $row): ?>
        <tr>
            <td><?= e($row['method']) ?></td>
            <td><?= e(str_replace('_', ' ', $row['status'])) ?></td>
            <td><?= (int) $row['payments'] ?></td>
            <td><?= e(taka($row['total'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
