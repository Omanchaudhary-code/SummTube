<?php

/**
 * Database Configuration
 * Supports both MySQL and PostgreSQL (Neon)
 * 
 * Switch between databases by changing DB_CONNECTION in .env:
 * - DB_CONNECTION=mysql (for local MySQL/MariaDB)
 * - DB_CONNECTION=pgsql (for Neon PostgreSQL)
 */

$connection = $_ENV['DB_CONNECTION'] ?? 'mysql';

if ($connection === 'pgsql') {
    // ==========================================
    // PostgreSQL Configuration (Neon)
    // ==========================================
    return [
        'connection' => 'pgsql',
        'driver' => 'pgsql',
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'port' => (int) ($_ENV['DB_PORT'] ?? 5432),
        'database' => $_ENV['DB_DATABASE'] ?? 'neondb',
        'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8',
        'prefix' => '',
        'schema' => 'public',
        'sslmode' => $_ENV['DB_SSLMODE'] ?? 'require',
        
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_PERSISTENT => false,
        ]
    ];
} else {
    // ==========================================
    // MySQL Configuration (Local/Cloud)
    // ==========================================
    return [
        'connection' => 'mysql',
        'driver' => 'mysql',
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
        'database' => $_ENV['DB_DATABASE'] ?? 'summtube',
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            PDO::ATTR_TIMEOUT => 30,
            PDO::ATTR_PERSISTENT => false,
        ]
    ];
}