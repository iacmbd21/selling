<?php
/** @var array $lines */
/** @var array $products */
/** @var array $old */
/** @var array $errors */
/** @var array|null $quote */
$name = (string) ($old['customer_name'] ?? ($prefillName ?? ''));
$mobile = (string) ($old['mobile'] ?? ($prefillMobile ?? ''));
$address = (string) ($old['address'] ?? '');
$coupon = (string) ($old['coupon_code'] ?? '');
?>
<form class="order-form checkout-layout" method="post" action="<?= e(url('/order')) ?>" data-once>
    <?= csrf_field() ?>
    <input type="hidden" name="idempotency_key" value="<?= e($checkoutKey ?? '') ?>">
    <div class="checkout-main">
        <section class="checkout-section">
            <h2>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/></svg>
                Products
            </h2>
            <p class="hint">The products in your cart are listed below. Enter the customer name, mobile number, and address.</p>
            <div id="lines">
                <?php foreach ($lines as $index => $line): ?>
                    <div class="line">
                        <label>
                            <span class="label-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v12H4z"/><path d="M4 7l8 6 8-6"/></svg>Select product</span>
                            <select name="items[<?= (int) $index ?>][product_id]" required>
                                <option value="">Choose a product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= (int) $product['id'] ?>" data-price="<?= e($product['price']) ?>" <?= (string) ($line['product_id'] ?? '') === (string) $product['id'] ? 'selected' : '' ?> <?= (int) $product['stock'] < 1 ? 'disabled' : '' ?>>
                                        <?= e($product['name']) ?> — <?= e(taka($product['price'])) ?> / <?= e(unit_code($product['unit'] ?? null)) ?> (<?= e(qty_text($product['stock'], $product['unit'] ?? null)) ?> in stock)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="qty">
                            <span class="label-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h16M4 16h16"/></svg>Qty</span>
                            <input type="number" name="items[<?= (int) $index ?>][quantity]" min="1" max="20" value="<?= e($line['quantity'] ?? 1) ?>">
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($errors['product_id'])): ?><p class="field-error"><?= e($errors['product_id']) ?></p><?php endif; ?>
            <?php if (!empty($errors['quantity'])): ?><p class="field-error"><?= e($errors['quantity']) ?></p><?php endif; ?>
            <button type="button" class="text-button" id="add-line">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 6v12M6 12h12"/></svg>
                Add another product
            </button>
        </section>

        <section class="checkout-section">
            <h2>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 19c1.5-3 3.8-4.5 7-4.5S17.5 16 19 19"/></svg>
                Customer
            </h2>
            <div class="customer-grid">
                <label>
                    <span class="label-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M5 19c1.5-3 3.8-4.5 7-4.5S17.5 16 19 19"/></svg>Customer name</span>
                    <input name="customer_name" required maxlength="120" value="<?= e($name) ?>" placeholder="Customer name">
                </label>
                <?php if (!empty($errors['customer_name'])): ?><p class="field-error wide"><?= e($errors['customer_name']) ?></p><?php endif; ?>

                <label>
                    <span class="label-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h4l1 4-2 1a12 12 0 0 0 6 6l1-2 4 1v4c0 1-1 2-2 2A16 16 0 0 1 5 5c0-1 1-2 2-2Z"/></svg>Mobile no</span>
                    <input name="mobile" required inputmode="tel" maxlength="20" value="<?= e($mobile) ?>" placeholder="01XXXXXXXXX">
                </label>
                <?php if (!empty($errors['mobile'])): ?><p class="field-error wide"><?= e($errors['mobile']) ?></p><?php endif; ?>

                <label class="wide">
                    <span class="label-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.2 7-11a7 7 0 1 0-14 0c0 4.8 7 11 7 11Z"/><circle cx="12" cy="10" r="2.2"/></svg>Address</span>
                    <textarea name="address" required rows="3" maxlength="500" placeholder="House, road, area, city"><?= e($address) ?></textarea>
                </label>
                <?php if (!empty($errors['address'])): ?><p class="field-error wide"><?= e($errors['address']) ?></p><?php endif; ?>

                <label>
                    <span class="label-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>Email, if you want updates</span>
                    <input type="email" name="customer_email" value="<?= e($old['customer_email'] ?? '') ?>" placeholder="Optional">
                </label>
                <?php if (!empty($errors['customer_email'])): ?><p class="field-error wide"><?= e($errors['customer_email']) ?></p><?php endif; ?>
                <label>
                    <span class="label-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V9l8-5 8 5v11"/><path d="M9 20v-6h6v6"/></svg>City</span>
                    <input name="city" value="<?= e($old['city'] ?? '') ?>" placeholder="Dhaka">
                </label>
                <label>
                    <span class="label-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M4 12h16M12 4c2.5 2.4 2.5 13.6 0 16M12 4c-2.5 2.4-2.5 13.6 0 16"/></svg>Country</span>
                    <select name="country_code">
                        <?php foreach (['BD' => 'Bangladesh', 'IN' => 'India', 'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States'] as $code => $label): ?>
                            <option value="<?= e($code) ?>" <?= ($old['country_code'] ?? 'BD') === $code ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span class="label-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18"/></svg>Payment method</span>
                    <select name="payment_method">
                        <option value="cod" <?= ($old['payment_method'] ?? 'cod') === 'cod' ? 'selected' : '' ?>>Cash on delivery</option>
                        <option value="bkash" <?= ($old['payment_method'] ?? '') === 'bkash' ? 'selected' : '' ?>>bKash</option>
                        <option value="nagad" <?= ($old['payment_method'] ?? '') === 'nagad' ? 'selected' : '' ?>>Nagad</option>
                    </select>
                </label>
            </div>
        </section>
    </div>

    <aside class="cart-summary">
        <h2>Summary</h2>
        <?php if (!empty($quote)): ?>
            <div><span>Subtotal</span><strong><?= e(taka($quote['subtotal'])) ?></strong></div>
            <div><span>Discount</span><strong><?= e(taka($quote['discount'])) ?></strong></div>
            <div><span>Delivery</span><strong><?= e(taka($quote['shipping'])) ?></strong></div>
            <div class="grand"><span>Total</span><strong><?= e(taka($quote['total'])) ?></strong></div>
            <p>Calculated on the server from current prices.</p>
        <?php else: ?>
            <p>Delivery is calculated from the address. Cash on delivery stays unpaid until the shop records collection.</p>
        <?php endif; ?>
        <details class="coupon">
            <summary>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V8Z"/></svg>
                Coupon code, if you have one
            </summary>
            <input name="coupon_code" maxlength="40" value="<?= e($coupon) ?>" placeholder="SAVE10">
        </details>
        <button type="submit" name="action" value="place" data-submit>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
            Place order
        </button>
        <button type="submit" name="action" value="preview" class="secondary">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
            Calculate total
        </button>
        <a class="text-link" href="<?= e(url('/cart')) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            Back to cart
        </a>
    </aside>
</form>
