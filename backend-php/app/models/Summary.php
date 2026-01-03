<?php
namespace App\Models;

use Core\Database;
use PDO;

class Summary
{
    private PDO $db;
    private string $table = 'summaries';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create summary (logged-in users only)
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (user_id, video_url, video_title, video_duration, summary_text, created_at) 
                VALUES (:user_id, :video_url, :video_title, :video_duration, :summary_text, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $data['user_id'],
            ':video_url' => $data['video_url'],
            ':video_title' => $data['video_title'] ?? null,
            ':video_duration' => $data['video_duration'] ?? null,
            ':summary_text' => $data['summary_text']
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get all summaries by user ID
     */
    public function getByUserId(int $userId, int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT id, video_url, video_title, video_duration, summary_text, created_at 
                FROM {$this->table} 
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get single summary by ID and user ID
     */
    public function getByIdAndUserId(int $id, int $userId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND user_id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId
        ]);

        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        return $summary ?: null;
    }

    /**
     * Delete summary
     */
    public function delete(int $id, int $userId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Count summaries by user
     */
    public function countByUserId(int $userId): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }
}
