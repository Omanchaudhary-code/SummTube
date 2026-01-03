<?php

/**
 * Neon PostgreSQL Setup Script
 * Interactive script to configure Neon database
 * 
 * Usage: php backend/setup-neon.php
 */

echo "\n";
echo "========================================\n";
echo "🚀 Neon PostgreSQL Setup for SummTube\n";
echo "========================================\n\n";

echo "This script will help you configure Neon PostgreSQL.\n\n";

echo "Step 1: Create a Neon account\n";
echo "-----------------------------------\n";
echo "1. Go to https://neon.tech\n";
echo "2. Click 'Sign Up' and use GitHub or email\n";
echo "3. No credit card required!\n\n";

echo "Press ENTER when you've created your account...";
fgets(STDIN);

echo "\nStep 2: Create a new project\n";
echo "-----------------------------------\n";
echo "1. Click 'Create a project'\n";
echo "2. Name it: summtube\n";
echo "3. Choose your region (closest to you)\n";
echo "4. Click 'Create project'\n\n";

echo "Press ENTER when you've created the project...";
fgets(STDIN);

echo "\nStep 3: Get your connection details\n";
echo "-----------------------------------\n";
echo "You should see a connection string like:\n";
echo "postgresql://username:password@ep-xyz-123.region.neon.tech/neondb\n\n";

// Get connection details from user
echo "Enter your connection details:\n\n";

echo "Host (e.g., ep-xyz-123.us-east-2.aws.neon.tech): ";
$host = trim(fgets(STDIN));

echo "Database (default: neondb): ";
$database = trim(fgets(STDIN));
if (empty($database)) $database = 'neondb';

echo "Username: ";
$username = trim(fgets(STDIN));

echo "Password: ";
$password = trim(fgets(STDIN));

$port = 5432; // PostgreSQL default

echo "\n========================================\n";
echo "Configuration Summary\n";
echo "========================================\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $database\n";
echo "Username: $username\n";
echo "Password: " . str_repeat('*', strlen($password)) . "\n\n";

echo "Is this correct? (y/n): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 'y') {
    echo "\n❌ Setup cancelled. Run the script again.\n\n";
    exit(1);
}

// Update .env file
echo "\nUpdating .env file...\n";

$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);

// Replace database configuration
$envContent = preg_replace(
    '/DB_CONNECTION=.*/m',
    'DB_CONNECTION=pgsql',
    $envContent
);

$envContent = preg_replace(
    '/DB_HOST=.*/m',
    "DB_HOST=$host",
    $envContent
);

$envContent = preg_replace(
    '/DB_PORT=.*/m',
    "DB_PORT=$port",
    $envContent
);

$envContent = preg_replace(
    '/DB_DATABASE=.*/m',
    "DB_DATABASE=$database",
    $envContent
);

$envContent = preg_replace(
    '/DB_USERNAME=.*/m',
    "DB_USERNAME=$username",
    $envContent
);

$envContent = preg_replace(
    '/DB_PASSWORD=.*/m',
    "DB_PASSWORD=$password",
    $envContent
);

file_put_contents($envFile, $envContent);

echo "✅ .env file updated!\n\n";

// Test connection
echo "Testing database connection...\n";

try {
    $_ENV['DB_CONNECTION'] = 'pgsql';
    $_ENV['DB_HOST'] = $host;
    $_ENV['DB_PORT'] = $port;
    $_ENV['DB_DATABASE'] = $database;
    $_ENV['DB_USERNAME'] = $username;
    $_ENV['DB_PASSWORD'] = $password;

    require_once __DIR__ . '/vendor/autoload.php';
    
    $db = Core\Database::getInstance();
    
    // Test query
    $stmt = $db->query("SELECT version() as version");
    $result = $stmt->fetch();
    
    echo "✅ Connection successful!\n";
    echo "PostgreSQL version: " . $result['version'] . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "Please check your credentials and try again.\n\n";
    exit(1);
}

// Create SQL migration file
echo "Creating migration file...\n";

$sqlContent = file_get_contents(__DIR__ . '/postgres_migrations.sql');
if (!$sqlContent) {
    echo "⚠️  Could not find postgres_migrations.sql\n";
    echo "Please run the SQL manually from Neon's SQL Editor.\n\n";
} else {
    echo "✅ Migration file ready at: postgres_migrations.sql\n\n";
}

echo "========================================\n";
echo "Next Steps\n";
echo "========================================\n\n";

echo "1. Run migrations on Neon:\n";
echo "   Option A: Go to Neon dashboard → SQL Editor\n";
echo "             Copy and paste the migrations from postgres_migrations.sql\n\n";
echo "   Option B: Use psql command:\n";
echo "             psql \"postgresql://$username:$password@$host/$database\" < postgres_migrations.sql\n\n";

echo "2. Test your backend:\n";
echo "   php test-backend.php\n\n";

echo "3. Start your server:\n";
echo "   cd public && php -S localhost:8000\n\n";

echo "4. Test API:\n";
echo "   curl http://localhost:8000/api/health\n\n";

echo "🎉 Setup complete! Your backend is now using Neon PostgreSQL!\n\n";