<?php

use App\Controllers\AuthController;
use App\Controllers\SummaryController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestLimitMiddleware;
use App\Middleware\RateLimitMiddleware;

// ==========================================
// PUBLIC ROUTES (No authentication required)
// ==========================================

// Minimal HEAD routes for uptime/keepalive (no body)
$router->head('/', function ($request, $response) {
    // Optional: identify service in headers
    $response->header('X-Service', 'SummTube PHP Backend');
    $response->header('Content-Length', '0');
    $response->empty(200);
});

$router->get('/', function ($request, $response) {
    $response->json([
        'success' => true,
        'service' => 'SummTube PHP Backend'
    ], 200);
});

$router->head('/api/health', function ($request, $response) {
    $response->header('X-Service', 'SummTube PHP Backend');
    $response->header('Content-Length', '0');
    $response->empty(200);
});

// Health check
$router->get('/api/health', function ($request, $response) {
    try {
        $health = [
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'services' => []
        ];

        // Check database connection
        try {
            $db = \Core\Database::getInstance();
            $db->query('SELECT 1');
            $health['services']['database'] = 'connected';
        } catch (\Exception $e) {
            $health['services']['database'] = 'disconnected';
            $health['status'] = 'degraded';
        }

        // Check AI service connection (non-blocking)
        try {
            $aiService = new \App\Services\AIService();
            $aiHealth = $aiService->testConnection();
            $health['services']['ai_service'] = $aiHealth['connected'] ? 'connected' : 'unreachable';
            if (!$aiHealth['connected']) {
                $health['status'] = 'degraded';
            }
        } catch (\Exception $e) {
            $health['services']['ai_service'] = 'error';
            $health['status'] = 'degraded';
        }

        $statusCode = $health['status'] === 'ok' ? 200 : 503;
        $response->json($health, $statusCode);
    } catch (\Exception $e) {
        $response->json([
            'status' => 'error',
            'message' => 'Health check failed',
            'timestamp' => date('Y-m-d H:i:s')
        ], 500);
    }
});

// Authentication Routes (with rate limiting)
$router->post('/api/auth/register', [AuthController::class, 'register'], [RateLimitMiddleware::class]);
$router->post('/api/auth/login', [AuthController::class, 'login'], [RateLimitMiddleware::class]);
$router->post('/api/auth/google', [AuthController::class, 'googleAuth'], [RateLimitMiddleware::class]);
$router->get('/api/auth/google/config', [AuthController::class, 'getGoogleConfig']);
$router->post('/api/auth/refresh', [AuthController::class, 'refresh']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/auth/debug-cookies', [AuthController::class, 'debugCookies']);

// Guest Summary Routes (with rate limiting)
$router->post(
    '/api/summary/guest',
    [SummaryController::class, 'guestSummary'],
    [GuestLimitMiddleware::class]
);

$router->get('/api/guest/status', [SummaryController::class, 'getGuestStatus']);

// ==========================================
// PROTECTED ROUTES (JWT authentication required)
// ==========================================

// User Profile Routes
$router->get(
    '/api/user/profile',
    [UserController::class, 'getProfile'],
    [AuthMiddleware::class]
);

$router->put(
    '/api/user/profile',
    [UserController::class, 'updateProfile'],
    [AuthMiddleware::class]
);

// Summary Routes (Authenticated Users)
$router->post(
    '/api/summary',
    [SummaryController::class, 'createSummary'],
    [AuthMiddleware::class]
);

$router->get(
    '/api/summary/history',
    [SummaryController::class, 'getHistory'],
    [AuthMiddleware::class]
);

$router->get(
    '/api/summary/:id',
    [SummaryController::class, 'getSummary'],
    [AuthMiddleware::class]
);

$router->delete(
    '/api/summary/:id',
    [SummaryController::class, 'deleteSummary'],
    [AuthMiddleware::class]
);

// Logout from all devices
$router->post(
    '/api/auth/logout-all',
    [AuthController::class, 'logoutAll'],
    [AuthMiddleware::class]
);

// ==========================================
// 404 Handler (Must be last)
// ==========================================

$router->setNotFoundHandler(function ($request, $response) {
    $response->json([
        'error' => 'Route not found',
        'path' => $request->uri(),
        'method' => $request->method()
    ], 404);
});
