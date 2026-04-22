<?php

declare(strict_types=1);

namespace App\Repositories;

use RuntimeException;

final class Database
{
    private static mixed $connection = null;

    public static function connection(): mixed
    {
        if (self::$connection) {
            return self::$connection;
        }

        if (!extension_loaded('oci8')) {
            throw new RuntimeException('PHP extension oci8 is not loaded.');
        }

        $config = require APP_ROOT . '/config/db.php';
        $connString = sprintf(
            '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=%s)(PORT=%s))(CONNECT_DATA=(SERVICE_NAME=%s)))',
            $config['host'],
            $config['port'],
            $config['service']
        );

        $attempts = 10;
        while ($attempts > 0) {
            $connection = @oci_connect($config['user'], $config['password'], $connString, 'AL32UTF8');
            if ($connection) {
                self::$connection = $connection;
                return self::$connection;
            }
            sleep(3);
            $attempts--;
        }

        $error = oci_error();
        throw new RuntimeException('Oracle not available: ' . ($error['message'] ?? 'unknown error'));
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $statement = $this->executeStatement($sql, $params, false);
        $rows = [];

        while (($row = oci_fetch_array($statement, OCI_ASSOC | OCI_RETURN_NULLS | OCI_RETURN_LOBS)) !== false) {
            $rows[] = $this->normalizeRow($row);
        }

        oci_free_statement($statement);
        return $rows;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $rows = $this->fetchAll($sql, $params);
        return $rows[0] ?? null;
    }

    public function scalar(string $sql, array $params = []): mixed
    {
        $row = $this->fetchOne($sql, $params);
        if ($row === null) {
            return null;
        }

        return reset($row);
    }

    public function execute(string $sql, array $params = []): void
    {
        $statement = $this->executeStatement($sql, $params, true);
        oci_free_statement($statement);
    }

    public function insertReturningId(string $sql, array $params = []): int
    {
        $connection = self::connection();
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

        $newId = 0;
        oci_bind_by_name($statement, ':new_id', $newId, 20, SQLT_INT);

        if (!oci_execute($statement, OCI_COMMIT_ON_SUCCESS)) {
            $error = oci_error($statement);
            throw new RuntimeException($error['message'] ?? 'SQL execution failed.');
        }

        oci_free_statement($statement);
        return (int) $newId;
    }

    private function executeStatement(string $sql, array $params, bool $commit): mixed
    {
        $connection = self::connection();
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

        $mode = $commit ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT;
        if (!oci_execute($statement, $mode)) {
            $error = oci_error($statement);
            throw new RuntimeException($error['message'] ?? 'SQL execution failed.');
        }

        return $statement;
    }

    private function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[strtolower((string) $key)] = $value;
        }

        return $normalized;
    }
}
