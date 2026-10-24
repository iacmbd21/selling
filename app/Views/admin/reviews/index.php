<h1>Reviews</h1>
<table>
    <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Comment</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($reviews as $review): ?>
        <tr>
            <td><?= e($review['product_name']) ?></td>
            <td><?= e($review['customer_name']) ?></td>
            <td><?= (int) $review['rating'] ?></td>
            <td><?= e($review['comment']) ?></td>
            <td><?= e($review['status']) ?></td>
            <td>
                <?php if ($review['status'] === 'pending'): ?>
                    <form method="post" action="<?= e(url('/admin/reviews/' . $review['id'])) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="published"><button type="submit">Publish</button></form>
                    <form method="post" action="<?= e(url('/admin/reviews/' . $review['id'])) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="rejected"><button class="secondary" type="submit">Reject</button></form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
