<?php

declare(strict_types=1);

namespace App\Validators;

class PaymentValidator
{
    public function normalizeTransaction(string $transactionId): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($transactionId)) ?? '');
    }

    public function validTransaction(string $transactionId): bool
    {
        return (bool) preg_match('/^[A-Z0-9]{8,12}$/', $transactionId);
    }

    public function validMethod(string $method): bool
    {
        return in_array($method, ['bkash', 'nagad'], true);
    }
}
