<h1>Edit product</h1>
<p><a href="<?= e(url('/admin/products')) ?>">Back to products</a></p>
<?php if (!empty($errors)): ?>
    <div class="alert danger"><?php foreach ($errors as $message): ?><div><?= e($message) ?></div><?php endforeach; ?></div>
<?php endif; ?>
<?php
$editing = $product;
$photos = $photos ?? [];
require BASE_PATH . '/app/Views/admin/products/form.php';
?>
