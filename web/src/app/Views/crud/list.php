<?php use App\Helpers\Security; ?>

<div class="page-heading">
    <div>
        <h1><?= e($config['title']) ?></h1>
        <p>Просмотр, создание и редактирование записей</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="/reports/<?= e($entity) ?>">PDF</a>
        <a class="btn btn-primary" href="/<?= e($entity) ?>/create">Создать</a>
    </div>
</div>

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
