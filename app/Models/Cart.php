<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Cart
{
    public function findFor(?int $userId, string $sessionId): ?array
    {
        if ($userId) {
            $statement = Database::pdo()->prepare('SELECT * FROM carts WHERE user_id = ? ORDER BY id DESC LIMIT 1');
            $statement->execute([$userId]);
        } else {
            $statement = Database::pdo()->prepare('SELECT * FROM carts WHERE session_id = ? ORDER BY id DESC LIMIT 1');
            $statement->execute([$sessionId]);
        }
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(?int $userId, string $sessionId): int
    {
        $statement = Database::pdo()->prepare('INSERT INTO carts (user_id, session_id) VALUES (?, ?)');
        $statement->execute([$userId, $userId ? null : $sessionId]);
        return (int) Database::pdo()->lastInsertId();
    }

    public function items(int $cartId): array
    {
        $statement = Database::pdo()->prepare(
            'SELECT ci.quantity, p.id, p.name, p.price, p.stock, p.unit, p.status, p.sku, p.slug, p.image_path
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = ?
             ORDER BY ci.id'
        );
        $statement->execute([$cartId]);
        return $statement->fetchAll();
    }

    public function addItem(int $cartId, int $productId, int $quantity): void
    {
        $existing = Database::pdo()->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?');
        $existing->execute([$cartId, $productId]);
        $row = $existing->fetch();
        if ($row) {
            $update = Database::pdo()->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?');
            $update->execute([(int) $row['quantity'] + $quantity, (int) $row['id']]);
            return;
        }
        $insert = Database::pdo()->prepare('INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)');
        $insert->execute([$cartId, $productId, $quantity]);
    }

    public function setQuantity(int $cartId, int $productId, int $quantity): void
    {
        if ($quantity < 1) {
            $this->removeItem($cartId, $productId);
            return;
        }
        $statement = Database::pdo()->prepare('UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND product_id = ?');
        $statement->execute([$quantity, $cartId, $productId]);
    }

    public function removeItem(int $cartId, int $productId): void
    {
        $statement = Database::pdo()->prepare('DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?');
        $statement->execute([$cartId, $productId]);
    }

    public function clear(int $cartId): void
    {
        $statement = Database::pdo()->prepare('DELETE FROM cart_items WHERE cart_id = ?');
        $statement->execute([$cartId]);
    }
}
