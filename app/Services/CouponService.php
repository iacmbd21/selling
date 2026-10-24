<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Money;
use App\Exceptions\DomainException;
use App\Models\Coupon as CouponModel;

class CouponService
{
    public function __construct(private CouponModel $coupons = new CouponModel())
    {
    }

    public function check(string $code, string $subtotal, bool $lock = false, ?int $userId = null, ?string $mobile = null): array
    {
        $coupon = $this->coupons->findByCode($code, $lock);
        if (!$coupon || $coupon['status'] !== 'active') {
            throw new DomainException('Invalid coupon.');
        }
        $now = date('Y-m-d H:i:s');
        if (!empty($coupon['starts_at']) && $coupon['starts_at'] > $now) {
            throw new DomainException('Coupon is not active yet.');
        }
        if (!empty($coupon['expires_at']) && $coupon['expires_at'] < $now) {
            throw new DomainException('Coupon has expired.');
        }
        if ($coupon['max_uses'] !== null && (int) $coupon['used_count'] >= (int) $coupon['max_uses']) {
            throw new DomainException('Coupon has already been used the maximum number of times.');
        }
        if (Money::comp($subtotal, (string) $coupon['min_order']) < 0) {
            throw new DomainException('This coupon needs a larger order.');
        }
        if (!empty($coupon['per_customer_limit']) && ($userId || $mobile)) {
            $statement = \App\Core\Database::pdo()->prepare(
                'SELECT COUNT(*) FROM coupon_usages WHERE coupon_id = ? AND (user_id = ? OR customer_mobile = ?)'
            );
            $statement->execute([(int) $coupon['id'], $userId ?: 0, $mobile ?: '']);
            if ((int) $statement->fetchColumn() >= (int) $coupon['per_customer_limit']) {
                throw new DomainException('This coupon has already been used on this customer account.');
            }
        }
        return $coupon;
    }

    public function discount(array $coupon, string $subtotal): string
    {
        if ($coupon['type'] === 'percent') {
            $amount = Money::percent($subtotal, (string) $coupon['value']);
        } else {
            $amount = Money::normalize((string) $coupon['value']);
        }
        if (!empty($coupon['maximum_discount']) && Money::comp($amount, (string) $coupon['maximum_discount']) > 0) {
            $amount = Money::normalize((string) $coupon['maximum_discount']);
        }
        if (Money::comp($amount, $subtotal) > 0) {
            return $subtotal;
        }
        return $amount;
    }
}
