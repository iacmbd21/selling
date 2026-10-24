<h1>Payments waiting for verification</h1>
<?php if ($pending === []): ?>
    <p class="empty">No bKash or Nagad submissions are waiting.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Order</th><th>Method</th><th>Transaction</th><th>Amount</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pending as $payment): ?>
            <tr>
                <td><a href="<?= e(url('/admin/orders/' . $payment['order_id'])) ?>"><?= e($payment['order_no']) ?></a></td>
                <td><?= e($payment['method']) ?></td>
                <td><?= e($payment['transaction_id']) ?></td>
                <td><?= e(taka($payment['amount'])) ?></td>
                <td>
                    <?php if (\App\Core\Gate::allows(\App\Core\Auth::role(), 'payments.verify') && (int) $payment['submitted_by'] !== (int) \App\Core\Auth::id()): ?>
                        <form method="post" action="<?= e(url('/admin/payments/' . $payment['id'] . '/verify')) ?>"><?= csrf_field() ?><button type="submit">Verify</button></form>
                    <?php else: ?>
                        Needs another authorized user
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
