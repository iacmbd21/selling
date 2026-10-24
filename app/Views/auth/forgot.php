<h1>Reset password</h1>
<p>Enter the email on the customer account.</p>
<?php if (!empty($resetUrl)): ?>
    <p class="alert success">Email delivery is not configured on this server. Use this one-time link: <a href="<?= e($resetUrl) ?>">Reset password</a></p>
<?php endif; ?>
<form method="post" action="<?= e(url('/forgot-password')) ?>">
    <?= csrf_field() ?>
    <label><span>Email</span><input type="email" name="email" required></label>
    <button type="submit">Create reset link</button>
</form>
