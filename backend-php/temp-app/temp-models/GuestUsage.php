<?php
namespace App\Models;

use Core\Database;
use PDO;

class GuestUsage
{
    private PDO $db;
    private string $table = 'guest_usage';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get guest usage by IP address
     */
    public function getByIp(string $ipAddress): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE ip_address = :ip_address LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ip_address' => $ipAddress]);

        $usage = $stmt->fetch(PDO::FETCH_ASSOC);
        return $usage ?: null;
    }

    /**
     * Create new guest usage record
     */
    public function create(string $ipAddress): int
    {
        $driver = Database::getDriver();
        
        // Set reset time to 24 hours from now
        $resetAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        if ($driver === 'pgsql') {
            // PostgreSQL with RETURNING
            $sql = "INSERT INTO {$this->table} (ip_address, summaries_count, reset_at) 
                    VALUES (:ip_address, 1, :reset_at)
                    RETURNING id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':ip_address' => $ipAddress,
                ':reset_at' => $resetAt
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['id'];
        } else {
            // MySQL
            $sql = "INSERT INTO {$this->table} (ip_address, summaries_count, reset_at, created_at) 
                    VALUES (:ip_address, 1, :reset_at, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':ip_address' => $ipAddress,
                ':reset_at' => $resetAt
            ]);
            
            return (int) $this->db->lastInsertId();
        }
    }

    /**
     * Get or create guest usage record
     */
    public function getOrCreate(string $ipAddress): array
    {
        $usage = $this->getByIp($ipAddress);
        
        if ($usage) {
            // Check if reset time has passed
            if (strtotime($usage['reset_at']) < time()) {
                // Reset the counter
                $this->reset($ipAddress);
                // Get updated record
                $usage = $this->getByIp($ipAddress);
            }
            return $usage;
        }
        
        // Create new record
        $this->create($ipAddress);
        return $this->getByIp($ipAddress);
    }

    /**
     * Increment summary count for guest
     */
    public function incrementSummaries(string $ipAddress): bool
    {
        $driver = Database::getDriver();
        $timestamp = $driver === 'pgsql' ? 'CURRENT_TIMESTAMP' : 'NOW()';
        
        $sql = "UPDATE {$this->table} 
                SET summaries_count = summaries_count + 1,
                    last_summary_at = {$timestamp}
                WHERE ip_address = :ip_address";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':ip_address' => $ipAddress]);
    }

    /**
     * Reset guest usage (after 24 hours)
     */
    public function reset(string $ipAddress): bool
    {
        $resetAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $sql = "UPDATE {$this->table} 
                SET summaries_count = 0,
                    reset_at = :reset_at
                WHERE ip_address = :ip_address";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':ip_address' => $ipAddress,
            ':reset_at' => $resetAt
        ]);
    }

    /**
     * Check if guest has exceeded daily limit
     */
    public function hasExceededLimit(string $ipAddress, int $limit = 3): bool
    {
        $usage = $this->getByIp($ipAddress);
        
        if (!$usage) {
            return false; // No usage record = not exceeded
        }

        // Check if reset time has passed
        if (strtotime($usage['reset_at']) < time()) {
            // Reset time passed, reset the counter
            $this->reset($ipAddress);
            return false;
        }

        // Check if count exceeded
        return $usage['summaries_count'] >= $limit;
    }

    /**
     * Get remaining summaries for guest
     */
    public function getRemainingCount(string $ipAddress, int $limit = 3): int
    {
        $usage = $this->getByIp($ipAddress);
        
        if (!$usage) {
            return $limit; // No usage = full limit available
        }

        // Check if reset time has passed
        if (strtotime($usage['reset_at']) < time()) {
            return $limit; // Reset time passed = full limit
        }

        $remaining = $limit - $usage['summaries_count'];
        return max(0, $remaining);
    }

    /**
     * Clean up expired records (for maintenance)
     */
    public function cleanupExpired(): int
    {
        $sql = "DELETE FROM {$this->table} WHERE reset_at < CURRENT_TIMESTAMP";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
}