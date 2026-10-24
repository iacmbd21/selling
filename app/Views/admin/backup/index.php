<h1>Database backup</h1>
<p>Create a backup of the shop records, or restore one that this shop created.</p>
<form method="post" action="<?= e(url('/admin/backup')) ?>"><?= csrf_field() ?><button type="submit">Create backup</button></form>
<?php if ($files === []): ?>
    <p class="empty">No backup files yet.</p>
<?php else: ?>
    <table>
        <thead><tr><th>File</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($files as $file): ?>
            <tr>
                <td><?= e($file) ?></td>
                <td>
                    <form method="post" action="<?= e(url('/admin/backup/restore')) ?>" onsubmit="return confirm('Restore this backup and replace current shop data?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="file" value="<?= e($file) ?>">
                        <button type="submit" class="secondary">Restore</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
