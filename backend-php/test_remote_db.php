<?php
/**
 * Test remote database connection
 * Usage: php test_remote_db.php
 */

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "========================================\n";
echo "🔍 Testing Remote Database Connection\n";
echo "========================================\n\n";

echo "📍 Connection Details:\n";
echo "   Host: " . $_ENV['DB_HOST'] . "\n";
echo "   Port: " . ($_ENV['DB_PORT'] ?? 3306) . "\n";
echo "   Database: " . $_ENV['DB_DATABASE'] . "\n";
echo "   Username: " . $_ENV['DB_USERNAME'] . "\n";
echo "   Password: " . (empty($_ENV['DB_PASSWORD']) ? '(empty)' : str_repeat('*', 10)) . "\n\n";

try {
    echo "⏳ Connecting...\n";
    $startTime = microtime(true);
    
    $db = Core\Database::getInstance();
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    echo "✅ Connected successfully! ({$duration}ms)\n\n";
    
    // Get database info
    echo "📊 Database Information:\n";
    $stmt = $db->query("SELECT DATABASE() as db_name, VERSION() as version, @@character_set_database as charset");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   Database: " . $result['db_name'] . "\n";
    echo "   MySQL Version: " . $result['version'] . "\n";
    echo "   Charset: " . $result['charset'] . "\n\n";
    
    // Show tables
    echo "📋 Existing Tables:\n";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "   (No tables found - run migrate_remote.php)\n\n";
    } else {
        foreach ($tables as $table) {
            // Get table info
            $countStmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $sizeStmt = $db->query("
                SELECT 
                    ROUND(((data_length + index_length) / 1024), 2) as size_kb
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE() 
                AND table_name = '$table'
            ");
            $size = $sizeStmt->fetch(PDO::FETCH_ASSOC)['size_kb'];
            
            echo "   ✓ $table - $count rows, {$size}KB\n";
        }
        echo "\n";
    }
    
    // Test write operation
    echo "🧪 Testing Write Permission...\n";
    try {
        $testTable = "test_connection_" . time();
        $db->exec("CREATE TABLE `$testTable` (id INT PRIMARY KEY)");
        $db->exec("DROP TABLE `$testTable`");
        echo "   ✅ Write permission OK\n\n";
    } catch (Exception $e) {
        echo "   ⚠️  Write permission issue: " . $e->getMessage() . "\n\n";
    }
    
    echo "========================================\n";
    echo "✅ All tests passed!\n";
    echo "========================================\n\n";
    
    echo "💡 Next Steps:\n";
    if (empty($tables)) {
        echo "   1. Run: php migrate_remote.php\n";
        echo "   2. Start your PHP server\n";
        echo "   3. Test your API endpoints\n";
    } else {
        echo "   1. Start your PHP server: php -S localhost:8080 -t public\n";
        echo "   2. Test your API with Postman/cURL\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Connection Failed!\n";
    echo "========================================\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    echo "💡 Troubleshooting Steps:\n";
    echo "   1. Check .env file has correct credentials\n";
    echo "   2. Verify database server is running\n";
    echo "   3. Check firewall/security groups\n";
    echo "   4. Ensure your IP is whitelisted\n";
    echo "   5. Test with: mysql -h HOST -u USER -p DATABASE\n\n";
    
    // Check if it's a local connection attempt
    if (strpos($_ENV['DB_HOST'], '127.0.0.1') !== false || strpos($_ENV['DB_HOST'], 'localhost') !== false) {
        echo "⚠️  You're connecting to localhost!\n";
        echo "   Update .env with your remote database credentials.\n\n";
    }
    
    exit(1);
}