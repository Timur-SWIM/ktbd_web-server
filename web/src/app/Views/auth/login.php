<?php use App\Helpers\Security; ?>

<div class="login-panel">
    <div class="login-brand">
        <img class="login-logo" src="<?= e(asset('img/logo-color-no-bg.png')) ?>" alt="Логотип МГТУ">
        <div>
            <h1>CRM / АСУ ТП</h1>
            <p>Вход в систему производственного участка</p>
        </div>
    </div>

    <form method="post" action="/login" class="vstack gap-3">
        <?= Security::csrfField() ?>
        <div>
            <label class="form-label" for="username">Логин</label>
            <input class="form-control form-control-lg" id="username" name="username" value="<?= e(old('username')) ?>" required autofocus>
        </div>
        <div>
            <label class="form-label" for="password">Пароль</label>
            <input class="form-control form-control-lg" id="password" name="password" type="password" required>
        </div>
        <button class="btn btn-primary btn-lg w-100" type="submit">Войти</button>
    </form>

    <div class="login-hint">
        Демо-доступ: <strong>admin</strong> / <strong>password</strong>
    </div>
</div>
