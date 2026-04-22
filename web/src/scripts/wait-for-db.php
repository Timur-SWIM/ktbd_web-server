<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$attempts = 20;
while ($attempts > 0) {
    try {
        \App\Repositories\Database::connection();
        fwrite(STDOUT, "Oracle connection is ready.\n");
        exit(0);
    } catch (Throwable $exception) {
        fwrite(STDOUT, 'Waiting for Oracle: ' . $exception->getMessage() . "\n");
        sleep(3);
        $attempts--;
    }
}

fwrite(STDERR, "Oracle not available.\n");
exit(1);
