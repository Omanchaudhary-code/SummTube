
echo "=========================================="
echo "SummTube Backend - Quick Setup & Test"
echo "=========================================="
echo ""

# Check if in backend directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: Not in backend directory"
    echo "Please cd into backend-php directory first"
    exit 1
fi

# Test database connection
echo "1️⃣  Testing database connection..."
mysql -u root -e "USE summtube; SELECT 'Database OK' as status;" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✅ Database connected"
else
    echo "❌ Database connection failed"
    echo "Please create database: CREATE DATABASE summtube;"
    exit 1
fi

# Check if guest_usage table exists with correct structure
echo ""
echo "2️⃣  Checking guest_usage table..."
IDENTIFIER_EXISTS=$(mysql -u root -D summtube -se "SHOW COLUMNS FROM guest_usage LIKE 'identifier';" 2>/dev/null)
if [ -n "$IDENTIFIER_EXISTS" ]; then
    echo "✅ guest_usage table structure OK"
else
    echo "⚠️  Recreating guest_usage table..."
    mysql -u root -D summtube < database/migrations/004_create_guest_usage_table.sql
    echo "✅ Table created"
fi

# Test AI service
echo ""
echo "3️⃣  Testing AI service connection..."
php test_ai_connection.php

echo ""
echo "=========================================="
echo "Setup Complete! Starting server..."
echo "=========================================="
echo ""
echo "Server running at: http://localhost:8080"
echo "Press Ctrl+C to stop"
echo ""
