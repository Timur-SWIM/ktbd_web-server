#!/usr/bin/env bash
set -euo pipefail

docker compose ps
docker compose exec web php /var/www/html/scripts/wait-for-db.php
curl -fsS http://localhost:8090/login >/dev/null

echo "Smoke checks passed: containers are up, Oracle is reachable, /login responds."
