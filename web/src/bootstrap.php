<?php

declare(strict_types=1);

define('APP_ROOT', __DIR__);
define('STORAGE_PATH', APP_ROOT . '/storage');

require_once APP_ROOT . '/app/Helpers/functions.php';

load_env(dirname(APP_ROOT, 2) . '/.env');
load_env(APP_ROOT . '/.env');

$debug = filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php.log');

foreach ([STORAGE_PATH . '/logs', STORAGE_PATH . '/pdf', STORAGE_PATH . '/tmp'] as $directory) {
    if (!is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

$vendorAutoload = APP_ROOT . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

session_name(env('SESSION_NAME', 'electronics_crm_session'));
session_start();
