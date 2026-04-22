<?php

declare(strict_types=1);

use App\Helpers\Router;

require_once dirname(__DIR__) . '/bootstrap.php';

$router = new Router();
require APP_ROOT . '/routes/web.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
