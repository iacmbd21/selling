<?php

declare(strict_types=1);

namespace App\Validators;

class ProductValidator
{
    public function validate(array $input): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '' || str_length($name) > 190) {
            $errors['name'] = 'Enter a product name.';
        }
        $sku = strtoupper(trim((string) ($input['sku'] ?? '')));
        if (!preg_match('/^[A-Z0-9-]{2,64}$/', $sku)) {
            $errors['sku'] = 'Enter a SKU using letters, numbers, and dashes.';
        }
        if (!ctype_digit((string) ($input['category_id'] ?? ''))) {
            $errors['category_id'] = 'Choose a category.';
        }
        if (!is_numeric($input['price'] ?? null) || (float) $input['price'] < 0) {
            $errors['price'] = 'Enter a valid price.';
        }
        if (isset($input['stock']) && (!ctype_digit((string) $input['stock']) || (int) $input['stock'] > 100000)) {
            $errors['stock'] = 'Enter a valid stock quantity.';
        }
        $unit = strtolower(trim((string) ($input['unit'] ?? 'pc')));
        if (!array_key_exists($unit, product_units())) {
            $errors['unit'] = 'Choose a unit such as pc, kg, or litre.';
        }
        if (!in_array($input['status'] ?? 'active', ['active', 'inactive'], true)) {
            $errors['status'] = 'Choose a status.';
        }
        return $errors;
    }
}
