<?php
namespace App\Models;

use Core\Database;
use PDO;

class Usage
{
    private PDO $db;
    private string $table = 'usage';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create usage record for new user
     * PostgreSQL compatible with RETURNING clause
     */
    public function create(int $userId): int
    {
        $driver = Database::getDriver();
        
        if ($driver === 'pgsql') {
            // PostgreSQL with RETURNING
            $sql = "INSERT INTO {$this->table} (user_id, total_summaries) 
                    VALUES (:user_id, 0)
                    RETURNING id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['id'];
        } else {
            // MySQL
            $sql = "INSERT INTO {$this->table} (user_id, total_summaries, created_at) 
                    VALUES (:user_id, 0, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            
            return (int) $this->db->lastInsertId();
        }
    }

    /**
     * Get usage by user ID
     */
    public function getByUserId(int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $usage = $stmt->fetch(PDO::FETCH_ASSOC);
        return $usage ?: null;
    }

    /**
     * Increment summary counter
     */
    public function incrementSummaries(int $userId): bool
    {
        $driver = Database::getDriver();
        $timestamp = $driver === 'pgsql' ? 'CURRENT_TIMESTAMP' : 'NOW()';
        
        $sql = "UPDATE {$this->table} 
                SET total_summaries = total_summaries + 1, 
                    last_summary_at = {$timestamp}
                WHERE user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Reset usage counter
     */
    public function reset(int $userId): bool
    {
        $sql = "UPDATE {$this->table} 
                SET total_summaries = 0 
                WHERE user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':user_id' => $userId]);
    }
}
