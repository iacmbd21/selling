<?php

declare(strict_types=1);

namespace App\Validators;

class CouponValidator
{
    public function validate(array $input): array
    {
        $errors = [];
        $code = strtoupper(trim((string) ($input['code'] ?? '')));
        if (!preg_match('/^[A-Z0-9]{3,40}$/', $code)) {
            $errors['code'] = 'Enter a coupon code using letters and numbers.';
        }
        if (!in_array($input['type'] ?? '', ['percent', 'fixed'], true)) {
            $errors['type'] = 'Choose percent or fixed.';
        }
        if (!is_numeric($input['value'] ?? null) || (float) $input['value'] <= 0) {
            $errors['value'] = 'Enter a coupon value greater than zero.';
        }
        if (($input['type'] ?? '') === 'percent' && (float) ($input['value'] ?? 0) > 100) {
            $errors['value'] = 'Percent discount cannot exceed 100.';
        }
        return $errors;
    }
}
