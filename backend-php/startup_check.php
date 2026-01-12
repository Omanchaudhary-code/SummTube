<?php
/**
 * Startup Environment Validation
 * This file validates critical environment variables in production
 * It logs warnings but doesn't break the application if validation fails
 */

$isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';

if ($isProduction) {
    $errors = [];
    
    // Critical validations
    if (empty($_ENV['JWT_SECRET']) || $_ENV['JWT_SECRET'] === 'your-secret-key-change-in-production-please') {
        $errors[] = 'JWT_SECRET is not set or using default value';
    }
    
    if (empty($_ENV['DB_HOST']) || empty($_ENV['DB_DATABASE']) || empty($_ENV['DB_USERNAME'])) {
        $errors[] = 'Database configuration is incomplete';
    }
    
    if (empty($_ENV['CORS_ALLOWED_ORIGINS'])) {
        $errors[] = 'CORS_ALLOWED_ORIGINS is not set';
    }
    
    // Log errors but don't break the application
    if (!empty($errors)) {
        foreach ($errors as $error) {
            error_log("CRITICAL PRODUCTION CONFIGURATION ERROR: $error");
        }
    }
}
