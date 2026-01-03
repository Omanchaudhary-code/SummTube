
<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', $_ENV['APP_DEBUG'] ?? 0);

// Create core objects
$router = new Core\Router();
$request = new Core\Request();
$response = new Core\Response();

// Apply global CORS middleware
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