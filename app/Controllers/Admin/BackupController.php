<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Services\BackupService;

class BackupController extends Controller
{
    public function index(): void
    {
        $this->render('admin/backup/index', [
            'title' => 'Backup',
            'files' => (new BackupService())->files(),
        ], 'layouts/admin');
    }

    public function create(): void
    {
        $path = (new BackupService())->create();
        Session::flash('success', 'Backup saved: ' . basename($path));
        redirect('/admin/backup');
    }

    public function restore(): void
    {
        try {
            $service = new BackupService();
            $service->restore($service->read((string) ($_POST['file'] ?? '')));
            Session::flash('success', 'Backup restored.');
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
        }
        redirect('/admin/backup');
    }
}
