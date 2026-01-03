<?php

return [
    // Application Settings
    'app_name' => $_ENV['APP_NAME'] ?? 'SummTube',
    'env' => $_ENV['APP_ENV'] ?? 'development',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    
    // JWT Configuration
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production-please',
        'algorithm' => $_ENV['JWT_ALGORITHM'] ?? 'HS256',
        // Access token expiry: 15 minutes (900 seconds)
        'access_expiry' => (int)($_ENV['JWT_ACCESS_EXPIRY'] ?? 900),
        // Refresh token expiry: 7 days (604800 seconds)
        'refresh_expiry' => (int)($_ENV['JWT_REFRESH_EXPIRY'] ?? 604800),
        // Legacy expiry (kept for backward compatibility)
        'expiry' => (int)($_ENV['JWT_EXPIRY'] ?? 604800),
    ],
    
    // Guest Usage Limits
    'guest' => [
        'summary_limit' => (int)($_ENV['GUEST_SUMMARY_LIMIT'] ?? 3),
        'reset_hours' => (int)($_ENV['GUEST_RESET_HOURS'] ?? 24),
        'max_text_length' => 5000,
    ],
    
    // Rate Limiting
    'rate_limit' => [
        'requests' => (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 100),
        'window' => (int)($_ENV['RATE_LIMIT_WINDOW'] ?? 3600),
    ],
    
    // AI Service
    'ai_service' => [
        'url' => $_ENV['AI_SERVICE_URL'] ?? 'http://localhost:8000',
        'timeout' => (int)($_ENV['AI_SERVICE_TIMEOUT'] ?? 30),
    ],
    
    // CORS Configuration
    'cors' => [
        'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:3000,http://localhost:5173'),
        'allowed_methods' => explode(',', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,DELETE,OPTIONS'),
        'allowed_headers' => explode(',', $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization,X-Requested-With'),
        'allow_credentials' => filter_var($_ENV['CORS_ALLOW_CREDENTIALS'] ?? 'true', FILTER_VALIDATE_BOOLEAN),
        'max_age' => (int)($_ENV['CORS_MAX_AGE'] ?? 86400),
    ],
];