<?php use App\Helpers\Security; ?>

<div class="page-heading">
    <div>
        <h1><?= e($title) ?></h1>
        <p><?= e($config['title']) ?></p>
    </div>
    <a class="btn btn-outline-secondary" href="/<?= e($entity) ?>">Назад</a>
</div>

<form method="post" action="<?= e($action) ?>" class="form-shell">
    <?= Security::csrfField() ?>

    <div class="row g-3">
        <?php foreach ($config['fields'] as $name => $field): ?>
            <?php
            $type = $field['type'] ?? 'text';
            $value = old($name, $item[$name] ?? '');
            $required = ($field['required'] ?? false) ? 'required' : '';
            $invalid = isset($errors[$name]) ? 'is-invalid' : '';
            ?>
            <div class="<?= $type === 'textarea' || $type === 'multiselect' ? 'col-12' : 'col-md-6' ?>">
                <label class="form-label" for="<?= e($name) ?>"><?= e($field['label']) ?></label>

                <?php if ($type === 'select'): ?>
                    <select class="form-select <?= e($invalid) ?>" id="<?= e($name) ?>" name="<?= e($name) ?>" <?= $required ?>>
                        <option value="">Выберите значение</option>
                        <?php foreach (($field['options'] ?? $options[$name] ?? []) as $optionValue => $optionLabel): ?>
                            <?php
                            if (is_array($optionLabel)) {
                                $optionValue = $optionLabel['id'];
                                $optionLabel = $optionLabel['label'];
                            }
                            ?>
                            <option value="<?= e((string) $optionValue) ?>" <?= (string) $value === (string) $optionValue ? 'selected' : '' ?>>
                                <?= e((string) $optionLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'multiselect'): ?>
                    <?php $selected = array_map('strval', (array) $value); ?>
                    <select class="form-select <?= e($invalid) ?>" id="<?= e($name) ?>" name="<?= e($name) ?>[]" multiple size="5">
                        <?php foreach (($options[$name] ?? []) as $option): ?>
                            <option value="<?= e((string) $option['id']) ?>" <?= in_array((string) $option['id'], $selected, true) ? 'selected' : '' ?>>
                                <?= e($option['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'textarea'): ?>
                    <textarea class="form-control <?= e($invalid) ?>" id="<?= e($name) ?>" name="<?= e($name) ?>" rows="6" <?= $required ?>><?= e((string) $value) ?></textarea>
                <?php else: ?>
                    <input class="form-control <?= e($invalid) ?>" id="<?= e($name) ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" value="<?= e((string) $value) ?>" <?= $required ?>>
                <?php endif; ?>

                <?php if (isset($errors[$name])): ?>
                    <div class="invalid-feedback"><?= e($errors[$name]) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Сохранить</button>
        <a class="btn btn-outline-secondary" href="/<?= e($entity) ?>">Отмена</a>
    </div>
</form>
