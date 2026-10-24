<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($snapshot['invoice_no'] ?? 'Invoice') ?></title>
    <style>
        body { margin: 0; font-family: "Segoe UI", sans-serif; color: #1d2421; background: #f6f3ec; }
        .sheet { max-width: 760px; margin: 24px auto; background: #fff; padding: 32px; }
        h1 { margin: 0; font-size: 28px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 8px 0; border-bottom: 1px solid #e4ddd0; }
        .totals { margin-top: 16px; width: 280px; margin-left: auto; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals strong { font-size: 18px; }
        .muted { color: #5c675f; }
        .actions { margin-bottom: 16px; }
        button, a.button { background: #0f6e56; color: #fff; border: 0; border-radius: 8px; padding: 8px 14px; text-decoration: none; }
        @media print { .actions { display: none; } body { background: #fff; } .sheet { margin: 0; } }
    </style>
</head>
<body>
<?= $content ?>
</body>
</html>
