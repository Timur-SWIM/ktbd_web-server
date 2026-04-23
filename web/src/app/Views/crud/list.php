<?php use App\Helpers\Security; ?>

<div class="page-heading">
    <div>
        <h1><?= e($config['title']) ?></h1>
        <p>Просмотр, создание и редактирование записей</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="/reports/<?= e($entity) ?>">PDF</a>
        <?php if (isset($config['import'])): ?>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createChoiceModal">
                Создать
            </button>
        <?php else: ?>
            <a class="btn btn-primary" href="/<?= e($entity) ?>/create">Создать</a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($config['import'])): ?>
    <div class="modal fade" id="createChoiceModal" tabindex="-1" aria-labelledby="createChoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="createChoiceModalLabel">Создать: <?= e($config['singular']) ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="d-grid gap-3">
                        <a class="btn btn-primary" href="/<?= e($entity) ?>/create">Вручную</a>
                        <form method="post" action="/<?= e($entity) ?>/import" enctype="multipart/form-data" class="import-file-form">
                            <?= Security::csrfField() ?>
                            <label class="form-label" for="import_file">Из файла (.txt или .xlsx)</label>
                            <div class="input-group">
                                <input class="form-control" id="import_file" name="import_file" type="file" accept=".txt,.xlsx" required>
                                <button class="btn btn-outline-primary" type="submit">Загрузить</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="table-shell">
    <table class="table table-striped table-hover align-middle data-table">
        <thead>
        <tr>
            <?php foreach ($config['list'] as $column): ?>
                <th><?= e($config['fields'][$column]['label'] ?? match ($column) {
                    'id' => 'ID',
                    'staff_name' => 'Ответственный',
                    default => ucfirst(str_replace('_', ' ', $column)),
                }) ?></th>
            <?php endforeach; ?>
            <th class="text-end">Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($config['list'] as $column): ?>
                    <td><?= e((string) ($row[$column] ?? '')) ?></td>
                <?php endforeach; ?>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <?php if ($entity === 'documents'): ?>
                            <a class="btn btn-outline-secondary" href="/documents/<?= e((string) $row['id']) ?>/pdf">PDF</a>
                        <?php endif; ?>
                        <a class="btn btn-outline-primary" href="/<?= e($entity) ?>/<?= e((string) $row['id']) ?>/edit">Изменить</a>
                        <?php if (has_role('admin')): ?>
                            <form method="post" action="/<?= e($entity) ?>/<?= e((string) $row['id']) ?>/delete" onsubmit="return confirm('Удалить запись?')">
                                <?= Security::csrfField() ?>
                                <button class="btn btn-outline-danger" type="submit">Удалить</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
