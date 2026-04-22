#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ -f composer.json ] && [ ! -d vendor/tecnickcom/tcpdf ]; then
  composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader || true
fi

php /var/www/html/scripts/wait-for-db.php

exec apache2-foreground
