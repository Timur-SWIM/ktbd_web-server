<?php use App\Helpers\Security; ?>

<div class="page-heading">
    <div>
        <h1><?= e($title) ?></h1>
        <p><?= e($config['title']) ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="/<?= e($entity) ?>">Назад</a>
</div>

<div class="form-shell">
    <div class="alert alert-danger shadow-none" role="alert">
        Файл не импортирован. Исправьте ошибки и загрузите файл повторно.
    </div>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Строка</th>
                <th>Ошибка</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($errors as $error): ?>
                <tr>
                    <td><?= isset($error['line']) && $error['line'] !== null ? e((string) $error['line']) : 'Файл' ?></td>
                    <td><?= e((string) $error['message']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form method="post" action="/<?= e($entity) ?>/import" enctype="multipart/form-data" class="mt-4">
        <?= Security::csrfField() ?>
        <label class="form-label" for="import_file_retry">Загрузить исправленный файл</label>
        <div class="input-group">
            <input class="form-control" id="import_file_retry" name="import_file" type="file" accept=".txt,.xlsx" required>
            <button class="btn btn-primary" type="submit">Загрузить</button>
        </div>
    </form>
</div>
