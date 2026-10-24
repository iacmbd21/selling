<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Payment
{
    public function forOrder(int $orderId): array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id');
        $statement->execute([$orderId]);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM payments WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function lock(int $id): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM payments WHERE id = ? FOR UPDATE');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function pending(): array
    {
        return Database::pdo()->query(
            "SELECT p.*, o.order_no, o.customer_name, o.total
             FROM payments p
             JOIN orders o ON o.id = p.order_id
             WHERE p.status = 'submitted'
             ORDER BY p.id"
        )->fetchAll();
    }

    public function activeTransaction(string $method, string $transactionId, ?int $exceptId = null): ?array
    {
        $sql = "SELECT * FROM payments
                WHERE method = ? AND transaction_id = ? AND status IN ('submitted','verified')";
        $params = [$method, $transactionId];
        if ($exceptId) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        $statement = Database::pdo()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function insert(array $data): int
    {
        $statement = Database::pdo()->prepare(
            'INSERT INTO payments (order_id, method, amount, status, transaction_id, sender_number, submitted_by, verified_by, note, verified_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $data['order_id'],
            $data['method'],
            $data['amount'],
            $data['status'],
            $data['transaction_id'] ?? null,
            $data['sender_number'] ?? null,
            $data['submitted_by'] ?? null,
            $data['verified_by'] ?? null,
            $data['note'] ?? null,
            $data['verified_at'] ?? null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }
}
