<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\EntityConfig;
use InvalidArgumentException;

final class EntityRepository
{
    public function __construct(private readonly Database $db = new Database())
    {
    }

    public function all(string $entity): array
    {
        $config = $this->config($entity);

        if ($entity === 'documents') {
            return $this->db->fetchAll(
                'SELECT d.id, d.title, d.document_number, d.document_type, s.full_name AS staff_name, d.status
                 FROM documents d LEFT JOIN staff s ON s.id = d.staff_id ORDER BY d.id DESC'
            );
        }

        $columns = implode(', ', array_map(static fn (string $column): string => $column, $config['list']));
        return $this->db->fetchAll("SELECT {$columns} FROM {$config['table']} ORDER BY id DESC");
    }

    public function find(string $entity, int $id): ?array
    {
        $config = $this->config($entity);
        $row = $this->db->fetchOne("SELECT * FROM {$config['table']} WHERE id = :id", ['id' => $id]);

        if ($row && $entity === 'devices') {
            $row['tool_ids'] = $this->relationIds('device_tools', 'tool_id', $id);
            $row['element_ids'] = $this->relationIds('device_elements', 'element_id', $id);
            $row['document_ids'] = $this->relationIds('device_documents', 'document_id', $id);
        }

        return $row;
    }

    public function create(string $entity, array $input, int $userId): int
    {
        $config = $this->config($entity);
        $fields = $this->databaseFields($config);
        $data = $this->only($input, $fields);
        $data['created_by'] = $userId;

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(static fn (string $field): string => ':' . $field, array_keys($data)));
        $id = $this->db->insertReturningId(
            "INSERT INTO {$config['table']} ({$columns}) VALUES ({$placeholders}) RETURNING id INTO :new_id",
            $data
        );

        if ($entity === 'devices') {
            $this->syncDeviceRelations($id, $input);
        }

        return $id;
    }

    public function update(string $entity, int $id, array $input): void
    {
        $config = $this->config($entity);
        $fields = $this->databaseFields($config);
        $data = $this->only($input, $fields);

        $sets = implode(', ', array_map(static fn (string $field): string => "{$field} = :{$field}", array_keys($data)));
        $data['id'] = $id;
        $this->db->execute("UPDATE {$config['table']} SET {$sets} WHERE id = :id", $data);

        if ($entity === 'devices') {
            $this->syncDeviceRelations($id, $input);
        }
    }

    public function delete(string $entity, int $id): void
    {
        $config = $this->config($entity);
        $this->db->execute("DELETE FROM {$config['table']} WHERE id = :id", ['id' => $id]);
    }

    public function options(string $source): array
    {
        return match ($source) {
            'staff' => $this->db->fetchAll('SELECT id, full_name AS label FROM staff ORDER BY full_name'),
            'tools' => $this->db->fetchAll("SELECT id, name || ' (' || inventory_number || ')' AS label FROM tools ORDER BY name"),
            'elements' => $this->db->fetchAll("SELECT id, name || ' (' || part_number || ')' AS label FROM elements ORDER BY name"),
            'documents' => $this->db->fetchAll("SELECT id, title || ' (' || document_number || ')' AS label FROM documents ORDER BY title"),
            default => [],
        };
    }

    public function validate(string $entity, array $input): array
    {
        $config = $this->config($entity);
        $errors = [];

        foreach ($config['fields'] as $name => $field) {
            if (($field['virtual'] ?? false) === true) {
                continue;
            }

            $value = trim((string) ($input[$name] ?? ''));
            if (($field['required'] ?? false) && $value === '') {
                $errors[$name] = 'Поле обязательно для заполнения.';
                continue;
            }

            if (($field['type'] ?? 'text') === 'number' && $value !== '' && !is_numeric($value)) {
                $errors[$name] = 'Введите число.';
            }

            if (($field['type'] ?? 'text') === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$name] = 'Введите корректный email.';
            }
        }

        return $errors;
    }

    public function config(string $entity): array
    {
        $config = EntityConfig::get($entity);
        if ($config === null) {
            throw new InvalidArgumentException('Unknown entity: ' . $entity);
        }

        return $config;
    }

    private function databaseFields(array $config): array
    {
        $fields = [];
        foreach ($config['fields'] as $name => $field) {
            if (($field['virtual'] ?? false) === true) {
                continue;
            }
            $fields[] = $name;
        }

        return $fields;
    }

    private function only(array $input, array $fields): array
    {
        $data = [];
        foreach ($fields as $field) {
            $value = $input[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $data[$field] = $value === '' ? null : $value;
        }

        return $data;
    }

    private function relationIds(string $table, string $column, int $deviceId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT {$column} AS id FROM {$table} WHERE device_id = :device_id",
            ['device_id' => $deviceId]
        );

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    private function syncDeviceRelations(int $deviceId, array $input): void
    {
        $this->syncRelation($deviceId, 'device_tools', 'tool_id', $input['tool_ids'] ?? []);
        $this->syncRelation($deviceId, 'device_elements', 'element_id', $input['element_ids'] ?? [], ['quantity' => 1]);
        $this->syncRelation($deviceId, 'device_documents', 'document_id', $input['document_ids'] ?? []);
    }

    private function syncRelation(int $deviceId, string $table, string $column, array $ids, array $extra = []): void
    {
        $this->db->execute("DELETE FROM {$table} WHERE device_id = :device_id", ['device_id' => $deviceId]);

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        foreach ($ids as $id) {
            $params = ['device_id' => $deviceId, $column => $id] + $extra;
            $columns = implode(', ', array_keys($params));
            $placeholders = implode(', ', array_map(static fn (string $field): string => ':' . $field, array_keys($params)));
            $this->db->execute("INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})", $params);
        }
    }
}
