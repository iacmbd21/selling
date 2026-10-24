<h1>Track an order</h1>
<p>Enter the order number and the mobile number used at checkout.</p>
<form method="post" action="<?= e(url('/track-order')) ?>" class="order-form">
    <?= csrf_field() ?>
    <label><span>Order number</span><input name="order_no" required></label>
    <label><span>Mobile no</span><input name="mobile" required></label>
    <button type="submit">Track</button>
</form>
