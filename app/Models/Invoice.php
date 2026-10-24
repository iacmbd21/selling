<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Invoice
{
    public function findByOrder(int $orderId): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM invoices WHERE order_id = ?');
        $statement->execute([$orderId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findByNumber(string $invoiceNo): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM invoices WHERE invoice_no = ?');
        $statement->execute([$invoiceNo]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function insert(int $orderId, string $invoiceNo, string $snapshot): int
    {
        $statement = Database::pdo()->prepare(
            'INSERT INTO invoices (order_id, invoice_no, snapshot_json) VALUES (?, ?, ?)'
        );
        $statement->execute([$orderId, $invoiceNo, $snapshot]);
        return (int) Database::pdo()->lastInsertId();
    }
}
