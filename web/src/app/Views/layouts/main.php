<?php

use App\Helpers\Security;
use App\Services\EntityConfig;

$messages = flash();
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? $app['name']) ?> | <?= e($app['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<?php if (current_user()): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary border-bottom">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold" href="/dashboard">АСУ ТП</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPath === '/dashboard' || $currentPath === '/' ? 'active' : '' ?>" href="/dashboard">Панель</a>
                </li>
                <?php foreach (EntityConfig::all() as $entityName => $entityConfig): ?>
                    <?php
                    $visible = false;
                    foreach ($entityConfig['roles'] as $role) {
                        $visible = $visible || has_role($role);
                    }
                    ?>
                    <?php if ($visible): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= str_starts_with($currentPath, '/' . $entityName) ? 'active' : '' ?>" href="/<?= e($entityName) ?>">
                                <?= e($entityConfig['title']) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex align-items-center gap-3 text-white">
                <span class="small"><?= e(current_user()['full_name']) ?></span>
                <form method="post" action="/logout" class="m-0">
                    <?= Security::csrfField() ?>
                    <button class="btn btn-outline-light btn-sm" type="submit">Выйти</button>
                </form>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="<?= current_user() ? 'container-fluid py-4' : 'auth-shell' ?>">
    <?php foreach ($messages as $type => $message): ?>
        <div class="alert alert-<?= $type === 'error' ? 'danger' : e($type) ?> alert-dismissible fade show" role="alert">
            <?= e($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <?php require $viewFile; ?>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
