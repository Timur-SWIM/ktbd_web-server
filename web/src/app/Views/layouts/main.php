<?php

use App\Helpers\Security;
use App\Services\EntityConfig;

$messages = flash();
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$user = current_user();
$pageTitle = $title ?? $app['name'];
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
<body class="<?= $user ? 'has-app-shell' : 'has-auth-shell' ?>">
<?php if ($user): ?>
<div class="app-shell">
    <aside class="app-sidebar">
        <a class="sidebar-brand" href="/dashboard" aria-label="АСУ ТП">
            <img class="brand-logo" src="<?= e(asset('img/logo-color-no-bg.png')) ?>" alt="Логотип МГТУ">
            <span>
                <strong>АСУ ТП</strong>
                <small>CRM контур участка</small>
            </span>
        </a>

        <nav class="sidebar-nav" aria-label="Основная навигация">
            <a class="sidebar-link <?= $currentPath === '/dashboard' || $currentPath === '/' ? 'active' : '' ?>" href="/dashboard">
                <span class="sidebar-link-dot"></span>
                Панель
            </a>
                <?php foreach (EntityConfig::all() as $entityName => $entityConfig): ?>
                    <?php
                    $visible = false;
                    foreach ($entityConfig['roles'] as $role) {
                        $visible = $visible || has_role($role);
                    }
                    ?>
                    <?php if ($visible): ?>
                        <a class="sidebar-link <?= str_starts_with($currentPath, '/' . $entityName) ? 'active' : '' ?>" href="/<?= e($entityName) ?>">
                            <span class="sidebar-link-dot"></span>
                            <?= e($entityConfig['title']) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
        </nav>
    </aside>

    <div class="app-workspace">
        <header class="app-header">
            <div class="header-brand">
                <img class="header-logo" src="<?= e(asset('img/logo-color-no-bg.png')) ?>" alt="Логотип МГТУ">
                <div>
                    <span>Рабочее пространство</span>
                    <strong><?= e($pageTitle) ?></strong>
                </div>
            </div>

            <div class="header-actions">
                <span class="user-chip"><?= e($user['full_name']) ?></span>
                <form method="post" action="/logout" class="m-0">
                    <?= Security::csrfField() ?>
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Выйти</button>
                </form>
            </div>
        </header>

        <main class="app-content">
            <?php foreach ($messages as $type => $message): ?>
                <div class="alert alert-<?= $type === 'error' ? 'danger' : e($type) ?> alert-dismissible fade show" role="alert">
                    <?= e($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>

            <?php require $viewFile; ?>
        </main>

        <footer class="app-footer">разработано на ИУ4</footer>
    </div>
</div>
<?php else: ?>
<main class="auth-shell">
    <?php foreach ($messages as $type => $message): ?>
        <div class="alert alert-<?= $type === 'error' ? 'danger' : e($type) ?> alert-dismissible fade show" role="alert">
            <?= e($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <?php require $viewFile; ?>

    <footer class="auth-footer">разработано на ИУ4</footer>
</main>
<?php endif; ?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
