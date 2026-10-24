<section class="confirmation">
    <p class="eyebrow">Order placed</p>
    <h1><?= e($order['order_no']) ?></h1>
    <p class="status"><?= e(strtoupper((string) $order['payment_method'])) ?> · <?= $order['payment_method'] === 'cod' && $order['payment_status'] === 'pending' ? 'unpaid until collection' : e(str_replace('_', ' ', $order['payment_status'])) ?></p>
    <dl class="details">
        <div><dt>Customer name</dt><dd><?= e($order['customer_name']) ?></dd></div>
        <div><dt>Mobile no</dt><dd><?= e($order['mobile']) ?></dd></div>
        <div><dt>Address</dt><dd><?= e($order['address']) ?></dd></div>
    </dl>
    <table>
        <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Line</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= e($item['product_name']) ?></td>
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
        <p><a class="button" href="<?= e(url('/invoices/' . $invoice['invoice_no'])) ?>">View invoice</a></p>
    <?php endif; ?>
</section>
