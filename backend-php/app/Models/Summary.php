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
     * Now uses normalized schema: video_id is INTEGER foreign key to videos table
     */
    public function create(array $data): int
    {
        $driver = Database::getDriver();

        // video_id is now INTEGER (foreign key), not VARCHAR
        $sql = "INSERT INTO {$this->table} (
            user_id, 
            video_id,
            original_text,
            summary_text,
            summary_type, 
            transcript_length,
            processing_time,
            created_at
        ) VALUES (
            :user_id, 
            :video_id,
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
                ':video_id' => $data['video_id'], // INTEGER foreign key
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
                ':video_id' => $data['video_id'], // INTEGER foreign key
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
     * Now JOINs with videos table for normalized data
     */
    public function getByUserId(int $userId, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT 
            s.id, 
            s.summary_text as summary, 
            s.summary_type,
            s.transcript_length,
            s.processing_time,
            s.created_at,
            s.updated_at,
            v.video_id,
            v.video_url, 
            v.title as video_title, 
            v.thumbnail,
            v.duration
        FROM {$this->table} s
        INNER JOIN videos v ON s.video_id = v.id
        WHERE s.user_id = :user_id 
        ORDER BY s.created_at DESC 
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
     * Now JOINs with videos table for normalized data
     */
    public function getByIdAndUserId(int $id, int $userId): ?array
    {
        $sql = "SELECT 
            s.id,
            s.user_id,
            s.original_text,
            s.summary_text as summary,
            s.summary_type,
            s.transcript_length,
            s.processing_time,
            s.created_at,
            s.updated_at,
            v.video_id,
            v.video_url,
            v.title as video_title,
            v.thumbnail,
            v.duration
        FROM {$this->table} s
        INNER JOIN videos v ON s.video_id = v.id
        WHERE s.id = :id AND s.user_id = :user_id 
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
     * Now uses videos table to find by video_url
     */
    public function findByVideoUrl(int $userId, string $videoUrl): ?array
    {
        $sql = "SELECT s.*, v.video_id, v.video_url, v.title as video_title, v.thumbnail, v.duration
                FROM {$this->table} s
                INNER JOIN videos v ON s.video_id = v.id
                WHERE s.user_id = :user_id 
                AND v.video_url = :video_url 
                ORDER BY s.created_at DESC 
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
     * Now JOINs with videos table for normalized data
     */
    public function getRecent(int $userId, int $limit = 5): array
    {
        $sql = "SELECT 
            s.id,
            s.summary_text as summary,
            s.summary_type,
            s.created_at,
            v.video_id,
            v.video_url,
            v.title as video_title,
            v.thumbnail,
            v.duration
        FROM {$this->table} s
        INNER JOIN videos v ON s.video_id = v.id
        WHERE s.user_id = :user_id
        ORDER BY s.created_at DESC
        LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search summaries by title or summary text
     * Now JOINs with videos table for normalized data
     */
    public function search(int $userId, string $query, int $limit = 20, int $offset = 0): array
    {
        $driver = Database::getDriver();
        $likeOperator = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
        
        $sql = "SELECT 
            s.id,
            s.summary_text as summary,
            s.summary_type,
            s.created_at,
            v.video_id,
            v.video_url,
            v.title as video_title,
            v.thumbnail,
            v.duration
        FROM {$this->table} s
        INNER JOIN videos v ON s.video_id = v.id
        WHERE s.user_id = :user_id 
        AND (v.title $likeOperator :query OR s.summary_text $likeOperator :query)
        ORDER BY s.created_at DESC
        LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':query', '%' . $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get or create video in videos table
     * Returns the video ID (INTEGER) for use as foreign key
     */
    public function getOrCreateVideo(array $videoData): int
    {
        $driver = Database::getDriver();
        $youtubeVideoId = $videoData['video_id'] ?? null;
        
        if (empty($youtubeVideoId)) {
            throw new \Exception("Video ID is required");
        }
        
        // Check if video exists
        $checkSql = "SELECT id FROM videos WHERE video_id = :video_id LIMIT 1";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([':video_id' => $youtubeVideoId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            return (int) $existing['id'];
        }
        
        // Create new video
        $insertSql = "INSERT INTO videos (video_id, video_url, title, thumbnail, duration)
                      VALUES (:video_id, :video_url, :title, :thumbnail, :duration)";
        
        if ($driver === 'pgsql') {
            $insertSql .= " RETURNING id";
        }
        
        $insertStmt = $this->db->prepare($insertSql);
        $insertStmt->execute([
            ':video_id' => $youtubeVideoId,
            ':video_url' => $videoData['video_url'] ?? '',
            ':title' => $videoData['title'] ?? $videoData['video_title'] ?? 'Unknown',
            ':thumbnail' => $videoData['thumbnail'] ?? null,
            ':duration' => $videoData['duration'] ?? 0
        ]);
        
        if ($driver === 'pgsql') {
            $result = $insertStmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['id'];
        } else {
            return (int) $this->db->lastInsertId();
        }
    }
}