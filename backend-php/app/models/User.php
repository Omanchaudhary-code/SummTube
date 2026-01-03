<?php
namespace App\Models;

use Core\Database;
use PDO;

class User
{
    private PDO $db;
    private string $table = 'users';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create new user
     * PostgreSQL compatible with RETURNING clause
     */
    public function create(array $data): int
    {
        // Check if using PostgreSQL
        $driver = Database::getDriver();
        
        if ($driver === 'pgsql') {
            // PostgreSQL with RETURNING
            $sql = "INSERT INTO {$this->table} (email, password, name, auth_provider, google_id) 
                    VALUES (:email, :password, :name, :auth_provider, :google_id)
                    RETURNING id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':email' => $data['email'],
                ':password' => $data['password'] ?? null,
                ':name' => $data['name'],
                ':auth_provider' => $data['auth_provider'] ?? 'email',
                ':google_id' => $data['google_id'] ?? null
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['id'];
        } else {
            // MySQL
            $sql = "INSERT INTO {$this->table} (email, password, name, auth_provider, google_id, created_at) 
                    VALUES (:email, :password, :name, :auth_provider, :google_id, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':email' => $data['email'],
                ':password' => $data['password'] ?? null,
                ':name' => $data['name'],
                ':auth_provider' => $data['auth_provider'] ?? 'email',
                ':google_id' => $data['google_id'] ?? null
            ]);
            
            return (int) $this->db->lastInsertId();
        }
    }

    /**
     * Find user by email
     * Removed is_active check
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Find user by ID
     * Removed is_active check
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Find user by Google ID
     * Removed is_active check
     */
    public function findByGoogleId(string $googleId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE google_id = :google_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':google_id' => $googleId]);
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (!in_array($key, ['id', 'created_at'])) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        // PostgreSQL uses CURRENT_TIMESTAMP, MySQL uses NOW()
        $driver = Database::getDriver();
        $timestamp = $driver === 'pgsql' ? 'CURRENT_TIMESTAMP' : 'NOW()';
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . ", updated_at = {$timestamp} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete user
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Hash password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}