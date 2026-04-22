<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Security;

abstract class BaseController
{
    protected function render(string $view, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        $app = require APP_ROOT . '/config/app.php';
        $viewFile = APP_ROOT . '/app/Views/' . $view . '.php';

        if (!is_file($viewFile)) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        extract($data, EXTR_SKIP);
        require APP_ROOT . '/app/Views/layouts/main.php';
        clear_old_input();
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $path);
    }

    protected function requirePostWithCsrf(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Method Not Allowed';
            return false;
        }

        if (!Security::verifyCsrf($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            echo 'CSRF token mismatch.';
            return false;
        }

        return true;
    }
}
