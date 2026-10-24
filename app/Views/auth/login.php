<?php $old = \App\Core\Session::pull('old') ?? []; ?>
<h1>Customer login</h1>
<form method="post" action="<?= e(url('/login')) ?>">
    <?= csrf_field() ?>
    <label><span>Email</span><input type="email" name="email" required value="<?= e($old['email'] ?? '') ?>"></label>
    <label><span>Password</span><input type="password" name="password" required></label>
    <button type="submit">Login</button>
</form>
<p class="hint">Ordering does not require an account. <a href="<?= e(url('/order')) ?>">Place an order</a> · <a href="<?= e(url('/forgot-password')) ?>">Forgot password</a></p>
