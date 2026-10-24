<h1>Shipping zones</h1>
<p>Fees are in BDT. Cash on delivery is offered only where the zone allows it.</p>
<table>
    <thead><tr><th>Zone</th><th>Country</th><th>Region</th><th>Fee</th><th>Free from</th><th>Payments</th></tr></thead>
    <tbody>
    <?php foreach ($zones as $zone): ?>
        <tr>
            <td><?= e($zone['name']) ?></td>
            <td><?= e($zone['country_code']) ?></td>
            <td><?= e($zone['region_code']) ?></td>
            <td><?= e(taka($zone['base_fee'])) ?></td>
            <td><?= $zone['free_shipping_threshold'] === null ? '—' : e(taka($zone['free_shipping_threshold'])) ?></td>
            <td><?= e($zone['payment_methods']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
