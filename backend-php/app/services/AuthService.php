<?php
namespace App\Services;

use App\Models\User;
use App\Models\Usage;

class AuthService
{
    private User $userModel;
    private Usage $usageModel;
    private JWTService $jwtService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->usageModel = new Usage();
        $this->jwtService = new JWTService();
    }

    /**
     * Register new user with email/password
     */
    public function register(string $email, string $password, string $name): array
    {
        // Check if user already exists
        if ($this->userModel->findByEmail($email)) {
            throw new \Exception('Email already registered');
        }

        // Create user
        $userId = $this->userModel->create([
            'email' => $email,
            'password' => $this->userModel->hashPassword($password),
            'name' => $name,
            'auth_provider' => 'email'
        ]);

        // Initialize usage tracking
        $this->usageModel->create($userId);

        // Get user data
        $user = [
            'id' => $userId,
            'email' => $email,
            'name' => $name,
            'auth_provider' => 'email'
        ];

        // Generate token pair (access + refresh)
        $tokens = $this->jwtService->generateTokenPair($user);

        return [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'user' => $user
        ];
    }

    /**
     * Login with email/password
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            throw new \Exception('Invalid credentials');
        }

        // For Google OAuth users, password login is not allowed
        if ($user['auth_provider'] === 'google') {
            throw new \Exception('Please login with Google');
        }

        // Verify password
        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            throw new \Exception('Invalid credentials');
        }

        // Prepare user data
        $userData = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'auth_provider' => $user['auth_provider']
        ];

        // Generate token pair (access + refresh)
        $tokens = $this->jwtService->generateTokenPair($userData);

        return [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'user' => $userData
        ];
    }

    /**
     * Handle Google OAuth authentication
     */
    public function handleGoogleAuth(array $googleUserData): array
    {
        $googleId = $googleUserData['id'];
        $email = $googleUserData['email'];
        $name = $googleUserData['name'];

        // Check if user exists with Google ID
        $user = $this->userModel->findByGoogleId($googleId);

        if (!$user) {
            // Check if email exists (link accounts)
            $user = $this->userModel->findByEmail($email);

            if ($user) {
                // Update existing user with Google ID
                $this->userModel->update($user['id'], [
                    'google_id' => $googleId,
                    'auth_provider' => 'google'
                ]);
            } else {
                // Create new user
                $userId = $this->userModel->create([
                    'email' => $email,
                    'name' => $name,
                    'google_id' => $googleId,
                    'auth_provider' => 'google',
                    'password' => null
                ]);

                // Initialize usage
                $this->usageModel->create($userId);

                $user = $this->userModel->findById($userId);
            }
        }

        // Prepare user data
        $userData = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'auth_provider' => $user['auth_provider']
        ];

        // Generate token pair (access + refresh)
        $tokens = $this->jwtService->generateTokenPair($userData);

        return [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'user' => $userData
        ];
    }

    /**
     * Refresh access token using refresh token
     */
    public function refreshAccessToken(string $refreshToken): array
    {
        // Verify refresh token
        $tokenData = $this->jwtService->verifyRefreshToken($refreshToken);

        if (!$tokenData) {
            throw new \Exception('Invalid or expired refresh token');
        }

        // Get fresh user data from database
        $user = $this->userModel->findById($tokenData['user_id']);

        if (!$user) {
            throw new \Exception('User not found');
        }

        // Prepare user data
        $userData = [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'auth_provider' => $user['auth_provider']
        ];

        // Generate new access token
        $accessToken = $this->jwtService->generateAccessToken($userData);

        return [
            'access_token' => $accessToken,
            'user' => $userData
        ];
    }

    /**
     * Logout - revoke refresh token
     */
    public function logout(string $refreshToken): bool
    {
        if (empty($refreshToken)) {
            return true; // No token to revoke
        }

        return $this->jwtService->revokeRefreshToken($refreshToken);
    }

    /**
     * Logout from all devices - revoke all user's refresh tokens
     */
    public function logoutAll(int $userId): bool
    {
        return $this->jwtService->revokeAllUserTokens($userId);
    }

    /**
     * Get user profile by ID
     */
    public function getUserProfile(int $userId): ?array
    {
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            return null;
        }

        // Remove sensitive data
        unset($user['password']);

        return $user;
    }
}