<?php
return [
    // Allowed origins - parsed from environment variable
    // Production: MUST be set via CORS_ALLOWED_ORIGINS env var (no localhost defaults)
    // Development: Defaults to localhost for local development
    'allowed_origins' => array_filter(
        array_map('trim', 
            !empty($_ENV['CORS_ALLOWED_ORIGINS']) 
                ? explode(',', $_ENV['CORS_ALLOWED_ORIGINS'])
                : (($_ENV['APP_ENV'] ?? 'development') === 'production' 
                    ? [] 
                    : ['http://localhost:5173', 'http://localhost:3000', 'http://localhost:8080'])
        )
    ),
    
    // Allowed HTTP methods
    'allowed_methods' => array_filter(
        array_map('trim', explode(',', $_ENV['CORS_ALLOWED_METHODS'] ?? 'GET,POST,PUT,DELETE,OPTIONS,PATCH'))
    ),
    
    // Allowed headers - EXPANDED for better compatibility
    'allowed_headers' => array_filter(
        array_map('trim', explode(',', $_ENV['CORS_ALLOWED_HEADERS'] ?? 'Content-Type,Authorization,X-Requested-With,Accept,Origin,Access-Control-Request-Method,Access-Control-Request-Headers'))
    ),
    
    // CRITICAL: Must be true for cookie-based authentication
    'allow_credentials' => filter_var(
        $_ENV['CORS_ALLOW_CREDENTIALS'] ?? 'true', 
        FILTER_VALIDATE_BOOLEAN
    ),
    
    // Cache preflight requests for 24 hours
    'max_age' => (int)($_ENV['CORS_MAX_AGE'] ?? 86400),
    
    // Exposed headers - Allow frontend to read these response headers
    'exposed_headers' => array_filter(
        array_map('trim', explode(',', $_ENV['CORS_EXPOSED_HEADERS'] ?? 'Content-Length,Content-Type,Authorization'))
    ),
    
    // Support for wildcard origins (set to false for production with credentials)
    'supports_wildcard' => filter_var(
        $_ENV['CORS_SUPPORTS_WILDCARD'] ?? 'false',
        FILTER_VALIDATE_BOOLEAN
    ),
];