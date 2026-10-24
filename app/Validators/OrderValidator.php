<?php

declare(strict_types=1);

namespace App\Validators;

class OrderValidator
{
    public function normalizeMobile(string $mobile): string
    {
        $mobile = preg_replace('/[\s-]/', '', trim($mobile)) ?? '';
        if (str_starts_with($mobile, '+880')) {
            $mobile = '0' . substr($mobile, 4);
        } elseif (str_starts_with($mobile, '880')) {
            $mobile = '0' . substr($mobile, 3);
        }
        return $mobile;
    }

    public function validate(string $name, string $mobile, string $address, array $items): array
    {
        $errors = [];
        if (!preg_match("/^[\\p{L}\\p{M}\\s.'-]{2,120}$/u", $name)) {
            $errors['customer_name'] = 'Enter the customer name.';
        }
        if (!preg_match('/^01[3-9][0-9]{8}$/', $mobile)) {
            $errors['mobile'] = 'Enter a valid mobile number, for example 01712345678.';
        }
        $length = str_length($address);
        if ($length < 10 || $length > 500) {
            $errors['address'] = 'Enter the full delivery address.';
        }
        if ($items === []) {
            $errors['product_id'] = 'Select a product.';
        }
        foreach ($items as $quantity) {
            if ($quantity < 1 || $quantity > 20) {
                $errors['quantity'] = 'Quantity must be between 1 and 20.';
            }
        }
        return $errors;
    }

    /**
     * @return array{items: array<int, int>, errors: array<string, string>}
     */
    public function collect(array $input): array
    {
        $rawItems = [];
        if (!empty($input['items']) && is_array($input['items'])) {
            $rawItems = $input['items'];
        } elseif (!empty($input['product_id'])) {
            $rawItems = [[
                'product_id' => $input['product_id'],
                'quantity' => $input['quantity'] ?? 1,
            ]];
        }

        $merged = [];
        $errors = [];
        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                $errors['product_id'] = 'Select a product.';
                continue;
            }
            $productId = trim((string) ($item['product_id'] ?? ''));
            $quantity = trim((string) ($item['quantity'] ?? '1'));
            if ($productId === '') {
                $errors['product_id'] = 'Select a product.';
                continue;
            }
            if (!preg_match('/^\d+$/', $productId)) {
                $errors['product_id'] = 'Select a product.';
                continue;
            }
            if (!preg_match('/^\d+$/', $quantity)) {
                $errors['quantity'] = 'Quantity must be a whole number.';
                continue;
            }
            $id = (int) $productId;
            $merged[$id] = ($merged[$id] ?? 0) + (int) $quantity;
        }

        return ['items' => $merged, 'errors' => $errors];
    }
}
