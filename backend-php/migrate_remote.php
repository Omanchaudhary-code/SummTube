<?php
/**
 * Run migrations on remote database
 * Usage: php migrate_remote.php
 */

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "========================================\n";
echo "🚀 SummTube - Remote Database Migration\n";
echo "========================================\n\n";

echo "📍 Target Database:\n";
echo "   Host: " . $_ENV['DB_HOST'] . "\n";
echo "   Database: " . $_ENV['DB_DATABASE'] . "\n";
echo "   Username: " . $_ENV['DB_USERNAME'] . "\n\n";

// Confirm before proceeding
echo "⚠️  This will create tables in the REMOTE database.\n";
echo "Continue? (y/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) != 'y') {
    echo "❌ Aborted.\n";
    exit(0);
}
fclose($handle);

try {
    $db = Core\Database::getInstance();
    echo "\n✅ Connected to remote database!\n\n";
    
    // List of migration files in order
    $migrations = [
        'database/migrations/001_create_users_table.sql',
        'database/migrations/002_create_summaries_table.sql',
        'database/migrations/003_create_usage_table.sql',
        'database/migrations/004_create_guest_usage_table.sql'
    ];
    
    foreach ($migrations as $index => $file) {
        $number = $index + 1;
        
        if (!file_exists($file)) {
            echo "❌ File not found: $file\n";
            continue;
        }
        
        echo "[$number/4] Running: " . basename($file) . "\n";
        
        // Read SQL file
        $sql = file_get_contents($file);
        
        // Remove comments and split by semicolon
        $sql = preg_replace('/--.*$/m', '', $sql); // Remove SQL comments
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($stmt) => !empty($stmt) && $stmt !== ''
        );
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                try {
                    $db->exec($statement);
                } catch (PDOException $e) {
                    // Ignore "table already exists" errors
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                    echo "      ⚠️  Table already exists, skipping...\n";
                }
            }
        }
        
        echo "      ✅ Completed\n\n";
    }
    
    echo "========================================\n";
    echo "🎉 All migrations completed!\n";
    echo "========================================\n\n";
    
    // Verify tables were created
    echo "📊 Verifying tables...\n";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "⚠️  No tables found!\n";
    } else {
        foreach ($tables as $table) {
            // Get row count
            $countStmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "   ✓ $table ($count rows)\n";
        }
    }
    
    echo "\n✅ Database setup complete!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\n💡 Troubleshooting:\n";
    echo "   1. Check your .env database credentials\n";
    echo "   2. Ensure your database server is accessible\n";
    echo "   3. Verify you have the correct permissions\n";
    exit(1);
}