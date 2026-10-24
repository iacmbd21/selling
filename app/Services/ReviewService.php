<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\DomainException;

class ReviewService
{
    public function published(int $productId): array
    {
        $statement = Database::pdo()->prepare(
            "SELECT r.*, u.name FROM product_reviews r JOIN users u ON u.id = r.user_id
             WHERE r.product_id = ? AND r.status = 'published' ORDER BY r.id DESC"
        );
        $statement->execute([$productId]);
        return $statement->fetchAll();
    }

    public function submit(int $userId, int $productId, int $rating, string $comment): void
    {
        if ($rating < 1 || $rating > 5) {
            throw new DomainException('Choose a rating from 1 to 5.');
        }
        $statement = Database::pdo()->prepare(
            "SELECT o.id FROM orders o JOIN order_items i ON i.order_id = o.id
             WHERE o.user_id = ? AND o.status = 'delivered' AND i.product_id = ? LIMIT 1"
        );
        $statement->execute([$userId, $productId]);
        $orderId = $statement->fetchColumn();
        if (!$orderId) {
            throw new DomainException('You can review a product after a delivered order.');
        }
        $comment = trim(strip_tags($comment));
        if (str_length($comment) > 1000) {
            throw new DomainException('The review is too long.');
        }
        try {
            Database::pdo()->prepare(
                'INSERT INTO product_reviews (product_id, user_id, order_id, rating, comment, status) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$productId, $userId, (int) $orderId, $rating, $comment !== '' ? $comment : null, 'pending']);
        } catch (\PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new DomainException('You have already reviewed this product.');
            }
            throw $exception;
        }
    }

    public function pending(): array
    {
        return Database::pdo()->query(
            "SELECT r.*, u.name AS customer_name, p.name AS product_name
             FROM product_reviews r
             JOIN users u ON u.id = r.user_id
             JOIN products p ON p.id = r.product_id
             ORDER BY r.id DESC"
        )->fetchAll();
    }

    public function moderate(int $id, string $status): void
    {
        if (!in_array($status, ['published', 'rejected'], true)) {
            throw new DomainException('Choose a review decision.');
        }
        Database::pdo()->prepare('UPDATE product_reviews SET status = ? WHERE id = ?')->execute([$status, $id]);
    }
}
