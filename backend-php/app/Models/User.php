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
     * Find user by email
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
     * Find user by Google ID
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
     * Find user by ID
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
     * Create new user
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (email, password, name, google_id, auth_provider, created_at, updated_at) 
                VALUES 
                (:email, :password, :name, :google_id, :auth_provider, NOW(), NOW())
                RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $data['email'],
            ':password' => $data['password'] ?? null,
            ':name' => $data['name'],
            ':google_id' => $data['google_id'] ?? null,
            ':auth_provider' => $data['auth_provider'] ?? 'email'
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['id'];
    }

    /**
     * Update user
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            $fields[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }

        $sql = "UPDATE {$this->table} 
                SET " . implode(', ', $fields) . ", updated_at = NOW() 
                WHERE id = :id";

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
        $stmt->execute([':id' => $id]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Hash password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Get all users (admin function)
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT id, email, name, auth_provider, created_at 
                FROM {$this->table} 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total users
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Check if user exists by email
     */
    public function existsByEmail(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /**
     * Check if user exists by Google ID
     */
    public function existsByGoogleId(string $googleId): bool
    {
        return $this->findByGoogleId($googleId) !== null;
    }
}