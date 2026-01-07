<?php
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
        $samesite = $_ENV['COOKIE_SAMESITE'] ?? 'Lax';
        $httponly = filter_var($_ENV['COOKIE_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN);
        
        // Access token cookie (15 minutes)
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

        // Refresh token cookie (7 days)
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
                'samesite' => 'Lax'
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
                'samesite' => 'Lax'
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
        // Get refresh token from cookie
        $refreshToken = $_COOKIE['refresh_token'] ?? '';

        if (empty($refreshToken)) {
            $response->json([
                'error' => 'Refresh token is required'
            ], 400);
            return;
        }

        try {
            $result = $this->authService->refreshAccessToken($refreshToken);

            // Update access token cookie (keep same refresh token)
            $this->setTokenCookies(
                $response,
                $result['access_token'],
                $refreshToken
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
        
        // Support both 'token', 'idToken', and 'credential' field names
        $idToken = $data['token'] ?? $data['idToken'] ?? $data['credential'] ?? '';

        if (empty($idToken)) {
            $response->json([
                'error' => 'Google ID token is required',
                'hint' => 'Send token in body as: {"token": "your-google-id-token"}'
            ], 400);
            return;
        }

        try {
            $googleService = new GoogleOAuthService();
            
            // Check if Google OAuth is configured
            if (!$googleService->isConfigured()) {
                $response->json([
                    'error' => 'Google OAuth is not configured',
                    'message' => 'Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env'
                ], 500);
                return;
            }
            
            // Verify token with Google
            $googleUserData = $googleService->verifyToken($idToken);

            if (!$googleUserData) {
                $response->json([
                    'error' => 'Invalid Google token',
                    'message' => 'Token verification failed. Check if token is valid and not expired.'
                ], 401);
                return;
            }

            // Handle Google authentication (create or login user)
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
            error_log('Google Auth Error: ' . $e->getMessage());
            $response->json([
                'error' => 'Google authentication failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Google OAuth client configuration
     * GET /api/auth/google/config
     */
    public function getGoogleConfig(Request $request, Response $response): void
    {
        try {
            $googleService = new GoogleOAuthService();
            
            // Check if Google OAuth is configured
            if (!$googleService->isConfigured()) {
                $response->json([
                    'error' => 'Google OAuth not configured',
                    'message' => 'GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET must be set in environment variables'
                ], 500);
                return;
            }
            
            // Get client configuration (only returns client_id, not secret)
            $config = $googleService->getClientConfig();
            
            $response->json([
                'success' => true,
                'client_id' => $config['client_id'],
                'redirect_uri' => $config['redirect_uri']
            ], 200);
            
        } catch (\Exception $e) {
            error_log('Google config error: ' . $e->getMessage());
            $response->json([
                'error' => 'Failed to get Google configuration',
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