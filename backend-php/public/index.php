<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Run startup validation checks (optional - file may not exist in all environments)
$startupCheckFile = __DIR__ . '/../startup_check.php';
if (file_exists($startupCheckFile)) {
    require_once $startupCheckFile;
}

// Error handling - Production safe
$isDebug = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);
$isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';

if ($isProduction || !$isDebug) {
    // Production: Hide errors, log them
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../storage/logs/php-errors.log');
} else {
    // Development: Show errors
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Create core objects
$router = new Core\Router();
$request = new Core\Request();
$response = new Core\Response();

// Apply global middleware (order matters)
$router->use(App\Middleware\RequestIdMiddleware::class);  // Request ID first for tracing
$router->use(App\Middleware\CorsMiddleware::class);

// Load API routes
require_once __DIR__ . '/../routes/api.php';

// Dispatch request
try {
    $router->dispatch($request, $response);
} catch (Exception $e) {
    error_log('Application Error: ' . $e->getMessage());
    
    if (!$response->isSent()) {
        $response->serverError('An unexpected error occurred');
    }
}