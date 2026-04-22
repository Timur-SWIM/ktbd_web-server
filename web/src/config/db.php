<?php

declare(strict_types=1);

return [
    'host' => env('ORACLE_HOST', 'oracle'),
    'port' => env('ORACLE_PORT', '1521'),
    'service' => env('ORACLE_SERVICE', 'XEPDB1'),
    'user' => env('ORACLE_APP_USER', 'electronics_app'),
    'password' => env('ORACLE_APP_PASSWORD', 'electronics_app_password'),
];
