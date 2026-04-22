<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;
use RuntimeException;

final class AuthController extends BaseController
{
    public function showLogin(): void
    {
        if (current_user()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('auth/login', ['title' => 'Вход']);
    }

    public function showRegister(): void
    {
        if (current_user()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('auth/register', [
            'title' => 'Регистрация',
            'errors' => [],
        ]);
    }

    public function login(): void
    {
        if (!$this->requirePostWithCsrf()) {
            return;
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $_SESSION['old'] = ['username' => $username];

        $repository = new UserRepository();
        $user = $repository->findActiveByUsername($username);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            flash('error', 'Неверный логин или пароль.');
            $this->redirect('/login');
            return;
        }

        if (!$repository->isActiveUserId((int) $user['id'])) {
            $repository->deleteAccount((int) $user['id']);
            flash('error', 'Учетная запись удалена или заблокирована.');
            $this->redirect('/login');
            return;
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'roles' => $repository->rolesForUser((int) $user['id']),
        ];

        unset($_SESSION['old']);
        $this->redirect('/dashboard');
    }

    public function register(): void
    {
        if (!$this->requirePostWithCsrf()) {
            return;
        }

        $data = [
            'username' => trim((string) ($_POST['username'] ?? '')),
            'password' => (string) ($_POST['password'] ?? ''),
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'position' => trim((string) ($_POST['position'] ?? '')),
            'department' => trim((string) ($_POST['department'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
        ];

        $_SESSION['old'] = [
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'position' => $data['position'],
            'department' => $data['department'],
            'phone' => $data['phone'],
        ];

        $errors = $this->validateRegistration($data);
        $repository = new UserRepository();

        if (!isset($errors['username']) && $repository->usernameExists($data['username'])) {
            $errors['username'] = 'Пользователь с таким логином уже существует.';
        }

        if ($errors !== []) {
            $this->render('auth/register', [
                'title' => 'Регистрация',
                'errors' => $errors,
            ], 422);
            return;
        }

        try {
            $repository->createWithStaffProfile([
                'username' => $data['username'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'position' => $data['position'],
                'department' => $data['department'],
                'phone' => $data['phone'],
            ]);
        } catch (RuntimeException) {
            flash('error', 'Не удалось создать пользователя. Попробуйте еще раз.');
            $this->redirect('/register');
            return;
        }

        unset($_SESSION['old']);
        flash('success', 'Пользователь создан. Теперь можно войти.');
        $this->redirect('/login');
    }

    public function logout(): void
    {
        if (!$this->requirePostWithCsrf()) {
            return;
        }

        $_SESSION = [];
        session_destroy();
        $this->redirect('/login');
    }

    private function validateRegistration(array $data): array
    {
        $errors = [];

        if ($data['username'] === '') {
            $errors['username'] = 'Укажите логин.';
        } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $data['username'])) {
            $errors['username'] = 'Логин должен быть от 3 символов: латиница, цифры, точка, дефис или подчеркивание.';
        }

        if ($data['password'] === '') {
            $errors['password'] = 'Укажите пароль.';
        } elseif (strlen($data['password']) < 6) {
            $errors['password'] = 'Пароль должен быть не короче 6 символов.';
        }

        if ($data['full_name'] === '') {
            $errors['full_name'] = 'Укажите ФИО.';
        }

        if ($data['email'] === '') {
            $errors['email'] = 'Укажите почту.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Укажите корректную почту.';
        }

        foreach (['position' => 'должность', 'department' => 'подразделение', 'phone' => 'телефон'] as $field => $label) {
            if ($data[$field] === '') {
                $errors[$field] = 'Укажите ' . $label . '.';
            }
        }

        return $errors;
    }
}
