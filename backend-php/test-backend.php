<?php

/**
 * Complete Backend Test Script
 * Tests database, auth system, and API endpoints
 * Supports both MySQL and PostgreSQL
 * 
 * Usage: php backend/test-backend.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Colors for terminal output
class Color {
    public static $GREEN = "\033[0;32m";
    public static $RED = "\033[0;31m";
    public static $YELLOW = "\033[1;33m";
    public static $BLUE = "\033[0;34m";
    public static $NC = "\033[0m"; // No Color
}

// Load environment variables
function loadEnv() {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value, '"\'');
            }
        }
    } else {
        echo Color::$RED . "✗ .env file not found!\n" . Color::$NC;
        echo "Copy .env.example to .env and configure it.\n\n";
        exit(1);
    }
}

loadEnv();

echo Color::$BLUE . "========================================\n";
echo "SummTube Backend Test Suite\n";
echo "========================================\n\n" . Color::$NC;

$allTestsPassed = true;
$warnings = [];

// ==========================================
// TEST 1: Environment Configuration
// ==========================================
echo Color::$BLUE . "Test 1: Environment Configuration\n" . Color::$NC;
echo "-----------------------------------\n";

$requiredEnvVars = [
    'APP_NAME', 'APP_ENV', 'DB_HOST', 'DB_DATABASE', 
    'DB_USERNAME', 'JWT_SECRET'
];

foreach ($requiredEnvVars as $var) {
    if (isset($_ENV[$var]) && !empty($_ENV[$var])) {
        echo Color::$GREEN . "✓" . Color::$NC . " {$var} is set\n";
    } else {
        echo Color::$RED . "✗" . Color::$NC . " {$var} is missing or empty\n";
        $allTestsPassed = false;
    }
}

if (strlen($_ENV['JWT_SECRET'] ?? '') < 32) {
    echo Color::$YELLOW . "⚠" . Color::$NC . " JWT_SECRET is too short (should be 32+ characters)\n";
    $warnings[] = "Generate a secure JWT_SECRET: openssl rand -hex 32";
}

echo "\n";

// ==========================================
// TEST 2: Database Connection
// ==========================================
echo Color::$BLUE . "Test 2: Database Connection\n" . Color::$NC;
echo "-----------------------------------\n";

try {
    $config = Core\Database::getConfig();
    $driver = $config['driver'] ?? $config['connection'] ?? 'mysql';
    
    echo Color::$GREEN . "✓" . Color::$NC . " Database config loaded\n";
    echo "  Driver: {$driver}\n";
    echo "  Host: {$config['host']}:{$config['port']}\n";
    echo "  Database: {$config['database']}\n";
    echo "  Username: {$config['username']}\n";
} catch (Exception $e) {
    echo Color::$RED . "✗" . Color::$NC . " Failed to load database config\n";
    echo "  Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

try {
    $db = Core\Database::getInstance();
    echo Color::$GREEN . "✓" . Color::$NC . " Database connection established\n";
    
    // Verify connection with database-specific query
    if ($driver === 'pgsql') {
        // PostgreSQL query
        $stmt = $db->query("SELECT current_database() as db_name, version() as version");
    } else {
        // MySQL query
        $stmt = $db->query("SELECT DATABASE() as db_name, VERSION() as version");
    }
    
    $result = $stmt->fetch();
    echo "  Connected to: {$result['db_name']}\n";
    echo "  Version: {$result['version']}\n";
} catch (Exception $e) {
    echo Color::$RED . "✗" . Color::$NC . " Database connection failed\n";
    echo "  Error: " . $e->getMessage() . "\n";
    
    if ($driver === 'pgsql') {
        echo "\n" . Color::$YELLOW . "PostgreSQL Fix:\n" . Color::$NC;
        echo "  1. Make sure DB_CONNECTION=pgsql in .env\n";
        echo "  2. Make sure DB_PORT=5432 in .env\n";
        echo "  3. Check your Neon credentials\n\n";
    } else {
        echo "\n" . Color::$YELLOW . "MySQL Fix:\n" . Color::$NC;
        echo "  1. Make sure MySQL is running\n";
        echo "  2. Check your .env database credentials\n";
        echo "  3. Create database: CREATE DATABASE {$config['database']};\n\n";
    }
    exit(1);
}

echo "\n";

// ==========================================
// TEST 3: Database Tables
// ==========================================
echo Color::$BLUE . "Test 3: Database Tables\n" . Color::$NC;
echo "-----------------------------------\n";

$requiredTables = [
    'users' => 'User accounts',
    'summaries' => 'Summary history',
    'usage' => 'User usage tracking',
    'guest_usage' => 'Guest rate limiting',
    'refresh_tokens' => 'Refresh token storage'
];

$missingTables = [];

foreach ($requiredTables as $table => $description) {
    try {
        if ($driver === 'pgsql') {
            // PostgreSQL query - simpler approach
            $stmt = $db->prepare(
                "SELECT COUNT(*) as count 
                 FROM information_schema.tables 
                 WHERE table_schema = 'public' 
                 AND table_name = :table_name"
            );
            $stmt->execute([':table_name' => $table]);
            $exists = $stmt->fetch()['count'] > 0;
        } else {
            // MySQL query
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $exists = $stmt->fetch();
        }
        
        if ($exists) {
            // Count rows - use proper quoting for PostgreSQL
            if ($driver === 'pgsql') {
                $countStmt = $db->query("SELECT COUNT(*) as count FROM \"{$table}\"");
            } else {
                $countStmt = $db->query("SELECT COUNT(*) as count FROM `{$table}`");
            }
            $count = $countStmt->fetch()['count'];
            echo Color::$GREEN . "✓" . Color::$NC . " {$table} ({$description}) - {$count} rows\n";
        } else {
            echo Color::$RED . "✗" . Color::$NC . " {$table} NOT FOUND\n";
            $missingTables[] = $table;
        }
    } catch (Exception $e) {
        echo Color::$RED . "✗" . Color::$NC . " Error checking {$table}: " . $e->getMessage() . "\n";
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    $allTestsPassed = false;
    echo "\n" . Color::$YELLOW . "Missing tables: " . implode(', ', $missingTables) . "\n" . Color::$NC;
    
    if ($driver === 'pgsql') {
        echo "Run migrations in Neon SQL Editor from the provided SQL script\n";
    } else {
        echo "Run migrations from: backend/database/migrations/\n";
    }
}

echo "\n";

// ==========================================
// TEST 4: Core Classes
// ==========================================
echo Color::$BLUE . "Test 4: Core Classes\n" . Color::$NC;
echo "-----------------------------------\n";

$coreClasses = [
    'Core\Router' => 'Router class',
    'Core\Request' => 'Request class',
    'Core\Response' => 'Response class',
    'Core\Database' => 'Database class',
    'Core\Validator' => 'Validator class'
];

foreach ($coreClasses as $class => $description) {
    if (class_exists($class)) {
        echo Color::$GREEN . "✓" . Color::$NC . " {$description} exists\n";
    } else {
        echo Color::$RED . "✗" . Color::$NC . " {$description} missing\n";
        $allTestsPassed = false;
    }
}

echo "\n";

// ==========================================
// TEST 5: Service Classes
// ==========================================
echo Color::$BLUE . "Test 5: Service Classes\n" . Color::$NC;
echo "-----------------------------------\n";

try {
    $jwtService = new App\Services\JWTService();
    echo Color::$GREEN . "✓" . Color::$NC . " JWTService initialized\n";
    
    $authService = new App\Services\AuthService();
    echo Color::$GREEN . "✓" . Color::$NC . " AuthService initialized\n";
    
} catch (Exception $e) {
    echo Color::$RED . "✗" . Color::$NC . " Service initialization failed\n";
    echo "  Error: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

echo "\n";

// ==========================================
// TEST 6: JWT Token Generation
// ==========================================
echo Color::$BLUE . "Test 6: JWT Token System\n" . Color::$NC;
echo "-----------------------------------\n";

try {
    $jwtService = new App\Services\JWTService();
    
    // Test user data
    $testUser = [
        'id' => 999,
        'email' => 'test@example.com',
        'name' => 'Test User'
    ];
    
    // Generate access token
    $accessToken = $jwtService->generateAccessToken($testUser);
    echo Color::$GREEN . "✓" . Color::$NC . " Access token generated\n";
    echo "  Token length: " . strlen($accessToken) . " characters\n";
    
    // Verify access token
    $decoded = $jwtService->verifyAccessToken($accessToken);
    if ($decoded['user_id'] == 999) {
        echo Color::$GREEN . "✓" . Color::$NC . " Access token verification works\n";
    } else {
        throw new Exception("Token verification failed");
    }
    
    // Test token decode
    $parts = $jwtService->decode($accessToken);
    echo Color::$GREEN . "✓" . Color::$NC . " Token decode works\n";
    echo "  Issued at: " . date('Y-m-d H:i:s', $parts['iat']) . "\n";
    echo "  Expires at: " . date('Y-m-d H:i:s', $parts['exp']) . "\n";
    
} catch (Exception $e) {
    echo Color::$RED . "✗" . Color::$NC . " JWT token test failed\n";
    echo "  Error: " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}

echo "\n";

// ==========================================
// TEST 7: Middleware Classes
// ==========================================
echo Color::$BLUE . "Test 7: Middleware Classes\n" . Color::$NC;
echo "-----------------------------------\n";

$middlewareClasses = [
    'App\Middleware\AuthMiddleware' => 'Auth middleware',
    'App\Middleware\CorsMiddleware' => 'CORS middleware',
    'App\Middleware\GuestLimitMiddleware' => 'Guest limit middleware'
];

foreach ($middlewareClasses as $class => $description) {
    if (class_exists($class)) {
        echo Color::$GREEN . "✓" . Color::$NC . " {$description} exists\n";
    } else {
        echo Color::$YELLOW . "⚠" . Color::$NC . " {$description} missing (optional)\n";
        $warnings[] = "Create {$class}";
    }
}

echo "\n";

// ==========================================
// TEST 8: Controller Classes
// ==========================================
echo Color::$BLUE . "Test 8: Controller Classes\n" . Color::$NC;
echo "-----------------------------------\n";

$controllerClasses = [
    'App\Controllers\AuthController' => 'Auth controller',
    'App\Controllers\UserController' => 'User controller',
    'App\Controllers\SummaryController' => 'Summary controller'
];

foreach ($controllerClasses as $class => $description) {
    if (class_exists($class)) {
        echo Color::$GREEN . "✓" . Color::$NC . " {$description} exists\n";
    } else {
        echo Color::$RED . "✗" . Color::$NC . " {$description} missing\n";
        $allTestsPassed = false;
    }
}

echo "\n";

// ==========================================
// TEST 9: Model Classes
// ==========================================
echo Color::$BLUE . "Test 9: Model Classes\n" . Color::$NC;
echo "-----------------------------------\n";

$modelClasses = [
    'App\Models\User' => 'User model',
    'App\Models\Summary' => 'Summary model',
    'App\Models\Usage' => 'Usage model'
];

foreach ($modelClasses as $class => $description) {
    if (class_exists($class)) {
        echo Color::$GREEN . "✓" . Color::$NC . " {$description} exists\n";
    } else {
        echo Color::$RED . "✗" . Color::$NC . " {$description} missing\n";
        $allTestsPassed = false;
    }
}

echo "\n";

// ==========================================
// TEST 10: File Structure
// ==========================================
echo Color::$BLUE . "Test 10: File Structure\n" . Color::$NC;
echo "-----------------------------------\n";

$requiredFiles = [
    'public/index.php' => 'Entry point',
    'routes/api.php' => 'API routes',
    'config/app.php' => 'App config',
    'config/database.php' => 'Database config',
    '.env' => 'Environment file'
];

foreach ($requiredFiles as $file => $description) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo Color::$GREEN . "✓" . Color::$NC . " {$description}: {$file}\n";
    } else {
        echo Color::$RED . "✗" . Color::$NC . " {$description} missing: {$file}\n";
        $allTestsPassed = false;
    }
}

echo "\n";

// ==========================================
// FINAL SUMMARY
// ==========================================
echo Color::$BLUE . "========================================\n";
echo "Test Summary\n";
echo "========================================\n\n" . Color::$NC;

if ($allTestsPassed && empty($warnings)) {
    echo Color::$GREEN . "✓ ALL TESTS PASSED!\n" . Color::$NC;
    echo Color::$GREEN . "✓ Your backend is ready to use!\n\n" . Color::$NC;
    
    $dbType = $driver === 'pgsql' ? 'PostgreSQL (Neon)' : 'MySQL';
    echo "Database: " . Color::$BLUE . $dbType . Color::$NC . "\n\n";
    
    echo Color::$BLUE . "Next Steps:\n" . Color::$NC;
    echo "1. Start the development server:\n";
    echo "   " . Color::$YELLOW . "cd backend/public && php -S localhost:8000\n" . Color::$NC;
    echo "\n2. Test the API:\n";
    echo "   " . Color::$YELLOW . "curl http://localhost:8000/api/health\n" . Color::$NC;
    echo "\n3. Test registration:\n";
    echo "   " . Color::$YELLOW . "curl -X POST http://localhost:8000/api/auth/register \\\n";
    echo "     -H 'Content-Type: application/json' \\\n";
    echo "     -d '{\"email\":\"test@example.com\",\"password\":\"password123\",\"name\":\"Test User\"}'\n" . Color::$NC;
    
} else {
    echo Color::$RED . "✗ SOME TESTS FAILED\n\n" . Color::$NC;
    
    if (!empty($warnings)) {
        echo Color::$YELLOW . "Warnings:\n" . Color::$NC;
        foreach ($warnings as $warning) {
            echo "  ⚠ {$warning}\n";
        }
        echo "\n";
    }
    
    echo Color::$YELLOW . "Action Items:\n" . Color::$NC;
    if (!empty($missingTables)) {
        if ($driver === 'pgsql') {
            echo "1. Run migrations in Neon SQL Editor\n";
            echo "   Go to: https://console.neon.tech\n";
            echo "   Use the SQL provided in the setup guide\n\n";
        } else {
            echo "1. Run database migrations:\n";
            echo "   cd backend/database/migrations\n";
            foreach ($missingTables as $table) {
                echo "   mysql -u root -p summtube < *_{$table}_table.sql\n";
            }
            echo "\n";
        }
    }
    
    echo "2. Fix any missing classes or files\n";
    echo "3. Re-run this test: " . Color::$YELLOW . "php backend/test-backend.php\n" . Color::$NC;
}

echo "\n";