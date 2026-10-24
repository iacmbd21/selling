<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Models\Payment;
use App\Services\PaymentService;

class PaymentController extends Controller
{
    public function index(): void
    {
        $this->render('admin/payments/index', [
            'title' => 'Payments',
            'pending' => (new Payment())->pending(),
        ], 'layouts/admin');
    }

    public function collect(string $orderId): void
    {
        $this->run(function () use ($orderId): void {
            (new PaymentService())->collectCod((int) $orderId, (int) Auth::id(), (string) ($_POST['amount'] ?? ''));
        }, 'Cash collection recorded. The order is now paid.', '/admin/orders/' . $orderId);
    }

    public function submit(string $orderId): void
    {
        $this->run(function () use ($orderId): void {
            (new PaymentService())->submitMobile(
                (int) $orderId,
                (string) ($_POST['method'] ?? ''),
                (string) ($_POST['transaction_id'] ?? ''),
                (string) ($_POST['sender_number'] ?? ''),
                (string) ($_POST['amount'] ?? ''),
                (int) Auth::id()
            );
        }, 'Payment submitted. An authorized user must verify it.', '/admin/orders/' . $orderId);
    }

    public function verify(string $paymentId): void
    {
        $payment = (new Payment())->find((int) $paymentId);
        $this->run(function () use ($paymentId): void {
            (new PaymentService())->verify((int) $paymentId, (int) Auth::id());
        }, 'Payment verified.', $payment ? '/admin/orders/' . $payment['order_id'] : '/admin/payments');
    }

    private function run(callable $callback, string $success, ?string $target = null): void
    {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $back = $target ?? ($orderId ? '/admin/orders/' . $orderId : '/admin/payments');
        try {
            $callback();
            Session::flash('success', $success);
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
        }
        redirect($back);
    }
}
