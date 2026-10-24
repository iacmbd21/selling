<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Coupon
{
    public function all(): array
    {
        return Database::pdo()->query('SELECT * FROM coupons ORDER BY code')->fetchAll();
    }

    public function findByCode(string $code, bool $lock = false): ?array
    {
        $sql = 'SELECT * FROM coupons WHERE code = ?' . ($lock ? ' FOR UPDATE' : '');
        $statement = Database::pdo()->prepare($sql);
        $statement->execute([strtoupper(trim($code))]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $statement = Database::pdo()->prepare(
            'INSERT INTO coupons (code, type, value, min_order, max_uses, used_count, starts_at, expires_at, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            strtoupper(trim($data['code'])),
            $data['type'],
            $data['value'],
            $data['min_order'],
            $data['max_uses'],
            $data['used_count'] ?? 0,
            $data['starts_at'],
            $data['expires_at'],
            $data['status'] ?? 'active',
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public function consume(int $id): void
    {
        $statement = Database::pdo()->prepare(
            'UPDATE coupons SET used_count = used_count + 1 WHERE id = ? AND (max_uses IS NULL OR used_count < max_uses)'
        );
        $statement->execute([$id]);
        if ($statement->rowCount() !== 1) {
            throw new \App\Exceptions\DomainException('Coupon has been fully used.');
        }
    }

    public function release(int $id): void
    {
        $statement = Database::pdo()->prepare(
            'UPDATE coupons SET used_count = IF(used_count > 0, used_count - 1, 0) WHERE id = ?'
        );
        $statement->execute([$id]);
    }
}
