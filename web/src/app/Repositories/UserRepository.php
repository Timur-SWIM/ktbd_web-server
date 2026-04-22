<?php

declare(strict_types=1);

namespace App\Repositories;

use RuntimeException;
use Throwable;

final class UserRepository
{
    public function __construct(private readonly Database $db = new Database())
    {
    }

    public function findActiveByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            'SELECT id, username, password_hash, full_name, status FROM users WHERE username = :username AND status = :status',
            ['username' => $username, 'status' => 'active']
        );
    }

    public function rolesForUser(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT r.code FROM roles r INNER JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = :user_id ORDER BY r.code',
            ['user_id' => $userId]
        );

        return array_map(static fn (array $row): string => $row['code'], $rows);
    }

    public function isActiveUserId(int $userId): bool
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*)
             FROM users u
             WHERE u.id = :id
               AND u.status = :status
               AND (
                   EXISTS (
                       SELECT 1
                       FROM user_roles ur INNER JOIN roles r ON r.id = ur.role_id
                       WHERE ur.user_id = u.id AND r.code = :admin_role
                   )
                   OR EXISTS (
                       SELECT 1
                       FROM staff s
                       WHERE s.created_by = u.id AND s.full_name = u.full_name
                   )
               )',
            ['id' => $userId, 'status' => 'active', 'admin_role' => 'admin']
        ) > 0;
    }

    public function usernameExists(string $username): bool
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM users WHERE username = :username',
            ['username' => $username]
        ) > 0;
    }

    public function createWithStaffProfile(array $data): int
    {
        $connection = Database::connection();

        try {
            $userId = $this->insertUser($connection, $data);
            $this->insertStaffProfile($connection, $userId, $data);

            if (!oci_commit($connection)) {
                $error = oci_error($connection);
                throw new RuntimeException($error['message'] ?? 'Failed to commit user registration.');
            }

            return $userId;
        } catch (Throwable $exception) {
            oci_rollback($connection);
            throw new RuntimeException('Failed to create user registration.', 0, $exception);
        }
    }

    public function deleteAccount(int $userId): void
    {
        $connection = Database::connection();

        try {
            foreach (['staff', 'tools', 'elements', 'documents', 'devices'] as $table) {
                $this->executeInTransaction(
                    $connection,
                    "UPDATE {$table} SET created_by = NULL WHERE created_by = :user_id",
                    ['user_id' => $userId]
                );
            }

            $this->executeInTransaction($connection, 'DELETE FROM users WHERE id = :user_id', ['user_id' => $userId]);

            if (!oci_commit($connection)) {
                $error = oci_error($connection);
                throw new RuntimeException($error['message'] ?? 'Failed to commit user deletion.');
            }
        } catch (Throwable $exception) {
            oci_rollback($connection);
            throw new RuntimeException('Failed to delete user account.', 0, $exception);
        }
    }

    private function insertUser(mixed $connection, array $data): int
    {
        $statement = oci_parse(
            $connection,
            'INSERT INTO users (username, password_hash, full_name, status)
             VALUES (:username, :password_hash, :full_name, :status)
             RETURNING id INTO :new_id'
        );

        if (!$statement) {
            $error = oci_error($connection);
            throw new RuntimeException($error['message'] ?? 'Failed to parse user insert.');
        }

        $fullName = $data['full_name'];
        $status = 'active';
        $newId = 0;

        oci_bind_by_name($statement, ':username', $data['username']);
        oci_bind_by_name($statement, ':password_hash', $data['password_hash']);
        oci_bind_by_name($statement, ':full_name', $fullName);
        oci_bind_by_name($statement, ':status', $status);
        oci_bind_by_name($statement, ':new_id', $newId, 20, SQLT_INT);

        if (!oci_execute($statement, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($statement);
            throw new RuntimeException($error['message'] ?? 'Failed to insert user.');
        }

        oci_free_statement($statement);
        return (int) $newId;
    }

    private function insertStaffProfile(mixed $connection, int $userId, array $data): void
    {
        $statement = oci_parse(
            $connection,
            'INSERT INTO staff (full_name, position, department, phone, email, status, created_by)
             VALUES (:full_name, :position, :department, :phone, :email, :status, :created_by)'
        );

        if (!$statement) {
            $error = oci_error($connection);
            throw new RuntimeException($error['message'] ?? 'Failed to parse staff insert.');
        }

        $fullName = $data['full_name'];
        $status = 'active';

        oci_bind_by_name($statement, ':full_name', $fullName);
        oci_bind_by_name($statement, ':position', $data['position']);
        oci_bind_by_name($statement, ':department', $data['department']);
        oci_bind_by_name($statement, ':phone', $data['phone']);
        oci_bind_by_name($statement, ':email', $data['email']);
        oci_bind_by_name($statement, ':status', $status);
        oci_bind_by_name($statement, ':created_by', $userId, 20, SQLT_INT);

        if (!oci_execute($statement, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($statement);
            throw new RuntimeException($error['message'] ?? 'Failed to insert staff profile.');
        }

        oci_free_statement($statement);
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
