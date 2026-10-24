<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Money;
use App\Exceptions\DomainException;

class ReportService
{
    public function __construct(
        private OrderService $orders = new OrderService(),
        private InventoryService $inventory = new InventoryService()
    ) {
    }

    public function sales(): array
    {
        return Database::pdo()->query(
            "SELECT DATE(created_at) AS day, COUNT(*) AS orders, COALESCE(SUM(total), 0) AS total
             FROM orders
             WHERE status IN ('pending','confirmed','processing','shipped','delivered')
             GROUP BY DATE(created_at)
             ORDER BY day DESC"
        )->fetchAll();
    }

    public function payments(): array
    {
        return Database::pdo()->query(
            'SELECT method, status, COUNT(*) AS payments, COALESCE(SUM(amount), 0) AS total
             FROM payments
             GROUP BY method, status
             ORDER BY method, status'
        )->fetchAll();
    }

    public function reconcile(): array
    {
        $issues = [];
        $pdo = Database::pdo();
        $orders = $pdo->query('SELECT * FROM orders ORDER BY id')->fetchAll();
        foreach ($orders as $order) {
            $itemsSum = $pdo->prepare('SELECT COALESCE(SUM(line_total), 0) FROM order_items WHERE order_id = ?');
            $itemsSum->execute([(int) $order['id']]);
            $sum = Money::normalize((string) $itemsSum->fetchColumn());
            if (Money::comp($sum, (string) $order['subtotal']) !== 0) {
                $issues[] = $order['order_no'] . ' item totals do not match the saved subtotal.';
            }
            $expected = $this->orders->totals(
                (string) $order['subtotal'],
                (string) $order['discount'],
                (string) $order['shipping'],
                (string) $order['tax']
            );
            if (Money::comp($expected['total'], (string) $order['total']) !== 0) {
                $issues[] = $order['order_no'] . ' saved total does not match the server calculation.';
            }

            $paid = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE order_id = ? AND status = 'verified'");
            $paid->execute([(int) $order['id']]);
            $paidTotal = Money::normalize((string) $paid->fetchColumn());
            if ($order['payment_status'] === 'verified' && Money::comp($paidTotal, (string) $order['total']) !== 0) {
                $issues[] = $order['order_no'] . ' paid amount does not match the order total.';
            }
            if ($order['payment_status'] === 'pending' && Money::comp($paidTotal, '0.00') !== 0) {
                $issues[] = $order['order_no'] . ' is unpaid but has a collected payment.';
            }

            $invoice = $pdo->prepare('SELECT snapshot_json FROM invoices WHERE order_id = ?');
            $invoice->execute([(int) $order['id']]);
            $json = $invoice->fetchColumn();
            if ($json) {
                $snapshot = json_decode((string) $json, true);
                if (!is_array($snapshot) || Money::comp((string) $snapshot['total'], (string) $order['total']) !== 0) {
                    $issues[] = $order['order_no'] . ' invoice total does not match the order.';
                }
            }
        }

        $salesRows = $this->sales();
        $reportSum = '0.00';
        foreach ($salesRows as $row) {
            $reportSum = Money::add($reportSum, (string) $row['total']);
        }
        $sourceSum = Money::normalize((string) $pdo->query(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE status IN ('pending','confirmed','processing','shipped','delivered')"
        )->fetchColumn());
        if (Money::comp($reportSum, $sourceSum) !== 0) {
            $issues[] = 'Sales report total does not match source orders.';
        }

        $paidReport = Money::normalize((string) $pdo->query(
            "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'verified'"
        )->fetchColumn());
        $paidOrders = Money::normalize((string) $pdo->query(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'verified'"
        )->fetchColumn());
        if (Money::comp($paidReport, $paidOrders) !== 0) {
            $issues[] = 'Payment report does not match paid orders.';
        }

        $stock = $this->inventory->reconcile();
        $issues = array_merge($issues, $stock['issues']);

        return [
            'ok' => $issues === [],
            'issues' => $issues,
            'sales' => $salesRows,
            'payments' => $this->payments(),
            'inventory' => $stock['rows'],
            'sales_total' => $sourceSum,
            'paid_total' => $paidOrders,
        ];
    }
}
