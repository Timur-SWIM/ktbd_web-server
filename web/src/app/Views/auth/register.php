<?php use App\Helpers\Security; ?>

<div class="login-panel auth-panel-wide">
    <div class="login-brand">
        <div>
            <h1>Регистрация пользователя</h1>
            <p>Создание доступа и карточки сотрудника</p>
        </div>
    </div>

    <form method="post" action="/register" class="vstack gap-3">
        <?= Security::csrfField() ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="username">Логин</label>
                <input class="form-control form-control-lg <?= isset($errors['username']) ? 'is-invalid' : '' ?>" id="username" name="username" value="<?= e(old('username')) ?>" required autofocus>
                <?php if (isset($errors['username'])): ?>
                    <div class="invalid-feedback"><?= e($errors['username']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="email">Почта</label>
                <input class="form-control form-control-lg <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" type="email" value="<?= e(old('email')) ?>" required>
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="password">Пароль</label>
                <input class="form-control form-control-lg <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" type="password" required>
                <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="full_name">ФИО</label>
                <input class="form-control form-control-lg <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" id="full_name" name="full_name" value="<?= e(old('full_name')) ?>" required>
                <?php if (isset($errors['full_name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="position">Должность</label>
                <input class="form-control form-control-lg <?= isset($errors['position']) ? 'is-invalid' : '' ?>" id="position" name="position" value="<?= e(old('position')) ?>" required>
                <?php if (isset($errors['position'])): ?>
                    <div class="invalid-feedback"><?= e($errors['position']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <label class="form-label" for="department">Подразделение</label>
                <input class="form-control form-control-lg <?= isset($errors['department']) ? 'is-invalid' : '' ?>" id="department" name="department" value="<?= e(old('department')) ?>" required>
                <?php if (isset($errors['department'])): ?>
                    <div class="invalid-feedback"><?= e($errors['department']) ?></div>
                <?php endif; ?>
            </div>

            <div class="col-12">
                <label class="form-label" for="phone">Телефон</label>
                <input class="form-control form-control-lg <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= e(old('phone')) ?>" required>
                <?php if (isset($errors['phone'])): ?>
                    <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <button class="btn btn-primary btn-lg w-100" type="submit">Создать пользователя</button>
    </form>

    <div class="auth-switch">
        Уже есть доступ? <a href="/login">Войти</a>
    </div>
</div>
