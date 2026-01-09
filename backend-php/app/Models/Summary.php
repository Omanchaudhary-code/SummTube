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
        $driver = Database::getDriver();

        $sql = "INSERT INTO {$this->table} (
            user_id, 
            video_url, 
            video_id,
            video_title, 
            thumbnail,
            duration,
            original_text,
            summary_text,
            summary_type, 
            transcript_length,
            processing_time,
            created_at
        ) VALUES (
            :user_id, 
            :video_url, 
            :video_id,
            :video_title, 
            :thumbnail,
            :duration,
            :original_text,
            :summary_text,
            :summary_type,
            :transcript_length,
            :processing_time,
            NOW()
        )";

        if ($driver === 'pgsql') {
            $sql .= " RETURNING id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':video_url' => $data['video_url'],
                ':video_id' => $data['video_id'] ?? null,
                ':video_title' => $data['video_title'] ?? 'Unknown',
                ':thumbnail' => $data['thumbnail'] ?? null,
                ':duration' => $data['duration'] ?? 0,
                ':original_text' => $data['original_text'] ?? '',
                ':summary_text' => $data['summary_text'],
                ':summary_type' => $data['summary_type'] ?? 'detailed',
                ':transcript_length' => $data['transcript_length'] ?? 0,
                ':processing_time' => $data['processing_time'] ?? 0
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['id'];
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':video_url' => $data['video_url'],
                ':video_id' => $data['video_id'] ?? null,
                ':video_title' => $data['video_title'] ?? 'Unknown',
                ':thumbnail' => $data['thumbnail'] ?? null,
                ':duration' => $data['duration'] ?? 0,
                ':original_text' => $data['original_text'] ?? '',
                ':summary_text' => $data['summary_text'],
                ':summary_type' => $data['summary_type'] ?? 'detailed',
                ':transcript_length' => $data['transcript_length'] ?? 0,
                ':processing_time' => $data['processing_time'] ?? 0
            ]);
            return (int) $this->db->lastInsertId();
        }
    }

    /**
     * Get all summaries by user ID with pagination
     */
    public function getByUserId(int $userId, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT 
            id, 
            video_url, 
            video_id,
            video_title, 
            thumbnail,
            duration,
            summary_text as summary, 
            summary_type,
            transcript_length,
            processing_time,
            created_at 
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
        $sql = "SELECT 
            id,
            user_id,
            video_url,
            video_id,
            video_title,
            thumbnail,
            duration,
            original_text,
            summary_text as summary,
            summary_type,
            transcript_length,
            processing_time,
            created_at,
            updated_at
        FROM {$this->table} 
        WHERE id = :id AND user_id = :user_id 
        LIMIT 1";

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
        $sql = "DELETE FROM {$this->table} 
                WHERE id = :id AND user_id = :user_id";

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
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} 
                WHERE user_id = :user_id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Check if video was already summarized by user
     */
    public function findByVideoUrl(int $userId, string $videoUrl): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = :user_id 
                AND video_url = :video_url 
                ORDER BY created_at DESC 
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':video_url' => $videoUrl
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get recent summaries (for dashboard/home page)
     */
    public function getRecent(int $userId, int $limit = 5): array
    {
        $sql = "SELECT 
            id,
            video_url,
            video_id,
            video_title,
            thumbnail,
            duration,
            summary_text as summary,
            summary_type,
            created_at
        FROM {$this->table}
        WHERE user_id = :user_id
        ORDER BY created_at DESC
        LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search summaries by title
     */
    public function search(int $userId, string $query, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT 
            id,
            video_url,
            video_id,
            video_title,
            thumbnail,
            duration,
            summary_text as summary,
            summary_type,
            created_at
        FROM {$this->table}
        WHERE user_id = :user_id 
        AND (video_title ILIKE :query OR summary_text ILIKE :query)
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}