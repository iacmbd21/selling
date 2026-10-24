<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Gate;
use App\Core\Money;
use App\Exceptions\DomainException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Validators\OrderValidator;
use App\Validators\PaymentValidator;

class PaymentService
{
    public function __construct(
        private Order $orders = new Order(),
        private Payment $payments = new Payment(),
        private User $users = new User(),
        private PaymentValidator $validator = new PaymentValidator(),
        private OrderValidator $mobiles = new OrderValidator()
    ) {
    }

    public function collectCod(int $orderId, int $staffId, ?string $amount = null): void
    {
        $actor = $this->actor($staffId, 'payments.collect');
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $order = $this->orders->lock($orderId);
            if (!$order) {
                throw new DomainException('Order was not found.');
            }
            if ($order['payment_method'] !== 'cod') {
                throw new DomainException('This order is not cash on delivery.');
            }
            if ($order['payment_status'] !== 'pending') {
                throw new DomainException('Cash on delivery is not waiting for collection.');
            }
            if (in_array($order['status'], ['cancelled', 'returned'], true)) {
                throw new DomainException('This order cannot be collected.');
            }
            $this->assertAmount($amount, (string) $order['total']);
            $existing = $pdo->prepare(
                "SELECT id FROM payments WHERE order_id = ? AND method = 'cod' AND status = 'pending' ORDER BY id LIMIT 1 FOR UPDATE"
            );
            $existing->execute([$orderId]);
            $paymentId = $existing->fetchColumn();
            if ($paymentId) {
                $pdo->prepare(
                    'UPDATE payments SET status = ?, amount = ?, submitted_by = ?, verified_by = ?, note = ?, verified_at = NOW() WHERE id = ?'
                )->execute([
                    'verified',
                    Money::normalize((string) $order['total']),
                    (int) $actor['id'],
                    (int) $actor['id'],
                    'Cash collected',
                    (int) $paymentId,
                ]);
            } else {
                $this->payments->insert([
                    'order_id' => $orderId,
                    'method' => 'cod',
                    'amount' => Money::normalize((string) $order['total']),
                    'status' => 'verified',
                    'submitted_by' => (int) $actor['id'],
                    'verified_by' => (int) $actor['id'],
                    'note' => 'Cash collected',
                    'verified_at' => date('Y-m-d H:i:s'),
                ]);
            }
            $pdo->prepare('UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?')
                ->execute(['verified', $orderId]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function submitMobile(int $orderId, string $method, string $transactionId, string $sender, string $amount, int $staffId): void
    {
        $actor = $this->actor($staffId, 'payments.submit');
        $method = strtolower(trim($method));
        $transactionId = $this->validator->normalizeTransaction($transactionId);
        $sender = $this->mobiles->normalizeMobile($sender);
        if (!$this->validator->validMethod($method)) {
            throw new DomainException('Choose bKash or Nagad.');
        }
        if (!$this->validator->validTransaction($transactionId)) {
            throw new DomainException('Enter a valid transaction ID.');
        }
        if (!preg_match('/^01[3-9][0-9]{8}$/', $sender)) {
            throw new DomainException('Enter the sender mobile number.');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $order = $this->orders->lock($orderId);
            if (!$order) {
                throw new DomainException('Order was not found.');
            }
            if (in_array($order['status'], ['cancelled', 'returned'], true)) {
                throw new DomainException('This order cannot take a payment.');
            }
            if (in_array($order['payment_status'], ['verified', 'submitted'], true)) {
                throw new DomainException('This order already has a payment submission.');
            }
            $this->assertAmount($amount, (string) $order['total']);
            if ($this->payments->activeTransaction($method, $transactionId)) {
                throw new DomainException('That transaction ID has already been used.');
            }
            $pdo->prepare(
                "UPDATE payments SET status = 'failed', note = 'Replaced by a mobile payment submission' WHERE order_id = ? AND status = 'pending'"
            )->execute([$orderId]);
            $this->payments->insert([
                'order_id' => $orderId,
                'method' => $method,
                'amount' => Money::normalize((string) $order['total']),
                'status' => 'submitted',
                'transaction_id' => $transactionId,
                'sender_number' => $sender,
                'submitted_by' => (int) $actor['id'],
                'note' => 'Waiting for authorized verification',
            ]);
            $pdo->prepare('UPDATE orders SET payment_method = ?, payment_status = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$method, 'submitted', $orderId]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function verify(int $paymentId, int $verifierId): void
    {
        $actor = $this->actor($verifierId, 'payments.verify');
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $payment = $this->payments->lock($paymentId);
            if (!$payment) {
                throw new DomainException('Payment was not found.');
            }
            if ($payment['status'] !== 'submitted') {
                throw new DomainException('This payment is not waiting for verification.');
            }
            if (!$this->validator->validMethod((string) $payment['method'])) {
                throw new DomainException('Only bKash and Nagad payments can be verified.');
            }
            if ((int) $payment['submitted_by'] === (int) $actor['id']) {
                throw new DomainException('A different authorized user must verify this payment.');
            }
            if (!$this->validator->validTransaction((string) $payment['transaction_id'])) {
                throw new DomainException('The transaction ID is not valid.');
            }
            if (!preg_match('/^01[3-9][0-9]{8}$/', (string) $payment['sender_number'])) {
                throw new DomainException('The sender number is not valid.');
            }
            $order = $this->orders->lock((int) $payment['order_id']);
            if (!$order) {
                throw new DomainException('Order was not found.');
            }
            if (Money::comp((string) $payment['amount'], (string) $order['total']) !== 0) {
                throw new DomainException('The payment amount does not match the order total.');
            }
            $duplicate = $this->payments->activeTransaction(
                (string) $payment['method'],
                (string) $payment['transaction_id'],
                (int) $payment['id']
            );
            if ($duplicate) {
                throw new DomainException('That transaction ID has already been used.');
            }
            $pdo->prepare(
                'UPDATE payments SET status = ?, verified_by = ?, verified_at = NOW() WHERE id = ?'
            )->execute(['verified', (int) $actor['id'], $paymentId]);
            $pdo->prepare('UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?')
                ->execute(['verified', (int) $order['id']]);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function actor(int $userId, string $permission): array
    {
        $user = $this->users->find($userId);
        if (!$user || $user['status'] !== 'active' || !Gate::allows($user['role'], $permission)) {
            throw new DomainException('You are not allowed to do that.');
        }
        return $user;
    }

    private function assertAmount(?string $amount, string $expected): void
    {
        if ($amount === null || $amount === '') {
            return;
        }
        if (!is_numeric($amount) || Money::comp($amount, $expected) !== 0) {
            throw new DomainException('The amount must match the order total.');
        }
    }
}
