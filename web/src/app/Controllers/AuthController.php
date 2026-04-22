<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;

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

    public function logout(): void
    {
        if (!$this->requirePostWithCsrf()) {
            return;
        }

        $_SESSION = [];
        session_destroy();
        $this->redirect('/login');
    }
}
