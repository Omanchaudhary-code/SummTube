<?php
namespace App\Services;

class GoogleOAuthService
{
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $this->clientId = $config['google']['client_id'];
        $this->clientSecret = $config['google']['client_secret'];
    }

    /**
     * Verify Google ID token and get user data
     */
    public function verifyToken(string $idToken): ?array
    {
        try {
            $client = new \Google_Client(['client_id' => $this->clientId]);
            $payload = $client->verifyIdToken($idToken);

            if (!$payload) {
                return null;
            }

            return [
                'id' => $payload['sub'],
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
}
