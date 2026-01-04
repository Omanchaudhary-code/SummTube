<?php
namespace App\Services;

use Google_Client;

class GoogleOAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $this->clientId = $config['google']['client_id'] ?? '';
        $this->clientSecret = $config['google']['client_secret'] ?? '';
        $this->redirectUri = $config['google']['redirect_uri'] ?? '';
        
        // Validate configuration
        if (empty($this->clientId) || empty($this->clientSecret)) {
            error_log('Warning: Google OAuth credentials not configured');
        }
    }

    /**
     * Verify Google ID token and get user data
     * Used for frontend Google Sign-In button flow
     * 
     * @param string $idToken The ID token from Google
     * @return array|null User data or null if verification fails
     */
    public function verifyToken(string $idToken): ?array
    {
        try {
            $client = new Google_Client(['client_id' => $this->clientId]);
            $payload = $client->verifyIdToken($idToken);

            if (!$payload) {
                error_log('Google OAuth: Token verification failed');
                return null;
            }

            error_log('Google OAuth: Token verified for ' . ($payload['email'] ?? 'unknown'));

            return [
                'google_id' => $payload['sub'],
                'email' => $payload['email'],
                'name' => $payload['name'],
                'picture' => $payload['picture'] ?? null,
                'email_verified' => $payload['email_verified'] ?? false
            ];

        } catch (\Exception $e) {
            error_log('Google OAuth Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Google OAuth URL for redirect flow (alternative method)
     * 
     * @return string The OAuth authorization URL
     */
    public function getAuthUrl(): string
    {
        $client = new Google_Client();
        $client->setClientId($this->clientId);
        $client->setClientSecret($this->clientSecret);
        $client->setRedirectUri($this->redirectUri);
        $client->addScope('email');
        $client->addScope('profile');
        $client->setAccessType('offline');
        
        return $client->createAuthUrl();
    }

    /**
     * Exchange authorization code for user data (alternative method)
     * 
     * @param string $code Authorization code from Google
     * @return array|null User data or null if exchange fails
     */
    public function getUserFromCode(string $code): ?array
    {
        try {
            $client = new Google_Client();
            $client->setClientId($this->clientId);
            $client->setClientSecret($this->clientSecret);
            $client->setRedirectUri($this->redirectUri);

            // Exchange code for access token
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                error_log('Google OAuth Error: ' . $token['error']);
                return null;
            }

            $client->setAccessToken($token);

            // Get user info
            $oauth2 = new \Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();

            return [
                'google_id' => $userInfo->id,
                'email' => $userInfo->email,
                'name' => $userInfo->name,
                'picture' => $userInfo->picture,
                'email_verified' => $userInfo->verifiedEmail
            ];

        } catch (\Exception $e) {
            error_log('Google OAuth Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get client configuration for frontend
     * Only returns client_id (NOT the secret)
     * 
     * @return array Configuration data
     */
    public function getClientConfig(): array
    {
        return [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri
        ];
    }

    /**
     * Check if Google OAuth is properly configured
     * 
     * @return bool True if configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }
}