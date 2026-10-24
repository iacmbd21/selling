<?php
use App\Core\Auth;
use App\Core\Gate;
$role = Auth::role();
?>
<p class="eyebrow"><?= e($order['order_no']) ?></p>
<h1><?= e($order['customer_name']) ?></h1>
<p><?= e($order['mobile']) ?> · <?= e($order['address']) ?></p>
<p>Status: <strong><?= e($order['status']) ?></strong> · Payment: <strong><?= e(str_replace('_', ' ', $order['payment_status'])) ?></strong> · <?= e(strtoupper($order['payment_method'])) ?></p>
<table>
    <thead><tr><th>Product</th><th>SKU</th><th>Unit price</th><th>Qty</th><th>Line</th></tr></thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <tr>
            <td><?= e($item['product_name']) ?></td>
            <td><?= e($item['sku']) ?></td>
            <td><?= e(taka($item['unit_price'])) ?></td>
            <td><?= e(qty_text($item['quantity'], $item['unit'] ?? null)) ?></td>
            <td><?= e(taka($item['line_total'])) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="quote">
    <div><span>Subtotal</span><strong><?= e(taka($order['subtotal'])) ?></strong></div>
    <div><span>Discount</span><strong><?= e(taka($order['discount'])) ?></strong></div>
    <div><span>Delivery</span><strong><?= e(taka($order['shipping'])) ?></strong></div>
    <div class="grand"><span>Total</span><strong><?= e(taka($order['total'])) ?></strong></div>
</div>
<?php if ($invoice): ?>
    <p><a class="button" href="<?= e(url('/invoices/' . $invoice['invoice_no'])) ?>">Print invoice</a></p>
<?php endif; ?>

<div class="action-row">
    <?php if ($order['status'] === 'pending' && Gate::allows($role, 'orders.manage')): ?>
        <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/confirm')) ?>"><?= csrf_field() ?><button type="submit">Confirm</button></form>
    <?php endif; ?>
    <?php if ($order['status'] === 'confirmed' && Gate::allows($role, 'orders.manage')): ?>
        <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/process')) ?>"><?= csrf_field() ?><button type="submit">Start packing</button></form>
    <?php endif; ?>
    <?php if ($order['status'] === 'processing' && Gate::allows($role, 'orders.manage')): ?>
        <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/ship')) ?>"><?= csrf_field() ?>
            <input name="courier_name" placeholder="Courier">
            <input name="tracking_number" placeholder="Tracking number">
            <button type="submit">Mark shipped</button>
        </form>
    <?php endif; ?>
    <?php if ($order['status'] === 'shipped' && Gate::allows($role, 'orders.manage')): ?>
        <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/deliver')) ?>"><?= csrf_field() ?><button type="submit">Mark delivered</button></form>
    <?php endif; ?>
    <?php if (in_array($order['status'], ['pending', 'confirmed', 'processing'], true) && Gate::allows($role, 'orders.manage')): ?>
        <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/cancel')) ?>"><?= csrf_field() ?><button type="submit" class="secondary">Cancel and restore stock</button></form>
    <?php endif; ?>
    <?php if ($order['status'] === 'delivered' && Gate::allows($role, 'orders.return')): ?>
        <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/return')) ?>"><?= csrf_field() ?><button type="submit" class="secondary">Record return</button></form>
    <?php endif; ?>
</div>

<?php if ($order['payment_status'] === 'pending' && $order['payment_method'] === 'cod' && !in_array($order['status'], ['cancelled', 'returned'], true) && Gate::allows($role, 'payments.collect')): ?>
    <h2>Cash collection</h2>
    <p>This cash order stays unpaid until collection is recorded.</p>
    <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/collect')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="amount" value="<?= e($order['total']) ?>">
        <button type="submit">Record collection of <?= e(taka($order['total'])) ?></button>
    </form>
<?php endif; ?>

<?php if (in_array($order['payment_status'], ['pending', 'failed'], true) && !in_array($order['status'], ['cancelled', 'returned'], true) && Gate::allows($role, 'payments.submit')): ?>
    <h2>bKash or Nagad</h2>
    <form method="post" action="<?= e(url('/admin/orders/' . $order['id'] . '/payment')) ?>" class="stack">
        <?= csrf_field() ?>
        <input type="hidden" name="amount" value="<?= e($order['total']) ?>">
        <label><span>Method</span>
            <select name="method"><option value="bkash">bKash</option><option value="nagad">Nagad</option></select>
        </label>
        <label><span>Transaction ID</span><input name="transaction_id" required placeholder="8 to 12 letters or numbers"></label>
        <label><span>Sender mobile</span><input name="sender_number" required placeholder="01XXXXXXXXX"></label>
        <button type="submit">Submit for verification</button>
    </form>
<?php endif; ?>

<h2>Payments</h2>
<table>
    <thead><tr><th>Method</th><th>Amount</th><th>Status</th><th>Transaction</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($payments as $payment): ?>
        <tr>
            <td><?= e($payment['method']) ?></td>
            <td><?= e(taka($payment['amount'])) ?></td>
            <td><?= e(str_replace('_', ' ', $payment['status'])) ?></td>
            <td><?= e($payment['transaction_id'] ?: '—') ?></td>
            <td>
                <?php if ($payment['status'] === 'submitted' && Gate::allows($role, 'payments.verify') && (int) $payment['submitted_by'] !== (int) Auth::id()): ?>
                    <form method="post" action="<?= e(url('/admin/payments/' . $payment['id'] . '/verify')) ?>"><?= csrf_field() ?><button type="submit">Verify</button></form>
                <?php elseif ($payment['status'] === 'submitted'): ?>
                    Waiting for a different authorized user
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
