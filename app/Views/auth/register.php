<h1>Create a customer account</h1>
<?php $old = \App\Core\Session::pull('old') ?? []; ?>
<form method="post" action="<?= e(url('/register')) ?>">
    <?= csrf_field() ?>
    <label><span>Name</span><input name="name" required value="<?= e($old['name'] ?? '') ?>"></label>
    <?php if (!empty($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
    <label><span>Email</span><input type="email" name="email" required value="<?= e($old['email'] ?? '') ?>"></label>
    <?php if (!empty($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
    <label><span>Mobile no</span><input name="mobile" value="<?= e($old['mobile'] ?? '') ?>"></label>
    <label><span>Password</span><input type="password" name="password" required></label>
    <?php if (!empty($errors['password'])): ?><p class="field-error"><?= e($errors['password']) ?></p><?php endif; ?>
    <button type="submit">Create account</button>
</form>
