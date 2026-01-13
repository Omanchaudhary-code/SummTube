<?php
/**
 * Database Normalization Migration Script
 * 
 * This script migrates from the old denormalized schema to the new normalized schema:
 * - Creates videos table (normalized video metadata)
 * - Migrates video data from summaries to videos
 * - Updates summaries to reference videos.id instead of storing video data directly
 * - Ensures only logged-in users can track history (user_id is required)
 * 
 * Usage: php migrate_normalize.php
 * 
 * ⚠️  IMPORTANT: Backup your database before running this migration!
 */

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "========================================\n";
echo "🔄 SummTube - Database Normalization Migration\n";
echo "========================================\n\n";

echo "📍 Target Database:\n";
echo "   Host: " . ($_ENV['DB_HOST'] ?? 'N/A') . "\n";
echo "   Database: " . ($_ENV['DB_DATABASE'] ?? 'N/A') . "\n";
echo "   Username: " . ($_ENV['DB_USERNAME'] ?? 'N/A') . "\n";
echo "   Driver: " . ($_ENV['DB_CONNECTION'] ?? 'pgsql') . "\n\n";

// Confirm before proceeding
echo "⚠️  WARNING: This migration will:\n";
echo "   1. Create a new 'videos' table\n";
echo "   2. Migrate video data from 'summaries' to 'videos'\n";
echo "   3. Update 'summaries' to reference 'videos.id'\n";
echo "   4. Remove video columns from 'summaries' table\n";
echo "   5. Ensure only logged-in users can have summaries (user_id required)\n\n";
echo "⚠️  BACKUP YOUR DATABASE BEFORE PROCEEDING!\n\n";
echo "Continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
if (strtolower($line) !== 'yes') {
    echo "❌ Migration aborted.\n";
    exit(0);
}
fclose($handle);

try {
    $db = Core\Database::getInstance();
    $driver = Core\Database::getDriver();
    
    echo "\n✅ Connected to database!\n";
    echo "   Driver: $driver\n\n";
    
    // Start transaction
    $db->beginTransaction();
    
    echo "========================================\n";
    echo "STEP 1: Creating videos table\n";
    echo "========================================\n";
    
    // Check if videos table already exists
    if ($driver === 'pgsql') {
        $checkStmt = $db->query("SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'videos'
        )");
        $videosExists = $checkStmt->fetch(PDO::FETCH_COLUMN);
    } else {
        $checkStmt = $db->query("SHOW TABLES LIKE 'videos'");
        $videosExists = $checkStmt->rowCount() > 0;
    }
    
    if ($videosExists) {
        echo "⚠️  Videos table already exists. Skipping creation.\n";
    } else {
        // Create videos table
        $createVideosSql = "
        CREATE TABLE videos (
            id SERIAL PRIMARY KEY,
            video_id VARCHAR(50) UNIQUE NOT NULL,
            video_url VARCHAR(500) NOT NULL,
            title TEXT NOT NULL,
            thumbnail TEXT,
            duration INTEGER,
            channel_name VARCHAR(255),
            published_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT chk_duration_positive CHECK (duration >= 0)
        );
        
        CREATE INDEX idx_videos_video_id ON videos(video_id);
        CREATE INDEX idx_videos_url ON videos(video_url);
        CREATE INDEX idx_videos_title ON videos(title);
        ";
        
        // Execute for PostgreSQL
        if ($driver === 'pgsql') {
            $db->exec($createVideosSql);
        } else {
            // MySQL version
            $createVideosSql = "
            CREATE TABLE videos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                video_id VARCHAR(50) UNIQUE NOT NULL,
                video_url VARCHAR(500) NOT NULL,
                title TEXT NOT NULL,
                thumbnail TEXT,
                duration INT,
                channel_name VARCHAR(255),
                published_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CHECK (duration >= 0)
            );
            
            CREATE INDEX idx_videos_video_id ON videos(video_id);
            CREATE INDEX idx_videos_url ON videos(video_url);
            CREATE INDEX idx_videos_title ON videos(title(255));
            ";
            $db->exec($createVideosSql);
        }
        
        echo "✅ Videos table created successfully!\n";
    }
    
    echo "\n========================================\n";
    echo "STEP 2: Migrating video data to videos table\n";
    echo "========================================\n";
    
    // Check if summaries table exists and has data
    if ($driver === 'pgsql') {
        $checkStmt = $db->query("SELECT EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'summaries'
        )");
        $summariesExists = $checkStmt->fetch(PDO::FETCH_COLUMN);
    } else {
        $checkStmt = $db->query("SHOW TABLES LIKE 'summaries'");
        $summariesExists = $checkStmt->rowCount() > 0;
    }
    
    if (!$summariesExists) {
        echo "⚠️  Summaries table does not exist. Skipping data migration.\n";
    } else {
        // Check if summaries table has the old columns
        $columns = [];
        if ($driver === 'pgsql') {
            $colStmt = $db->query("
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_name = 'summaries'
            ");
            $columns = $colStmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $colStmt = $db->query("SHOW COLUMNS FROM summaries");
            $colResults = $colStmt->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_column($colResults, 'Field');
        }
        
        $hasOldColumns = in_array('video_url', $columns) || in_array('video_title', $columns);
        
        if (!$hasOldColumns) {
            echo "⚠️  Summaries table appears to already be normalized. Skipping data migration.\n";
        } else {
            // Get unique videos from summaries
            echo "   Extracting unique videos from summaries...\n";
            
            $selectSql = "
                SELECT DISTINCT 
                    COALESCE(video_id, 'unknown_' || id::text) as video_id,
                    video_url,
                    video_title as title,
                    thumbnail,
                    duration
                FROM summaries
                WHERE video_url IS NOT NULL
            ";
            
            $stmt = $db->query($selectSql);
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $videoCount = count($videos);
            echo "   Found $videoCount unique videos to migrate.\n";
            
            if ($videoCount > 0) {
                // Insert videos (handle duplicates)
                $insertSql = "
                    INSERT INTO videos (video_id, video_url, title, thumbnail, duration)
                    VALUES (:video_id, :video_url, :title, :thumbnail, :duration)
                ";
                
                if ($driver === 'pgsql') {
                    $insertSql .= " ON CONFLICT (video_id) DO UPDATE SET
                        video_url = EXCLUDED.video_url,
                        title = EXCLUDED.title,
                        thumbnail = COALESCE(EXCLUDED.thumbnail, videos.thumbnail),
                        duration = COALESCE(EXCLUDED.duration, videos.duration),
                        updated_at = CURRENT_TIMESTAMP
                    ";
                } else {
                    $insertSql = "
                        INSERT INTO videos (video_id, video_url, title, thumbnail, duration)
                        VALUES (:video_id, :video_url, :title, :thumbnail, :duration)
                        ON DUPLICATE KEY UPDATE
                            video_url = VALUES(video_url),
                            title = VALUES(title),
                            thumbnail = COALESCE(VALUES(thumbnail), videos.thumbnail),
                            duration = COALESCE(VALUES(duration), videos.duration),
                            updated_at = CURRENT_TIMESTAMP
                    ";
                }
                
                $insertStmt = $db->prepare($insertSql);
                $inserted = 0;
                
                foreach ($videos as $video) {
                    try {
                        $insertStmt->execute([
                            ':video_id' => $video['video_id'] ?? 'unknown',
                            ':video_url' => $video['video_url'] ?? '',
                            ':title' => $video['title'] ?? 'Unknown',
                            ':thumbnail' => $video['thumbnail'] ?? null,
                            ':duration' => $video['duration'] ?? 0
                        ]);
                        $inserted++;
                    } catch (PDOException $e) {
                        // Skip duplicates or errors
                        if (strpos($e->getMessage(), 'duplicate') === false && 
                            strpos($e->getMessage(), 'UNIQUE') === false) {
                            echo "      ⚠️  Error inserting video {$video['video_id']}: " . $e->getMessage() . "\n";
                        }
                    }
                }
                
                echo "   ✅ Migrated $inserted videos to videos table.\n";
            }
        }
    }
    
    echo "\n========================================\n";
    echo "STEP 3: Adding video_id foreign key column to summaries\n";
    echo "========================================\n";
    
    // Check if summaries already has the new video_id (INTEGER) column
    $hasNewVideoId = false;
    if (isset($columns)) {
        // Check if there's a video_id column that's INTEGER (foreign key)
        if ($driver === 'pgsql') {
            $colStmt = $db->query("
                SELECT data_type 
                FROM information_schema.columns 
                WHERE table_name = 'summaries' 
                AND column_name = 'video_id'
            ");
            $result = $colStmt->fetch(PDO::FETCH_ASSOC);
            if ($result && in_array($result['data_type'], ['integer', 'int4', 'bigint'])) {
                $hasNewVideoId = true;
            }
        } else {
            $colStmt = $db->query("
                SELECT DATA_TYPE 
                FROM information_schema.columns 
                WHERE table_name = 'summaries' 
                AND column_name = 'video_id'
            ");
            $result = $colStmt->fetch(PDO::FETCH_ASSOC);
            if ($result && in_array($result['DATA_TYPE'], ['int', 'bigint'])) {
                $hasNewVideoId = true;
            }
        }
    }
    
    if ($hasNewVideoId) {
        echo "⚠️  Summaries table already has video_id foreign key column. Skipping.\n";
    } else {
        // Add temporary column for new video_id (INTEGER foreign key)
        echo "   Adding video_id_fk column (temporary)...\n";
        
        if ($driver === 'pgsql') {
            $db->exec("ALTER TABLE summaries ADD COLUMN IF NOT EXISTS video_id_fk INTEGER");
        } else {
            $db->exec("ALTER TABLE summaries ADD COLUMN video_id_fk INT");
        }
        
        echo "   ✅ Column added.\n";
        
        // Update video_id_fk to reference videos.id based on matching video_id (YouTube ID)
        echo "   Updating video_id_fk to reference videos.id...\n";
        
        $updateSql = "
            UPDATE summaries s
            SET video_id_fk = v.id
            FROM videos v
            WHERE s.video_id = v.video_id
        ";
        
        if ($driver !== 'pgsql') {
            $updateSql = "
                UPDATE summaries s
                INNER JOIN videos v ON s.video_id = v.video_id
                SET s.video_id_fk = v.id
            ";
        }
        
        $updateStmt = $db->exec($updateSql);
        $updatedRows = $updateStmt;
        
        echo "   ✅ Updated $updatedRows summaries to reference videos.\n";
        
        // Handle summaries without matching videos (create placeholder videos)
        echo "   Creating placeholder videos for summaries without matches...\n";
        
        $orphanSql = "
            SELECT DISTINCT s.video_id, s.video_url, s.video_title, s.thumbnail, s.duration
            FROM summaries s
            WHERE s.video_id_fk IS NULL 
            AND s.video_id IS NOT NULL
        ";
        
        $orphanStmt = $db->query($orphanSql);
        $orphans = $orphanStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($orphans) > 0) {
            $placeholderStmt = $db->prepare($insertSql);
            $created = 0;
            
            foreach ($orphans as $orphan) {
                try {
                    $placeholderStmt->execute([
                        ':video_id' => $orphan['video_id'] ?? 'unknown',
                        ':video_url' => $orphan['video_url'] ?? '',
                        ':title' => $orphan['video_title'] ?? 'Unknown',
                        ':thumbnail' => $orphan['thumbnail'] ?? null,
                        ':duration' => $orphan['duration'] ?? 0
                    ]);
                    $created++;
                } catch (PDOException $e) {
                    // Skip errors
                }
            }
            
            // Update orphan summaries again
            $db->exec($updateSql);
            
            echo "   ✅ Created $created placeholder videos and updated summaries.\n";
        }
        
        // Drop old video_id VARCHAR column
        echo "   Dropping old video_id VARCHAR column...\n";
        
        if ($driver === 'pgsql') {
            $db->exec("ALTER TABLE summaries DROP COLUMN IF EXISTS video_id");
        } else {
            $db->exec("ALTER TABLE summaries DROP COLUMN video_id");
        }
        
        // Rename video_id_fk to video_id
        echo "   Renaming video_id_fk to video_id...\n";
        
        if ($driver === 'pgsql') {
            $db->exec("ALTER TABLE summaries RENAME COLUMN video_id_fk TO video_id");
        } else {
            $db->exec("ALTER TABLE summaries CHANGE COLUMN video_id_fk video_id INT");
        }
        
        // Make video_id NOT NULL (after ensuring all rows have values)
        echo "   Setting video_id as NOT NULL...\n";
        
        // First, handle any NULL values by creating a default video
        $nullCountStmt = $db->query("SELECT COUNT(*) FROM summaries WHERE video_id IS NULL");
        $nullCount = $nullCountStmt->fetch(PDO::FETCH_COLUMN);
        
        if ($nullCount > 0) {
            echo "      ⚠️  Found $nullCount summaries with NULL video_id. Creating default video...\n";
            
            // Create a default "unknown" video
            $defaultVideoSql = "
                INSERT INTO videos (video_id, video_url, title)
                VALUES ('unknown', 'https://youtube.com/watch?v=unknown', 'Unknown Video')
            ";
            
            if ($driver === 'pgsql') {
                $defaultVideoSql .= " ON CONFLICT (video_id) DO NOTHING";
            } else {
                $defaultVideoSql .= " ON DUPLICATE KEY UPDATE video_id = video_id";
            }
            
            $db->exec($defaultVideoSql);
            
            // Get the default video ID
            $defaultVideoStmt = $db->query("SELECT id FROM videos WHERE video_id = 'unknown'");
            $defaultVideoId = $defaultVideoStmt->fetch(PDO::FETCH_COLUMN);
            
            // Update NULL video_ids
            $db->exec("UPDATE summaries SET video_id = $defaultVideoId WHERE video_id IS NULL");
            
            echo "      ✅ Updated NULL values to default video.\n";
        }
        
        // Now make it NOT NULL
        if ($driver === 'pgsql') {
            $db->exec("ALTER TABLE summaries ALTER COLUMN video_id SET NOT NULL");
        } else {
            $db->exec("ALTER TABLE summaries MODIFY COLUMN video_id INT NOT NULL");
        }
        
        // Add foreign key constraint
        echo "   Adding foreign key constraint...\n";
        
        if ($driver === 'pgsql') {
            $db->exec("
                ALTER TABLE summaries 
                ADD CONSTRAINT fk_summaries_video_id 
                FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
            ");
        } else {
            $db->exec("
                ALTER TABLE summaries 
                ADD CONSTRAINT fk_summaries_video_id 
                FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
            ");
        }
        
        echo "   ✅ Foreign key constraint added.\n";
    }
    
    echo "\n========================================\n";
    echo "STEP 4: Removing old video columns from summaries\n";
    echo "========================================\n";
    
    // Remove old columns (video_url, video_title, thumbnail, duration)
    $columnsToRemove = ['video_url', 'video_title', 'thumbnail', 'duration'];
    
    foreach ($columnsToRemove as $column) {
        if (in_array($column, $columns ?? [])) {
            echo "   Removing column: $column...\n";
            
            if ($driver === 'pgsql') {
                $db->exec("ALTER TABLE summaries DROP COLUMN IF EXISTS $column");
            } else {
                $db->exec("ALTER TABLE summaries DROP COLUMN $column");
            }
            
            echo "      ✅ Removed $column.\n";
        } else {
            echo "      ⚠️  Column $column does not exist. Skipping.\n";
        }
    }
    
    echo "\n========================================\n";
    echo "STEP 5: Ensuring user_id is required (only logged-in users)\n";
    echo "========================================\n";
    
    // Check if user_id is already NOT NULL
    if ($driver === 'pgsql') {
        $checkStmt = $db->query("
            SELECT is_nullable 
            FROM information_schema.columns 
            WHERE table_name = 'summaries' 
            AND column_name = 'user_id'
        ");
        $isNullable = $checkStmt->fetch(PDO::FETCH_COLUMN);
    } else {
        $checkStmt = $db->query("
            SELECT IS_NULLABLE 
            FROM information_schema.columns 
            WHERE table_name = 'summaries' 
            AND column_name = 'user_id'
        ");
        $isNullable = $checkStmt->fetch(PDO::FETCH_COLUMN);
    }
    
    if ($isNullable === 'NO' || $isNullable === false) {
        echo "✅ user_id is already NOT NULL. Only logged-in users can have summaries.\n";
    } else {
        // Check for NULL user_ids
        $nullUserStmt = $db->query("SELECT COUNT(*) FROM summaries WHERE user_id IS NULL");
        $nullUserCount = $nullUserStmt->fetch(PDO::FETCH_COLUMN);
        
        if ($nullUserCount > 0) {
            echo "⚠️  Found $nullUserCount summaries with NULL user_id.\n";
            echo "   These will be deleted as only logged-in users can track history.\n";
            
            $db->exec("DELETE FROM summaries WHERE user_id IS NULL");
            echo "   ✅ Deleted summaries without user_id.\n";
        }
        
        // Make user_id NOT NULL
        echo "   Setting user_id as NOT NULL...\n";
        
        if ($driver === 'pgsql') {
            $db->exec("ALTER TABLE summaries ALTER COLUMN user_id SET NOT NULL");
        } else {
            $db->exec("ALTER TABLE summaries MODIFY COLUMN user_id INT NOT NULL");
        }
        
        echo "   ✅ user_id is now required. Only logged-in users can track history.\n";
    }
    
    echo "\n========================================\n";
    echo "STEP 6: Creating indexes and optimizing\n";
    echo "========================================\n";
    
    // Create indexes if they don't exist
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_summaries_user_id ON summaries(user_id)",
        "CREATE INDEX IF NOT EXISTS idx_summaries_video_id ON summaries(video_id)",
        "CREATE INDEX IF NOT EXISTS idx_summaries_created_at ON summaries(created_at DESC)",
        "CREATE INDEX IF NOT EXISTS idx_summaries_user_created ON summaries(user_id, created_at DESC)"
    ];
    
    foreach ($indexes as $indexSql) {
        try {
            if ($driver !== 'pgsql') {
                // MySQL doesn't support IF NOT EXISTS for indexes
                $indexSql = str_replace(' IF NOT EXISTS', '', $indexSql);
            }
            $db->exec($indexSql);
            echo "   ✅ Index created/verified.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key') === false && 
                strpos($e->getMessage(), 'already exists') === false) {
                echo "      ⚠️  Index creation warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Commit transaction
    $db->commit();
    
    echo "\n========================================\n";
    echo "🎉 Migration completed successfully!\n";
    echo "========================================\n\n";
    
    // Verify migration
    echo "📊 Verifying migration...\n\n";
    
    // Count videos
    $videoCountStmt = $db->query("SELECT COUNT(*) FROM videos");
    $videoCount = $videoCountStmt->fetch(PDO::FETCH_COLUMN);
    echo "   Videos table: $videoCount videos\n";
    
    // Count summaries
    $summaryCountStmt = $db->query("SELECT COUNT(*) FROM summaries");
    $summaryCount = $summaryCountStmt->fetch(PDO::FETCH_COLUMN);
    echo "   Summaries table: $summaryCount summaries\n";
    
    // Check foreign key relationships
    $fkCheckStmt = $db->query("
        SELECT COUNT(*) 
        FROM summaries s
        INNER JOIN videos v ON s.video_id = v.id
    ");
    $linkedCount = $fkCheckStmt->fetch(PDO::FETCH_COLUMN);
    echo "   Linked summaries: $linkedCount summaries\n";
    
    if ($linkedCount == $summaryCount) {
        echo "\n✅ All summaries are properly linked to videos!\n";
    } else {
        echo "\n⚠️  Warning: Some summaries may not be linked to videos.\n";
    }
    
    echo "\n✅ Database normalization complete!\n";
    echo "\n📝 Next steps:\n";
    echo "   1. Update your application code to use the new normalized schema\n";
    echo "   2. Update Summary model to join with videos table\n";
    echo "   3. Test your application thoroughly\n";
    echo "   4. Update API responses to include video data from videos table\n\n";
    
} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
        echo "\n❌ Error occurred. Transaction rolled back.\n";
    }
    
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    echo "\n💡 Troubleshooting:\n";
    echo "   1. Check your database connection\n";
    echo "   2. Verify you have the correct permissions\n";
    echo "   3. Ensure all required tables exist\n";
    echo "   4. Check database logs for detailed errors\n";
    exit(1);
}
