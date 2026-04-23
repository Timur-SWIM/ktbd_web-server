<?php

declare(strict_types=1);

namespace App\Services;

final class EntityConfig
{
    public static function all(): array
    {
        return [
            'tools' => [
                'title' => 'Оборудование',
                'singular' => 'Оборудование',
                'table' => 'tools',
                'roles' => ['engineer'],
                'import' => ['unique' => 'inventory_number'],
                'list' => ['id', 'name', 'inventory_number', 'tool_type', 'location', 'status'],
                'fields' => [
                    'name' => ['label' => 'Наименование', 'required' => true],
                    'inventory_number' => ['label' => 'Инвентарный номер', 'required' => true],
                    'tool_type' => ['label' => 'Тип', 'required' => true],
                    'location' => ['label' => 'Место хранения'],
                    'status' => ['label' => 'Статус', 'type' => 'select', 'required' => true, 'options' => [
                        'available' => 'Доступно',
                        'in_use' => 'В работе',
                        'maintenance' => 'Обслуживание',
                        'retired' => 'Списано',
                    ]],
                ],
            ],
            'staff' => [
                'title' => 'Персонал',
                'singular' => 'Сотрудник',
                'table' => 'staff',
                'roles' => ['manager'],
                'list' => ['id', 'full_name', 'position', 'department', 'phone', 'email', 'status'],
                'fields' => [
                    'full_name' => ['label' => 'ФИО', 'required' => true],
                    'position' => ['label' => 'Должность', 'required' => true],
                    'department' => ['label' => 'Подразделение', 'required' => true],
                    'phone' => ['label' => 'Телефон'],
                    'email' => ['label' => 'Email', 'type' => 'email'],
                    'status' => ['label' => 'Статус', 'type' => 'select', 'required' => true, 'options' => [
                        'active' => 'Активен',
                        'inactive' => 'Неактивен',
                    ]],
                ],
            ],
            'elements' => [
                'title' => 'Элементы',
                'singular' => 'Элемент',
                'table' => 'elements',
                'roles' => ['technologist'],
                'import' => ['unique' => 'part_number'],
                'list' => ['id', 'name', 'part_number', 'element_type', 'quantity', 'unit', 'status'],
                'fields' => [
                    'name' => ['label' => 'Наименование', 'required' => true],
                    'part_number' => ['label' => 'Артикул', 'required' => true],
                    'element_type' => ['label' => 'Тип', 'required' => true],
                    'quantity' => ['label' => 'Количество', 'type' => 'number', 'required' => true],
                    'unit' => ['label' => 'Ед. изм.', 'required' => true],
                    'status' => ['label' => 'Статус', 'type' => 'select', 'required' => true, 'options' => [
                        'active' => 'Активен',
                        'reserved' => 'Зарезервирован',
                        'depleted' => 'Закончился',
                        'obsolete' => 'Устарел',
                    ]],
                ],
            ],
            'documents' => [
                'title' => 'Документы',
                'singular' => 'Документ',
                'table' => 'documents',
                'roles' => ['director'],
                'list' => ['id', 'title', 'document_number', 'document_type', 'staff_name', 'status'],
                'fields' => [
                    'title' => ['label' => 'Название', 'required' => true],
                    'document_number' => ['label' => 'Номер документа', 'required' => true],
                    'document_type' => ['label' => 'Тип документа', 'required' => true],
                    'staff_id' => ['label' => 'Ответственный', 'type' => 'select', 'source' => 'staff'],
                    'status' => ['label' => 'Статус', 'type' => 'select', 'required' => true, 'options' => [
                        'draft' => 'Черновик',
                        'approved' => 'Утвержден',
                        'archived' => 'Архив',
                    ]],
                    'content' => ['label' => 'Содержание', 'type' => 'textarea'],
                ],
            ],
            'devices' => [
                'title' => 'Изделия',
                'singular' => 'Изделие',
                'table' => 'devices',
                'roles' => ['engineer'],
                'import' => ['unique' => 'serial_number'],
                'list' => ['id', 'name', 'serial_number', 'model', 'production_status', 'status'],
                'fields' => [
                    'name' => ['label' => 'Наименование', 'required' => true],
                    'serial_number' => ['label' => 'Серийный номер', 'required' => true],
                    'model' => ['label' => 'Модель', 'required' => true],
                    'production_status' => ['label' => 'Производственный статус', 'type' => 'select', 'required' => true, 'options' => [
                        'planned' => 'План',
                        'assembly' => 'Сборка',
                        'testing' => 'Испытания',
                        'ready' => 'Готово',
                        'blocked' => 'Блокировано',
                    ]],
                    'status' => ['label' => 'Статус', 'type' => 'select', 'required' => true, 'options' => [
                        'active' => 'Активно',
                        'inactive' => 'Неактивно',
                    ]],
                    'tool_ids' => ['label' => 'Оборудование', 'type' => 'multiselect', 'source' => 'tools', 'virtual' => true],
                    'element_ids' => ['label' => 'Элементы', 'type' => 'multiselect', 'source' => 'elements', 'virtual' => true],
                    'document_ids' => ['label' => 'Документы', 'type' => 'multiselect', 'source' => 'documents', 'virtual' => true],
                ],
            ],
        ];
    }

    public static function get(string $entity): ?array
    {
        return self::all()[$entity] ?? null;
    }
}
