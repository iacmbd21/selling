<h1>Contact</h1>
<form method="post" action="<?= e(url('/contact')) ?>" class="order-form">
    <?= csrf_field() ?>
    <label><span>Name</span><input name="name" required></label>
    <label><span>Email</span><input type="email" name="email" required></label>
    <label><span>Message</span><textarea name="message" required rows="4"></textarea></label>
    <button type="submit">Send</button>
</form>
<p class="hint"><a href="<?= e(url('/pages/delivery')) ?>">Delivery</a> · <a href="<?= e(url('/pages/returns')) ?>">Returns</a> · <a href="<?= e(url('/pages/privacy')) ?>">Privacy</a> · <a href="<?= e(url('/pages/terms')) ?>">Terms</a></p>
