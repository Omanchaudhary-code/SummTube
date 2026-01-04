<?php

use App\Controllers\AuthController;
use App\Controllers\SummaryController;
use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestLimitMiddleware;

// ==========================================
// PUBLIC ROUTES (No authentication required)
// ==========================================

// Health check
$router->get('/api/health', function($request, $response) {
    $response->json([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Authentication Routes
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/google', [AuthController::class, 'googleAuth']);
$router->get('/api/auth/google/config', [AuthController::class, 'getGoogleConfig']); // ✅ ADDED
$router->post('/api/auth/refresh', [AuthController::class, 'refresh']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);

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
// DEBUG ROUTES (Remove in production)
// ==========================================

if ($_ENV['APP_ENV'] === 'development' || ($_ENV['APP_DEBUG'] ?? false)) {
    
    // Debug: Check headers
    $router->get('/api/debug/headers', function($request, $response) {
        $response->json([
            'all_headers' => $request->headers(),
            'authorization' => $request->header('Authorization'),
            'bearer_token' => $request->bearerToken(),
            'token_length' => $request->bearerToken() ? strlen($request->bearerToken()) : 0
        ]);
    });
    
    // Debug: Test protected route
    $router->get('/api/debug/auth-test', function($request, $response) {
        $response->json([
            'message' => 'This is a public debug route',
            'has_token' => $request->bearerToken() !== null
        ]);
    });
    
    // Debug: Decode token (without verification)
    $router->post('/api/debug/decode-token', function($request, $response) {
        $data = $request->body();
        $token = $data['token'] ?? '';
        
        if (empty($token)) {
            $response->json(['error' => 'Token required'], 400);
            return;
        }
        
        try {
            $jwtService = new \App\Services\JWTService();
            $decoded = $jwtService->decode($token);
            $response->json([
                'decoded' => $decoded,
                'issued_at' => date('Y-m-d H:i:s', $decoded['iat'] ?? 0),
                'expires_at' => date('Y-m-d H:i:s', $decoded['exp'] ?? 0),
                'is_expired' => ($decoded['exp'] ?? 0) < time()
            ]);
        } catch (\Exception $e) {
            $response->json(['error' => $e->getMessage()], 400);
        }
    });
    
    // Debug: Reset guest limit
    $router->post('/api/debug/reset-guest', function($request, $response) {
        $guestService = new \App\Services\GuestService();
        $identifier = $guestService->generateIdentifier(
            $request->ip(),
            $request->userAgent()
        );
        
        // Reset guest usage
        $db = \Core\Database::getInstance();
        $sql = "UPDATE guest_usage SET summaries_count = 0, reset_at = NOW() + INTERVAL '24 hours' WHERE identifier = :identifier";
        $stmt = $db->prepare($sql);
        $stmt->execute([':identifier' => $identifier]);
        
        $response->json([
            'success' => true,
            'message' => 'Guest limit reset',
            'identifier' => $identifier
        ]);
    });
}

// ==========================================
// 404 Handler (Must be last)
// ==========================================

$router->setNotFoundHandler(function($request, $response) {
    $response->json([
        'error' => 'Route not found',
        'path' => $request->uri(),
        'method' => $request->method()
    ], 404);
});