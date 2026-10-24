<h1>Customers</h1>
<table>
    <thead><tr><th>Name</th><th>Email</th><th>Mobile</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($customers as $customer): ?>
        <tr>
            <td><?= e($customer['name']) ?></td>
            <td><?= e($customer['email']) ?></td>
            <td><?= e($customer['mobile']) ?></td>
            <td><?= e($customer['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
