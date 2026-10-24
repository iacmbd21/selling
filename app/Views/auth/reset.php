<h1>Choose a new password</h1>
<form method="post" action="<?= e(url('/reset-password')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label><span>New password</span><input type="password" name="password" required></label>
    <button type="submit">Update password</button>
</form>
