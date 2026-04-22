<?php

declare(strict_types=1);

namespace App\Repositories;

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
}
