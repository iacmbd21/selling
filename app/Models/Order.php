<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Order
{
    public function find(int $id): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM orders WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function lock(int $id): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findByIdempotency(string $key): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM orders WHERE idempotency_key = ?');
        $statement->execute([$key]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findByNumberAndMobile(string $orderNo, string $mobile): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM orders WHERE order_no = ? AND mobile = ?');
        $statement->execute([$orderNo, $mobile]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findByNumber(string $orderNo): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM orders WHERE order_no = ?');
        $statement->execute([$orderNo]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function items(int $orderId): array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
        $statement->execute([$orderId]);
        return $statement->fetchAll();
    }

    public function search(array $filters = []): array
    {
        $sql = 'SELECT * FROM orders WHERE 1 = 1';
        $params = [];
        if (($filters['status'] ?? '') !== '') {
            $sql .= ' AND status = ?';
            $params[] = $filters['status'];
        }
        if (($filters['payment_status'] ?? '') !== '') {
            $sql .= ' AND payment_status = ?';
            $params[] = $filters['payment_status'];
        }
        if (($filters['q'] ?? '') !== '') {
            $sql .= ' AND (order_no LIKE ? OR customer_name LIKE ? OR mobile LIKE ?)';
            $like = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $sql .= ' ORDER BY id DESC';
        $statement = Database::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function forUser(int $userId): array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }

    public function insert(array $data): int
    {
        $statement = Database::pdo()->prepare(
            'INSERT INTO orders (
                order_no, user_id, customer_name, mobile, customer_email, address, city, postal_code, country_code,
                delivery_note, status, payment_method, payment_status, currency,
                subtotal, discount, shipping, tax, total, coupon_id, coupon_code, idempotency_key
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $data['order_no'],
            $data['user_id'],
            $data['customer_name'],
            $data['mobile'],
            $data['customer_email'] ?? null,
            $data['address'],
            $data['city'] ?? null,
            $data['postal_code'] ?? null,
            $data['country_code'] ?? 'BD',
            $data['delivery_note'] ?? null,
            'pending',
            $data['payment_method'] ?? 'cod',
            $data['payment_status'] ?? 'pending',
            'BDT',
            $data['subtotal'],
            $data['discount'],
            $data['shipping'],
            $data['tax'],
            $data['total'],
            $data['coupon_id'],
            $data['coupon_code'],
            ($data['idempotency_key'] ?? '') !== '' ? $data['idempotency_key'] : null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public function insertItem(array $item): void
    {
        $statement = Database::pdo()->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, sku, unit_price, quantity, unit, line_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $item['order_id'],
            $item['product_id'],
            $item['product_name'],
            $item['sku'],
            $item['unit_price'],
            $item['quantity'],
            unit_code($item['unit'] ?? 'pc'),
            $item['line_total'],
        ]);
    }
}
