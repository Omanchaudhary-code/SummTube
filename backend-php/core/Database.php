<?php

namespace Core;

use PDO;
use PDOException;

/**
 * Database Connection Manager
 * Singleton pattern - supports both MySQL and PostgreSQL
 */
class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        // Singleton pattern
    }

    /**
     * Get PDO instance (Singleton)
     * Returns actual PDO object for direct use
     * 
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }

        return self::$instance;
    }

    /**
     * Establish database connection
     * Supports both MySQL and PostgreSQL
     * 
     * @return void
     */
    private static function connect(): void
    {
        try {
            // Load database configuration
            self::$config = require __DIR__ . '/../config/database.php';

            $driver = self::$config['driver'] ?? self::$config['connection'] ?? 'mysql';
            $host = self::$config['host'];
            $port = self::$config['port'];
            $database = self::$config['database'];
            $username = self::$config['username'];
            $password = self::$config['password'];

            // Build DSN based on driver
            if ($driver === 'pgsql') {
                // PostgreSQL DSN
                $charset = self::$config['charset'] ?? 'utf8';
                $sslmode = self::$config['sslmode'] ?? 'prefer';
                
                $dsn = "pgsql:host={$host};port={$port};dbname={$database};sslmode={$sslmode}";
                
            } else {
                // MySQL DSN
                $charset = self::$config['charset'] ?? 'utf8mb4';
                $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
            }

            // Create PDO instance
            self::$instance = new PDO(
                $dsn,
                $username,
                $password,
                self::$config['options'] ?? []
            );

            // Set error mode to exception
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            // PostgreSQL specific settings
            if ($driver === 'pgsql' && isset(self::$config['schema'])) {
                self::$instance->exec("SET search_path TO " . self::$config['schema']);
            }

        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Begin transaction
     * 
     * @return bool
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit transaction
     * 
     * @return bool
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Rollback transaction
     * 
     * @return bool
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Prepare SQL statement
     * Convenience method for direct access
     * 
     * @param string $sql
     * @return \PDOStatement
     */
    public static function prepare(string $sql): \PDOStatement
    {
        return self::getInstance()->prepare($sql);
    }

    /**
     * Execute query and return results
     * 
     * @param string $sql
     * @param array $params
     * @return array
     */
    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute statement and return affected rows
     * 
     * @param string $sql
     * @param array $params
     * @return int
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Get last insert ID
     * Works with both MySQL and PostgreSQL
     * 
     * @param string|null $name Sequence name for PostgreSQL (optional)
     * @return string
     */
    public static function lastInsertId(?string $name = null): string
    {
        return self::getInstance()->lastInsertId($name);
    }

    /**
     * Close database connection
     * 
     * @return void
     */
    public static function close(): void
    {
        self::$instance = null;
    }

    /**
     * Test database connection
     * 
     * @return bool
     */
    public static function testConnection(): bool
    {
        try {
            self::getInstance();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get database configuration
     * 
     * @return array
     */
    public static function getConfig(): array
    {
        if (empty(self::$config)) {
            self::$config = require __DIR__ . '/../config/database.php';
        }
        return self::$config;
    }

    /**
     * Get database driver (mysql or pgsql)
     * 
     * @return string
     */
    public static function getDriver(): string
    {
        $config = self::getConfig();
        return $config['driver'] ?? $config['connection'] ?? 'mysql';
    }

    /**
     * Check if using PostgreSQL
     * 
     * @return bool
     */
    public static function isPostgreSQL(): bool
    {
        return self::getDriver() === 'pgsql';
    }

    /**
     * Check if using MySQL
     * 
     * @return bool
     */
    public static function isMySQL(): bool
    {
        return self::getDriver() === 'mysql';
    }
}