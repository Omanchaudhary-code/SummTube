<?php
/**
 * Run 002_add_video_columns migration
 * Usage: php apply_002.php
 */

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "========================================\n";
echo "🚀 SummTube - Apply Migration 002\n";
echo "========================================\n\n";

try {
    $db = Core\Database::getInstance();
    echo "✅ Connected to database: " . $_ENV['DB_DATABASE'] . "\n\n";

    $file = '002_add_video_columns.sql';

    if (!file_exists($file)) {
        die("❌ Error: Migration file '$file' not found!\n");
    }

    echo "📄 Reading: $file\n";
    $sql = file_get_contents($file);

    // Split by semicolon but preserve DO $$ blocks
    // This is a simple regex for this specific file, usually a proper SQL parser is better
    // But for this specific migration, we can run it as a single block if the PDO driver supports it,
    // or better, just run it statement by statement.

    // For Neon (PostgreSQL), we can usually run multiple statements in one exec() call.
    echo "⏳ Applying migration...\n";

    $db->exec($sql);

    echo "✅ Migration applied successfully!\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\n💡 Troubleshooting:\n";
    echo "   1. Check your .env database credentials\n";
    echo "   2. Ensure your database server is accessible\n";
    exit(1);
}
