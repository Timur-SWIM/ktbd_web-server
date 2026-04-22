<?php

declare(strict_types=1);

return [
    'name' => 'CRM / АСУ ТП электроники',
    'env' => env('APP_ENV', 'local'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url' => env('APP_URL', 'http://localhost:8090'),
];
