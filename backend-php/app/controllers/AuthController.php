<?php
// ==========================================
// SECURE AUTH CONTROLLER WITH COOKIES
// File: app/controllers/AuthController.php
// ==========================================

namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Validator;
use App\Services\AuthService;
use App\Services\GoogleOAuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Set secure HTTP-only cookies for tokens
     */
    private function setTokenCookies(Response $response, string $accessToken, string $refreshToken): void
{
    $secure = filter_var($_ENV['COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $domain = $_ENV['COOKIE_DOMAIN'] ?? '';
    $samesite = $_ENV['COOKIE_SAMESITE'] ?? 'Strict';
    $httponly = filter_var($_ENV['COOKIE_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN);
    
    // Access token cookie
    setcookie(
        $_ENV['ACCESS_TOKEN_COOKIE_NAME'] ?? 'access_token',
        $accessToken,
        [
            'expires' => time() + (int)($_ENV['ACCESS_TOKEN_COOKIE_EXPIRY'] ?? 900),
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]
    );

    // Refresh token cookie
    setcookie(
        $_ENV['REFRESH_TOKEN_COOKIE_NAME'] ?? 'refresh_token',
        $refreshToken,
        [
            'expires' => time() + (int)($_ENV['REFRESH_TOKEN_COOKIE_EXPIRY'] ?? 604800),
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
        ]
    );
}

    /**
     * Clear token cookies on logout
     */
    private function clearTokenCookies(Response $response): void
    {
        $domain = $_ENV['COOKIE_DOMAIN'] ?? '';
        
        setcookie(
            'access_token',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => $domain,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );

        setcookie(
            'refresh_token',
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => $domain,
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }

    /**
     * Register new user with email/password
     * POST /api/auth/register
     */
    public function register(Request $request, Response $response): void
    {
        $data = $request->body();

        if (!is_array($data)) {
            $response->json(['error' => 'Invalid request body'], 400);
            return;
        }

        $validator = new Validator($data, [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'name' => 'required|min:2'
        ]);

        if ($validator->fails()) {
            $response->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $result = $this->authService->register(
                $data['email'],
                $data['password'],
                $data['name']
            );

            // Set secure cookies
            $this->setTokenCookies(
                $response,
                $result['access_token'],
                $result['refresh_token']
            );

            // Return user info only (no tokens in body)
            $response->json([
                'success' => true,
                'message' => 'Registration successful',
                'user' => $result['user']
            ], 201);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Login with email/password
     * POST /api/auth/login
     */
    public function login(Request $request, Response $response): void
    {
        $data = $request->body();

        if (!is_array($data)) {
            $response->json(['error' => 'Invalid request body'], 400);
            return;
        }

        $validator = new Validator($data, [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            $response->json([
                'error' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
            return;
        }

        try {
            $result = $this->authService->login(
                $data['email'],
                $data['password']
            );

            // Set secure cookies
            $this->setTokenCookies(
                $response,
                $result['access_token'],
                $result['refresh_token']
            );

            // Return user info only
            $response->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $result['user']
            ], 200);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Invalid credentials',
                'message' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Refresh access token
     * POST /api/auth/refresh
     */
    public function refresh(Request $request, Response $response): void
    {
        // Get refresh token from cookie instead of body
        $refreshToken = $_COOKIE['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            $response->json([
                'error' => 'Refresh token is required'
            ], 400);
            return;
        }

        try {
            $result = $this->authService->refreshAccessToken($refreshToken);

            // Update access token cookie
            $this->setTokenCookies(
                $response,
                $result['access_token'],
                $refreshToken // Keep same refresh token
            );

            $response->json([
                'success' => true,
                'message' => 'Token refreshed',
                'user' => $result['user']
            ], 200);

        } catch (\Exception $e) {
            // Clear cookies on refresh failure
            $this->clearTokenCookies($response);
            
            $response->json([
                'error' => 'Token refresh failed',
                'message' => $e->getMessage()
            ], 401);
        }
    }

    /**
     * Google OAuth authentication
     * POST /api/auth/google
     */
    public function googleAuth(Request $request, Response $response): void
    {
        $data = $request->body();
        $idToken = $data['token'] ?? '';

        if (empty($idToken)) {
            $response->json(['error' => 'Google token is required'], 400);
            return;
        }

        try {
            $googleService = new GoogleOAuthService();
            $googleUserData = $googleService->verifyToken($idToken);

            if (!$googleUserData) {
                $response->json(['error' => 'Invalid Google token'], 401);
                return;
            }

            $result = $this->authService->handleGoogleAuth($googleUserData);

            // Set secure cookies
            $this->setTokenCookies(
                $response,
                $result['access_token'],
                $result['refresh_token']
            );

            $response->json([
                'success' => true,
                'message' => 'Google authentication successful',
                'user' => $result['user']
            ], 200);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Google authentication failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout - clear cookies and revoke refresh token
     * POST /api/auth/logout
     */
    public function logout(Request $request, Response $response): void
    {
        $refreshToken = $_COOKIE['refresh_token'] ?? '';

        // Clear cookies first
        $this->clearTokenCookies($response);

        // Try to revoke token from database
        if (!empty($refreshToken)) {
            try {
                $this->authService->logout($refreshToken);
            } catch (\Exception $e) {
                // Log error but still return success
                error_log('Logout token revocation failed: ' . $e->getMessage());
            }
        }

        $response->json([
            'success' => true,
            'message' => 'Logout successful'
        ], 200);
    }

    /**
     * Logout from all devices
     * POST /api/auth/logout-all
     */
    public function logoutAll(Request $request, Response $response): void
    {
        $userId = $request->user['user_id'] ?? null;

        if (!$userId) {
            $response->json(['error' => 'User not authenticated'], 401);
            return;
        }

        // Clear cookies
        $this->clearTokenCookies($response);

        try {
            $this->authService->logoutAll($userId);

            $response->json([
                'success' => true,
                'message' => 'Logged out from all devices successfully'
            ], 200);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Logout failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}