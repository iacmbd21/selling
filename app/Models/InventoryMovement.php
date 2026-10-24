<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class InventoryMovement
{
    public function record(?int $orderId, int $productId, string $type, int $quantity, int $stockAfter, string $note): void
    {
        $statement = Database::pdo()->prepare(
            'INSERT INTO inventory_movements (product_id, order_id, type, quantity, stock_after, note)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$productId, $orderId, $type, $quantity, $stockAfter, $note]);
    }

    public function hasType(int $orderId, string $type): bool
    {
        $statement = Database::pdo()->prepare(
            'SELECT id FROM inventory_movements WHERE order_id = ? AND type = ? LIMIT 1'
        );
        $statement->execute([$orderId, $type]);
        return (bool) $statement->fetch();
    }

    public function latestStock(int $productId): ?int
    {
        $statement = Database::pdo()->prepare(
            'SELECT stock_after FROM inventory_movements WHERE product_id = ? ORDER BY id DESC LIMIT 1'
        );
        $statement->execute([$productId]);
        $value = $statement->fetchColumn();
        return $value === false ? null : (int) $value;
    }
}
