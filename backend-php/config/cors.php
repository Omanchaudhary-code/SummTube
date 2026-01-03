<?php

return [
    // Allowed origins - parsed from environment variable
    'allowed_origins' => array_filter(
        array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? 'http://localhost:5173,http://localhost:3000'))
    ),
    
    // Allowed HTTP methods
    'allowed_methods' => array_filter(
        array_map('trim', explode(',', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,DELETE,OPTIONS,PATCH'))
    ),
    
    // Allowed headers
    'allowed_headers' => array_filter(
        array_map('trim', explode(',', $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization,X-Requested-With,Accept,Origin'))
    ),
    
    // CRITICAL: Must be true for cookie-based authentication
    'allow_credentials' => filter_var(
        $_ENV['CORS_ALLOW_CREDENTIALS'] ?? 'true', 
        FILTER_VALIDATE_BOOLEAN
    ),
    
    // Cache preflight requests for 24 hours
    'max_age' => (int)($_ENV['CORS_MAX_AGE'] ?? 86400),
    
    // Exposed headers (optional - for custom headers in response)
    'exposed_headers' => array_filter(
        array_map('trim', explode(',', $_ENV['CORS_EXPOSED_HEADERS'] ?? ''))
    ),
];