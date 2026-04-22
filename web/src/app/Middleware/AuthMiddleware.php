<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Repositories\UserRepository;
use RuntimeException;

final class AuthMiddleware
{
    public static function authorize(array $options): bool
    {
        $requiresAuth = $options['auth'] ?? false;
        $roles = $options['roles'] ?? [];

        if (!$requiresAuth && $roles === []) {
            return true;
        }

        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            return false;
        }

        try {
            if (!(new UserRepository())->isActiveUserId((int) $_SESSION['user']['id'])) {
                $_SESSION = [];
                session_destroy();
                header('Location: /login');
                return false;
            }
        } catch (RuntimeException) {
            http_response_code(500);
            echo '<h1>500</h1><p>Не удалось проверить учетную запись.</p>';
            return false;
        }

        if ($roles === []) {
            return true;
        }

        $userRoles = $_SESSION['user']['roles'] ?? [];
        if (in_array('admin', $userRoles, true) || array_intersect($roles, $userRoles) !== []) {
            return true;
        }

        http_response_code(403);
        echo '<h1>403</h1><p>Недостаточно прав для выполнения операции.</p>';
        return false;
    }
}
