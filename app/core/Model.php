<?php
/**
 * Base Model
 */

namespace App\Core;

use PDO;
use PDOException;

abstract class Model {
    protected ?PDO $db;
    protected string $table = '';
    protected string $primaryKey = 'id';

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Execute a prepared SQL query
     */
    public function query(string $sql, array $params = []): false|\PDOStatement {
        if (!$this->db) {
            return false;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all records
     */
    public function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Fetch single record
     */
    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->query($sql, $params);
        $result = $stmt ? $stmt->fetch() : false;
        return $result !== false ? $result : null;
    }

    /**
     * Get all rows from current table
     */
    public function all(string $orderBy = 'id DESC'): array {
        if (empty($this->table)) return [];
        return $this->fetchAll("SELECT * FROM `{$this->table}` ORDER BY {$orderBy}");
    }

    /**
     * Find single record by primary key
     */
    public function find(int $id): ?array {
        if (empty($this->table)) return null;
        return $this->fetchOne("SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id LIMIT 1", ['id' => $id]);
    }

    /**
     * Find single record by column
     */
    public function findBy(string $column, mixed $value): ?array {
        if (empty($this->table)) return null;
        return $this->fetchOne("SELECT * FROM `{$this->table}` WHERE `{$column}` = :val LIMIT 1", ['val' => $value]);
    }

    /**
     * Get multiple records by column
     */
    public function where(string $column, mixed $value, string $orderBy = 'id DESC'): array {
        if (empty($this->table)) return [];
        return $this->fetchAll("SELECT * FROM `{$this->table}` WHERE `{$column}` = :val ORDER BY {$orderBy}", ['val' => $value]);
    }

    /**
     * Count records
     */
    public function count(string $whereClause = '', array $params = []): int {
        if (empty($this->table)) return 0;
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}`" . ($whereClause !== '' ? " WHERE {$whereClause}" : "");
        $res = $this->fetchOne($sql, $params);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Insert record
     */
    public function create(array $data): int|false {
        if (empty($this->table) || empty($data)) return false;

        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $sql = sprintf(
            "INSERT INTO `%s` (`%s`) VALUES (%s)",
            $this->table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->query($sql, $data);
        if ($stmt !== false) {
            return (int)$this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Update record
     */
    public function update(int $id, array $data): bool {
        if (empty($this->table) || empty($data)) return false;

        $fields = [];
        foreach ($data as $col => $val) {
            $fields[] = "`$col` = :$col";
        }
        $data['__id'] = $id;

        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE `{$this->primaryKey}` = :__id",
            $this->table,
            implode(', ', $fields)
        );

        $stmt = $this->query($sql, $data);
        return $stmt !== false;
    }

    /**
     * Delete record by ID
     */
    public function delete(int $id): bool {
        if (empty($this->table)) return false;
        $stmt = $this->query("DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id", ['id' => $id]);
        return $stmt !== false;
    }

    /**
     * Last Insert ID
     */
    public function lastInsertId(): string|false {
        return $this->db ? $this->db->lastInsertId() : false;
    }
}
