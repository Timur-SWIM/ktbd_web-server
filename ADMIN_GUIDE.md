# Руководство администратора

## Роли

Роли создаются SQL-скриптом `database/sql/06_seed.sql`.

Минимальный набор:

- `admin`
- `engineer`
- `technologist`
- `manager`
- `director`

Правила доступа приложения описаны в `web/src/config/roles.php` и `web/src/app/Services/EntityConfig.php`.

## Учетные записи

Пароли должны храниться только как `password_hash`. Для новых пользователей используйте PHP `password_hash()` и вставляйте полученный hash в БД.

## Логи

PHP error log:

- `web/src/storage/logs/php.log`

Apache logs доступны внутри контейнера web:

```bash
docker compose logs web
```

Oracle logs доступны через:

```bash
docker compose logs oracle
```

## Диагностика

Проверить подключение web к Oracle:

```bash
docker compose exec web php /var/www/html/scripts/wait-for-db.php
```

Проверить smoke-сценарий:

```bash
./scripts/smoke.sh
```

## Безопасность

- Не используйте `system/oracle` в настройках приложения.
- Не публикуйте `.env`.
- Не открывайте наружу Oracle port `1521`, если он не нужен вне локальной сети.
- Все SQL-запросы приложения должны проходить через bind-параметры.
