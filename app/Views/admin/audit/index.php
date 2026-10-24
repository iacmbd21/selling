<h1>Audit log</h1>
<table>
    <thead><tr><th>When</th><th>Action</th><th>Record</th><th>Details</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
        <tr>
            <td><?= e($log['created_at']) ?></td>
            <td><?= e($log['action']) ?></td>
            <td><?= e($log['entity_type']) ?> <?= e($log['entity_id']) ?></td>
            <td><?= e($log['metadata_json']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
