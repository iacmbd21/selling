<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Money;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Models\Coupon as CouponModel;
use App\Models\Order as OrderModel;
use App\Models\Payment;
use App\Validators\OrderValidator;
use PDOException;

class OrderService
{
    public function __construct(
        private OrderValidator $validator = new OrderValidator(),
        private CouponService $coupons = new CouponService(),
        private ShippingService $shipping = new ShippingService(),
        private InventoryService $inventory = new InventoryService(),
        private InvoiceService $invoices = new InvoiceService(),
        private OrderModel $orders = new OrderModel(),
        private Payment $payments = new Payment(),
        private CouponModel $couponModel = new CouponModel()
    ) {
    }

    public function quote(array $input): array
    {
        return $this->prepare($input, false, \App\Core\Auth::id());
    }

    public function place(array $input, ?int $userId): array
    {
        $key = trim((string) ($input['idempotency_key'] ?? ''));
        if ($key !== '') {
            $existing = $this->orders->findByIdempotency($key);
            if ($existing) {
                $existing['items'] = $this->orders->items((int) $existing['id']);
                return $existing;
            }
        }
        $attempt = 0;
        beginning:
        $attempt++;
        $pdo = Database::pdo();
        try {
            $pdo->beginTransaction();
            $prepared = $this->prepare($input, true, $userId);
            $orderNo = $this->number('TS');
            $invoiceNo = $this->number('INV');
            $orderId = $this->orders->insert([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'customer_name' => $prepared['customer_name'],
                'mobile' => $prepared['mobile'],
                'customer_email' => $prepared['customer_email'],
                'address' => $prepared['address'],
                'city' => $prepared['city'],
                'postal_code' => $prepared['postal_code'],
                'country_code' => $prepared['country_code'],
                'delivery_note' => $prepared['delivery_note'],
                'payment_method' => $prepared['payment_method'],
                'payment_status' => 'pending',
                'subtotal' => $prepared['subtotal'],
                'discount' => $prepared['discount'],
                'shipping' => $prepared['shipping'],
                'tax' => $prepared['tax'],
                'total' => $prepared['total'],
                'coupon_id' => $prepared['coupon']['id'] ?? null,
                'coupon_code' => $prepared['coupon']['code'] ?? null,
                'idempotency_key' => $key,
            ]);
            foreach ($prepared['lines'] as $line) {
                $this->orders->insertItem($line + ['order_id' => $orderId]);
                $this->inventory->apply(
                    (int) $line['product_id'],
                    -1 * (int) $line['quantity'],
                    'sale',
                    $orderId,
                    'Sale ' . $orderNo
                );
            }
            if ($prepared['coupon']) {
                $this->couponModel->consume((int) $prepared['coupon']['id']);
                Database::pdo()->prepare(
                    'INSERT INTO coupon_usages (coupon_id, order_id, user_id, customer_mobile, discount_amount) VALUES (?, ?, ?, ?, ?)'
                )->execute([
                    (int) $prepared['coupon']['id'],
                    $orderId,
                    $userId,
                    $prepared['mobile'],
                    $prepared['discount'],
                ]);
            }
            $this->payments->insert([
                'order_id' => $orderId,
                'method' => $prepared['payment_method'],
                'amount' => $prepared['total'],
                'status' => 'pending',
                'note' => $prepared['payment_method'] === 'cod'
                    ? 'Cash on delivery is unpaid until collection is recorded'
                    : 'Waiting for a transaction reference',
            ]);
            $snapshot = [
                'invoice_no' => $invoiceNo,
                'order_no' => $orderNo,
                'customer_name' => $prepared['customer_name'],
                'mobile' => $prepared['mobile'],
                'address' => $prepared['address'],
                'city' => $prepared['city'],
                'country_code' => $prepared['country_code'],
                'items' => $prepared['lines'],
                'subtotal' => $prepared['subtotal'],
                'discount' => $prepared['discount'],
                'shipping' => $prepared['shipping'],
                'tax' => $prepared['tax'],
                'total' => $prepared['total'],
                'coupon_code' => $prepared['coupon']['code'] ?? null,
                'payment_method' => $prepared['payment_method'],
                'payment_status' => 'pending',
                'issued_at' => date('Y-m-d H:i:s'),
            ];
            $this->invoices->issue($orderId, $snapshot);
            $pdo->commit();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $driverCode = (int) ($exception->errorInfo[1] ?? 0);
            if ($attempt < 3 && in_array($driverCode, [1062, 1205, 1213], true)) {
                goto beginning;
            }
            throw $exception;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        try {
            $this->invoices->writeCopy($snapshot);
        } catch (\Throwable) {
            // The database snapshot remains the invoice of record.
        }
        $order = $this->orders->find($orderId);
        $order['items'] = $this->orders->items($orderId);
        $order['invoice_no'] = $invoiceNo;
        return $order;
    }

    public function confirm(int $orderId): void
    {
        $this->transition($orderId, 'pending', 'confirmed');
    }

    public function process(int $orderId): void
    {
        $this->transition($orderId, 'confirmed', 'processing');
    }

    public function ship(int $orderId, string $courier = '', string $tracking = ''): void
    {
        $this->transition($orderId, 'processing', 'shipped');
        Database::pdo()->prepare(
            'INSERT INTO shipments (order_id, courier_name, tracking_number, status, shipped_at) VALUES (?, ?, ?, ?, NOW())'
        )->execute([$orderId, $courier !== '' ? $courier : null, $tracking !== '' ? $tracking : null, 'shipped']);
    }

    public function deliver(int $orderId): void
    {
        $this->transition($orderId, 'shipped', 'delivered');
        Database::pdo()->prepare(
            "UPDATE shipments SET status = 'delivered', delivered_at = NOW() WHERE order_id = ? ORDER BY id DESC LIMIT 1"
        )->execute([$orderId]);
    }

    public function cancel(int $orderId): void
    {
        $this->restore($orderId, ['pending', 'confirmed', 'processing'], 'cancelled', 'cancel_restore', 'Cancelled');
    }

    public function returnOrder(int $orderId): void
    {
        $this->restore($orderId, ['delivered'], 'returned', 'return_restore', 'Returned');
    }

    public function totals(string $subtotal, string $discount, string $shipping, string $tax = '0.00'): array
    {
        $total = Money::sub(Money::add($subtotal, $shipping, $tax), $discount);
        if (Money::comp($total, '0.00') < 0) {
            $total = '0.00';
        }
        return [
            'subtotal' => Money::normalize($subtotal),
            'discount' => Money::normalize($discount),
            'shipping' => Money::normalize($shipping),
            'tax' => Money::normalize($tax),
            'total' => $total,
        ];
    }

    private function prepare(array $input, bool $lock, ?int $userId = null): array
    {
        $collected = $this->validator->collect($input);
        $name = trim((string) ($input['customer_name'] ?? ''));
        $mobile = $this->validator->normalizeMobile((string) ($input['mobile'] ?? ''));
        $address = trim((string) ($input['address'] ?? ''));
        $errors = $collected['errors'] + $this->validator->validate($name, $mobile, $address, $collected['items']);
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $email = strtolower(trim((string) ($input['customer_email'] ?? '')));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['customer_email'] = 'Enter a valid email address.';
        }
        $country = $this->shipping->country((string) ($input['country_code'] ?? 'BD'));
        $city = trim((string) ($input['city'] ?? ''));
        $postal = trim((string) ($input['postal_code'] ?? ''));
        $note = trim((string) ($input['delivery_note'] ?? ''));
        $method = strtolower(trim((string) ($input['payment_method'] ?? 'cod')));
        if (!in_array($method, ['cod', 'bkash', 'nagad'], true)) {
            $errors['payment_method'] = 'Choose a payment method.';
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        if (!in_array($method, $this->shipping->methods($address, $country, $city), true)) {
            throw new DomainException('That payment method is not available for this destination.');
        }

        $priced = $this->pricedLines($collected['items'], $lock);
        $coupon = null;
        $discount = '0.00';
        $code = strtoupper(trim((string) ($input['coupon_code'] ?? '')));
        if ($code !== '') {
            $coupon = $this->coupons->check($code, $priced['subtotal'], $lock, $userId, $mobile);
            $discount = $this->coupons->discount($coupon, $priced['subtotal']);
        }
        $shipping = $this->shipping->quote($address, $priced['subtotal'], $country, $city);
        $totals = $this->totals($priced['subtotal'], $discount, $shipping, '0.00');

        return $totals + [
            'customer_name' => preg_replace('/\s+/', ' ', $name) ?? $name,
            'mobile' => $mobile,
            'customer_email' => $email !== '' ? $email : null,
            'address' => $address,
            'city' => $city !== '' ? $city : null,
            'postal_code' => $postal !== '' ? $postal : null,
            'country_code' => $country,
            'delivery_note' => $note !== '' ? $note : null,
            'payment_method' => $method,
            'lines' => $priced['lines'],
            'coupon' => $coupon,
        ];
    }

    private function pricedLines(array $quantities, bool $lock): array
    {
        $ids = array_keys($quantities);
        sort($ids);
        $lines = [];
        $subtotal = '0.00';
        $products = new \App\Models\Product();
        foreach ($ids as $id) {
            $product = $lock ? $products->lock((int) $id) : $products->find((int) $id);
            if (!$product || $product['status'] !== 'active') {
                throw new DomainException('Select an active product.');
            }
            $quantity = $quantities[$id];
            if ((int) $product['stock'] < $quantity) {
                throw new DomainException('Insufficient stock for ' . $product['name'] . '.');
            }
            $unit = Money::normalize((string) $product['price']);
            $lineTotal = Money::mul($unit, $quantity);
            $subtotal = Money::add($subtotal, $lineTotal);
            $lines[] = [
                'product_id' => (int) $product['id'],
                'product_name' => $product['name'],
                'sku' => $product['sku'],
                'unit_price' => $unit,
                'unit' => unit_code($product['unit'] ?? 'pc'),
                'quantity' => $quantity,
                'line_total' => $lineTotal,
            ];
        }
        return ['lines' => $lines, 'subtotal' => $subtotal];
    }

    private function transition(int $orderId, string $from, string $to): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $order = $this->orders->lock($orderId);
            if (!$order || $order['status'] !== $from) {
                throw new DomainException('That order cannot be updated.');
            }
            $pdo->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$to, $orderId]);
            $pdo->prepare(
                'INSERT INTO order_status_history (order_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)'
            )->execute([$orderId, $from, $to, \App\Core\Auth::id()]);
            (new AuditService())->write(\App\Core\Auth::id(), 'order.status', 'order', $orderId, ['from' => $from, 'to' => $to]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function restore(int $orderId, array $from, string $status, string $movement, string $note): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $order = $this->orders->lock($orderId);
            if (!$order || !in_array($order['status'], $from, true)) {
                throw new DomainException('That order cannot be updated.');
            }
            if ($this->inventory->alreadyRestored($orderId, $movement)) {
                throw new DomainException('Stock was already restored for this order.');
            }
            $items = $this->orders->items($orderId);
            $ordered = $items;
            usort($ordered, static fn (array $a, array $b): int => ((int) $a['product_id']) <=> ((int) $b['product_id']));
            foreach ($ordered as $item) {
                $this->inventory->apply(
                    (int) $item['product_id'],
                    (int) $item['quantity'],
                    $movement,
                    $orderId,
                    $note . ' ' . $order['order_no']
                );
            }
            $paymentStatus = match ($order['payment_status']) {
                'verified' => 'refunded',
                'submitted' => 'failed',
                default => $order['payment_status'],
            };
            if (in_array($order['payment_status'], ['verified', 'submitted'], true)) {
                $pdo->prepare(
                    "UPDATE payments SET status = 'failed', note = ? WHERE order_id = ? AND status IN ('verified','submitted')"
                )->execute([$note, $orderId]);
            }
            $pdo->prepare('UPDATE orders SET status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$status, $paymentStatus, $orderId]);
            if (!empty($order['coupon_id'])) {
                $this->couponModel->release((int) $order['coupon_id']);
            }
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function number(string $prefix): string
    {
        return $prefix . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
    }
}
