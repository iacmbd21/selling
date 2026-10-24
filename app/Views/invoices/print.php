<div class="actions">
    <button type="button" onclick="window.print()">Print invoice</button>
</div>
<header class="invoice-head">
    <div>
        <h1>Today Sell</h1>
        <p class="muted">Invoice <?= e($snapshot['invoice_no']) ?></p>
    </div>
    <div>
        <p>Order <?= e($snapshot['order_no']) ?></p>
        <p class="muted">Issued <?= e($snapshot['issued_at']) ?></p>
    </div>
</header>
<h2><?= e($snapshot['customer_name']) ?></h2>
<p><?= e($snapshot['mobile']) ?><br><?= nl2br(e($snapshot['address'])) ?></p>
<table>
    <thead><tr><th>Product</th><th>SKU</th><th>Price</th><th>Qty</th><th>Amount</th></tr></thead>
    <tbody>
    <?php foreach ($snapshot['items'] as $item): ?>
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
<div class="totals">
    <div><span>Subtotal</span><span><?= e(taka($snapshot['subtotal'])) ?></span></div>
    <div><span>Discount</span><span><?= e(taka($snapshot['discount'])) ?></span></div>
    <div><span>Delivery</span><span><?= e(taka($snapshot['shipping'])) ?></span></div>
    <div><strong>Total</strong><strong><?= e(taka($snapshot['total'])) ?></strong></div>
</div>
<p class="muted">This invoice keeps the product names and prices from the day the order was placed. Payment at issue: <?= e(str_replace('_', ' ', (string) $snapshot['payment_status'])) ?>.</p>
