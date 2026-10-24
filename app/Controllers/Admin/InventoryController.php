<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Services\InventoryService;

class InventoryController extends Controller
{
    public function index(): void
    {
        $report = (new InventoryService())->reconcile();
        $this->render('admin/inventory/index', [
            'title' => 'Inventory',
            'rows' => $report['rows'],
            'issues' => $report['issues'],
        ], 'layouts/admin');
    }

    public function adjust(): void
    {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $newStock = (string) ($_POST['stock'] ?? '');
        try {
            if (!ctype_digit($newStock)) {
                throw new DomainException('Enter the new stock as a whole number.');
            }
            $pdo = \App\Core\Database::pdo();
            $pdo->beginTransaction();
            try {
                $current = (new \App\Models\Product())->lock($productId);
                if (!$current) {
                    throw new DomainException('Product was not found.');
                }
                $delta = (int) $newStock - (int) $current['stock'];
                if ($delta !== 0) {
                    (new InventoryService())->apply($productId, $delta, 'adjustment', null, 'Manual stock adjustment');
                }
                $pdo->commit();
            } catch (\Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $exception;
            }
            Session::flash('success', 'Stock updated.');
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
        }
        redirect('/admin/inventory');
    }
}
