# Установка

## Требования

- Docker
- Docker Compose v2
- Доступ к интернету при первой сборке PHP-образа, чтобы скачать Oracle Instant Client, Composer-зависимости и Docker images.

## Локальный запуск

```bash
cp .env.example .env
docker compose up --build
```

Oracle стартует долго. Web-контейнер ждет готовности БД через `web/src/scripts/wait-for-db.php`.

## Подключение к Oracle

Приложение внутри контейнера web подключается только так:

- host: `oracle`
- port: `1521`
- service: `XEPDB1`
- app user: `electronics_app`

Пользователь `system` не используется приложением. Он нужен только контейнеру Oracle для первичной инициализации.

## Перенос на сервер `100.108.192.17`

1. Скопируйте весь каталог проекта на сервер.
2. На сервере создайте `.env` из `.env.example`.
3. Запустите `docker compose up --build`.
4. Проверьте доступность `http://100.108.192.17:8090/login`.

Если Oracle volume уже создан и нужно пересоздать БД с нуля, остановите проект и удалите volume `oracle-data`.
