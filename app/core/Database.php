<?php
/**
 * Database Singleton Connection Handler
 */

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static ?Database $instance = null;
    private ?PDO $pdo = null;
    private ?string $connectionError = null;

    private function __construct() {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            DB_HOST,
            DB_PORT,
            DB_NAME
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $this->connectionError = $e->getMessage();
            $this->pdo = null;
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): ?PDO {
        return $this->pdo;
    }

    public function getError(): ?string {
        return $this->connectionError;
    }

    public function isConnected(): bool {
        return $this->pdo !== null;
    }

    public static function createDatabaseIfNotExists(): bool {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', DB_HOST, DB_PORT);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', DB_NAME));
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
