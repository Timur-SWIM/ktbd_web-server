<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\EntityConfig;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

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

    public function createMany(string $entity, array $rows, int $userId): int
    {
        if ($rows === []) {
            return 0;
        }

        $config = $this->config($entity);
        $fields = $this->databaseFields($config);
        $connection = Database::connection();
        $created = 0;

        try {
            foreach ($rows as $row) {
                $data = $this->only($row, $fields);
                $data['created_by'] = $userId;

                $columns = implode(', ', array_keys($data));
                $placeholders = implode(', ', array_map(static fn (string $field): string => ':' . $field, array_keys($data)));
                $statement = oci_parse($connection, "INSERT INTO {$config['table']} ({$columns}) VALUES ({$placeholders})");
                if (!$statement) {
                    $error = oci_error($connection);
                    throw new RuntimeException($error['message'] ?? 'Failed to parse SQL.');
                }

                $bound = [];
                foreach ($data as $key => $value) {
                    $bound[$key] = $value;
                    oci_bind_by_name($statement, ':' . $key, $bound[$key]);
                }

                if (!oci_execute($statement, OCI_NO_AUTO_COMMIT)) {
                    $error = oci_error($statement);
                    throw new RuntimeException($error['message'] ?? 'SQL execution failed.');
                }

                oci_free_statement($statement);
                $created++;
            }

            if (!oci_commit($connection)) {
                $error = oci_error($connection);
                throw new RuntimeException($error['message'] ?? 'Failed to commit imported records.');
            }
        } catch (Throwable $exception) {
            oci_rollback($connection);
            throw new RuntimeException('Failed to import records.', 0, $exception);
        }

        return $created;
    }

    public function existingValues(string $entity, string $field, array $values): array
    {
        $config = $this->config($entity);
        if (!in_array($field, $this->databaseFields($config), true)) {
            throw new InvalidArgumentException('Unknown field: ' . $field);
        }

        $values = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $values
        ), static fn (string $value): bool => $value !== '')));

        if ($values === []) {
            return [];
        }

        $params = [];
        $placeholders = [];
        foreach ($values as $index => $value) {
            $key = 'value_' . $index;
            $params[$key] = $value;
            $placeholders[] = ':' . $key;
        }

        $rows = $this->db->fetchAll(
            "SELECT {$field} AS value FROM {$config['table']} WHERE {$field} IN (" . implode(', ', $placeholders) . ')',
            $params
        );

        return array_map(static fn (array $row): string => (string) $row['value'], $rows);
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

        if ($entity === 'staff') {
            $this->deleteStaff($id);
            return;
        }

        $this->deleteEntityRelations($entity, $id);
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

    private function deleteStaff(int $id): void
    {
        $staff = $this->db->fetchOne(
            'SELECT s.id, s.full_name, s.created_by, u.full_name AS user_full_name,
                    (SELECT COUNT(*)
                     FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id
                     WHERE ur.user_id = u.id AND r.code = :admin_role) AS admin_roles
             FROM staff s LEFT JOIN users u ON u.id = s.created_by
             WHERE s.id = :id',
            ['id' => $id, 'admin_role' => 'admin']
        );

        if ($staff === null) {
            return;
        }

        $linkedUserId = $this->linkedUserIdForStaff($staff);
        $connection = Database::connection();

        try {
            $this->executeInTransaction($connection, 'UPDATE documents SET staff_id = NULL WHERE staff_id = :id', ['id' => $id]);
            $this->executeInTransaction($connection, 'DELETE FROM staff WHERE id = :id', ['id' => $id]);

            if ($linkedUserId !== null) {
                $this->deleteUserAccountInTransaction($connection, $linkedUserId);
            }

            if (!oci_commit($connection)) {
                $error = oci_error($connection);
                throw new RuntimeException($error['message'] ?? 'Failed to commit staff deletion.');
            }
        } catch (Throwable $exception) {
            oci_rollback($connection);
            throw new RuntimeException('Failed to delete staff record.', 0, $exception);
        }
    }

    private function linkedUserIdForStaff(array $staff): ?int
    {
        $createdBy = (int) ($staff['created_by'] ?? 0);
        if ($createdBy <= 0) {
            return null;
        }

        if ((int) ($staff['admin_roles'] ?? 0) > 0) {
            return null;
        }

        if ((string) ($staff['full_name'] ?? '') !== (string) ($staff['user_full_name'] ?? '')) {
            return null;
        }

        return $createdBy;
    }

    private function deleteUserAccountInTransaction(mixed $connection, int $userId): void
    {
        foreach (['staff', 'tools', 'elements', 'documents', 'devices'] as $table) {
            $this->executeInTransaction(
                $connection,
                "UPDATE {$table} SET created_by = NULL WHERE created_by = :user_id",
                ['user_id' => $userId]
            );
        }

        $this->executeInTransaction($connection, 'DELETE FROM users WHERE id = :user_id', ['user_id' => $userId]);
    }

    private function deleteEntityRelations(string $entity, int $id): void
    {
        match ($entity) {
            'tools' => $this->db->execute('DELETE FROM device_tools WHERE tool_id = :id', ['id' => $id]),
            'elements' => $this->db->execute('DELETE FROM device_elements WHERE element_id = :id', ['id' => $id]),
            'documents' => $this->db->execute('DELETE FROM device_documents WHERE document_id = :id', ['id' => $id]),
            'devices' => $this->deleteDeviceRelations($id),
            default => null,
        };
    }

    private function deleteDeviceRelations(int $id): void
    {
        $this->db->execute('DELETE FROM device_tools WHERE device_id = :id', ['id' => $id]);
        $this->db->execute('DELETE FROM device_elements WHERE device_id = :id', ['id' => $id]);
        $this->db->execute('DELETE FROM device_documents WHERE device_id = :id', ['id' => $id]);
    }

    private function executeInTransaction(mixed $connection, string $sql, array $params): void
    {
        $statement = oci_parse($connection, $sql);
        if (!$statement) {
            $error = oci_error($connection);
            throw new RuntimeException($error['message'] ?? 'Failed to parse SQL.');
        }

        $bound = [];
        foreach ($params as $key => $value) {
            $bound[$key] = $value;
            oci_bind_by_name($statement, ':' . $key, $bound[$key]);
        }

        if (!oci_execute($statement, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($statement);
            throw new RuntimeException($error['message'] ?? 'SQL execution failed.');
        }

        oci_free_statement($statement);
    }
}
