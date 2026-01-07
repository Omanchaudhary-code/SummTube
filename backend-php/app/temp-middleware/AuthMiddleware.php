<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use App\Services\JWTService;
use App\Models\User;

class AuthMiddleware extends Middleware
{
    /**
     * Verify JWT access token from cookie and authenticate user
     */
    public function handle(Request $request, Response $response, callable $next)
    {
        // Try to get token from cookie first (new method)
        $token = $_COOKIE['access_token'] ?? null;
        
        // Fallback to Authorization header for backward compatibility
        if (!$token) {
            $token = $request->bearerToken();
        }

        if (!$token) {
            $response->json([
                'error' => 'Token not provided',
                'message' => 'Authentication required. Please login.'
            ], 401);
            return false;
        }

        try {
            $jwtService = new JWTService();
            
            // Verify access token
            try {
                $payload = $jwtService->verifyAccessToken($token);
            } catch (\Exception $e) {
                // Token might be expired, return specific error
                if (strpos($e->getMessage(), 'expired') !== false) {
                    $response->json([
                        'error' => 'Token expired',
                        'message' => 'Your session has expired. Please refresh your token.',
                        'code' => 'TOKEN_EXPIRED'
                    ], 401);
                    return false;
                }
                
                // Fallback to old verify method for backward compatibility
                $payload = $jwtService->verify($token);
            }

            // Extract user_id (handles both old and new token formats)
            $userId = $payload['user_id'] ?? $payload['data']['user_id'] ?? null;

            if (!$userId) {
                $response->json([
                    'error' => 'Invalid token format',
                    'message' => 'Token does not contain user information'
                ], 401);
                return false;
            }

            // Verify user exists and is active
            $userModel = new User();
            $user = $userModel->findById($userId);

            if (!$user) {
                $response->json([
                    'error' => 'User not found',
                    'message' => 'The user associated with this token does not exist'
                ], 401);
                return false;
            }

            // Attach user to request (like req.user in Express)
            $request->user = [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'auth_provider' => $user['auth_provider'] ?? 'email'
            ];

            return $next();

        } catch (\Exception $e) {
            // Log error for debugging
            error_log('AuthMiddleware - Token verification failed: ' . $e->getMessage());
            
            $response->json([
                'error' => 'Invalid token',
                'message' => 'Authentication failed. Please login again.'
            ], 401);
            return false;
        }
    }
}