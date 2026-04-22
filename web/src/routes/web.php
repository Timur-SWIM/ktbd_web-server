<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CrudController;
use App\Controllers\DashboardController;
use App\Controllers\ReportController;
use App\Helpers\Router;
use App\Services\EntityConfig;

/** @var Router $router */
$router->get('/', [DashboardController::class, 'index'], ['auth' => true]);
$router->get('/dashboard', [DashboardController::class, 'index'], ['auth' => true]);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth' => true]);

foreach (EntityConfig::all() as $entity => $config) {
    $options = ['auth' => true, 'roles' => $config['roles'], 'params' => ['entity' => $entity]];
    $router->get('/' . $entity, [CrudController::class, 'index'], $options);
    $router->get('/' . $entity . '/create', [CrudController::class, 'create'], $options);
    $router->post('/' . $entity, [CrudController::class, 'store'], $options);
    $router->get('/' . $entity . '/{id}/edit', [CrudController::class, 'edit'], $options);
    $router->post('/' . $entity . '/{id}', [CrudController::class, 'update'], $options);
    $router->post('/' . $entity . '/{id}/delete', [CrudController::class, 'delete'], $options);
}

$router->get('/reports/{entity}', [ReportController::class, 'entity'], ['auth' => true]);
$router->get('/documents/{id}/pdf', [ReportController::class, 'document'], ['auth' => true]);
