<h1>Staff</h1>
<table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($users as $user): ?>
        <tr>
            <td><?= e($user['name']) ?></td>
            <td><?= e($user['email']) ?></td>
            <td><?= e($user['role']) ?></td>
            <td><?= e($user['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<h2>Add staff</h2>
<?php if (!empty($errors)): ?><div class="alert danger"><?php foreach ($errors as $message): ?><div><?= e($message) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" action="<?= e(url('/admin/users')) ?>" class="stack">
    <?= csrf_field() ?>
    <label><span>Name</span><input name="name" required></label>
    <label><span>Email</span><input type="email" name="email" required></label>
    <label><span>Password</span><input type="password" name="password" required></label>
    <label><span>Role</span><select name="role"><option value="staff">Staff</option><option value="manager">Manager</option></select></label>
    <button type="submit">Create account</button>
</form>
