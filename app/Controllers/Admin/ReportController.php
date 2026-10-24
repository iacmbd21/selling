<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function index(): void
    {
        $report = (new ReportService())->reconcile();
        $this->render('admin/reports/index', [
            'title' => 'Reports',
            'report' => $report,
        ], 'layouts/admin');
    }
}
