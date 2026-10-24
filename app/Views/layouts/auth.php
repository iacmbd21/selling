<?php
$success = \App\Core\Session::pull('success');
$error = \App\Core\Session::pull('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Login') ?> · Today Sell</title>
    <link rel="stylesheet" href="<?= e(url('/assets/app.css')) ?>">
</head>
<body class="auth-body">
<main class="auth-card">
    <a class="brand" href="<?= e(url('/')) ?>">Today Sell</a>
    <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
    <?= $content ?>
</main>
</body>
</html>
