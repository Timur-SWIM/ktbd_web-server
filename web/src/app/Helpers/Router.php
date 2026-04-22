<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Middleware\AuthMiddleware;
use Throwable;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, array $options = []): void
    {
        $this->add('GET', $path, $handler, $options);
    }

    public function post(string $path, array $handler, array $options = []): void
    {
        $this->add('POST', $path, $handler, $options);
    }

    public function add(string $method, string $path, array $handler, array $options = []): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'pattern' => $this->compilePath($path),
            'handler' => $handler,
            'options' => $options,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $method = strtoupper($_POST['_method'] ?? $method);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $params[$key] = $value;
                }
            }
            $params = array_merge($route['options']['params'] ?? [], $params);

            if (!AuthMiddleware::authorize($route['options'])) {
                return;
            }

            try {
                [$class, $action] = $route['handler'];
                $controller = new $class();
                $controller->{$action}(...array_values($params));
            } catch (Throwable $exception) {
                http_response_code(500);
                error_log($exception);
                $message = env('APP_DEBUG', 'false') === 'true'
                    ? $exception->getMessage()
                    : 'Внутренняя ошибка приложения';
                echo '<h1>500</h1><p>' . e($message) . '</p>';
            }

            return;
        }

        http_response_code(404);
        echo '<h1>404</h1><p>Страница не найдена</p>';
    }

    private function compilePath(string $path): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}
