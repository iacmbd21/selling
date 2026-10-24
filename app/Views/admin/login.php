<h1>Staff login</h1>
<form method="post" action="<?= e(url('/admin/login')) ?>">
    <?= csrf_field() ?>
    <label><span>Email</span><input type="email" name="email" required></label>
    <label><span>Password</span><input type="password" name="password" required></label>
    <button type="submit">Login</button>
</form>
