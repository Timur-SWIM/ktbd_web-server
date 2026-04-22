<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\DashboardRepository;

final class DashboardController extends BaseController
{
    public function index(): void
    {
        $metrics = (new DashboardRepository())->metrics();
        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'metrics' => $metrics,
        ]);
    }
}
