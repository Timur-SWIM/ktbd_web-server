# Схема БД

## Основные таблицы

- `users`: пользователи приложения.
- `roles`: роли.
- `user_roles`: связь пользователей и ролей.
- `staff`: персонал.
- `tools`: оборудование и инструмент.
- `elements`: компоненты и элементы.
- `documents`: технологические документы.
- `devices`: изделия.

## Связи изделий

- `device_tools`: изделие -> оборудование.
- `device_elements`: изделие -> элементы.
- `device_documents`: изделие -> документы.

## Ограничения

Скрипт `02_constraints.sql` создает:

- primary keys;
- foreign keys;
- unique constraints;
- check constraints для статусов;
- индексы по статусам и внешним ключам.

## Sequences и triggers

Скрипт `03_sequences.sql` создает sequences для таблиц с числовыми ID.

Скрипт `04_triggers.sql` создает `BEFORE INSERT OR UPDATE` triggers:

- заполнение ID из sequence;
- заполнение `created_at`;
- обновление `updated_at`.

## PL/SQL

Скрипт `05_packages.sql` создает пакет `pkg_dashboard` с функциями:

- `count_devices`
- `count_staff`
- `count_documents`
- `count_tools_by_status`

Dashboard использует пакет для получения основных метрик.
