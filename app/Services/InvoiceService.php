<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;

class InvoiceService
{
    public function __construct(private Invoice $invoices = new Invoice(), private Order $orders = new Order())
    {
    }

    public function issue(int $orderId, array $snapshot): array
    {
        $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $this->invoices->insert($orderId, $snapshot['invoice_no'], $json);
        return $snapshot;
    }

    public function writeCopy(array $snapshot): void
    {
        $this->writeFile($snapshot['invoice_no'], $snapshot);
    }

    public function findByOrder(int $orderId): ?array
    {
        $invoice = $this->invoices->findByOrder($orderId);
        return $invoice ? $this->hydrate($invoice) : null;
    }

    public function findByNumber(string $invoiceNo): ?array
    {
        $invoice = $this->invoices->findByNumber($invoiceNo);
        return $invoice ? $this->hydrate($invoice) : null;
    }

    public function render(array $invoice): string
    {
        $snapshot = $invoice['snapshot'];
        return \App\Core\View::render('invoices/print', [
            'title' => $snapshot['invoice_no'],
            'snapshot' => $snapshot,
            'order' => $this->orders->find((int) $invoice['order_id']),
        ], 'layouts/print');
    }

    private function hydrate(array $invoice): array
    {
        $invoice['snapshot'] = json_decode((string) $invoice['snapshot_json'], true, 512, JSON_THROW_ON_ERROR);
        return $invoice;
    }

    private function writeFile(string $invoiceNo, array $snapshot): void
    {
        $directory = BASE_PATH . '/storage/invoices';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $safeName = preg_replace('/[^A-Z0-9-]/', '', $invoiceNo) ?: 'invoice';
        file_put_contents(
            $directory . '/' . $safeName . '.html',
            \App\Core\View::render('invoices/print', [
                'title' => $invoiceNo,
                'snapshot' => $snapshot,
                'order' => null,
            ], 'layouts/print')
        );
    }
}
