<?php
namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Core\Database;
use PDO;

class JWTService
{
    private string $secret;
    private string $algorithm;
    private int $accessTokenExpiry;
    private int $refreshTokenExpiry;
    private PDO $db; // Changed from Database to PDO

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        $this->secret = $config['jwt']['secret'];
        $this->algorithm = $config['jwt']['algorithm'];
        
        // Access token: 15 minutes (short-lived)
        $this->accessTokenExpiry = $config['jwt']['access_expiry'] ?? (60 * 15);
        
        // Refresh token: 7 days (long-lived)
        $this->refreshTokenExpiry = $config['jwt']['refresh_expiry'] ?? (60 * 60 * 24 * 7);
        
        // Get PDO instance from Database singleton
        $this->db = Database::getInstance();
    }

    /**
     * Generate access token (short-lived)
     */
    public function generateAccessToken(array $user): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->accessTokenExpiry;

        $token = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'type' => 'access',
            'data' => [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name']
            ]
        ];

        return JWT::encode($token, $this->secret, $this->algorithm);
    }

    /**
     * Generate refresh token (long-lived) and store in database
     */
    public function generateRefreshToken(int $userId): string
    {
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->refreshTokenExpiry);

        // Store in database
        $stmt = $this->db->prepare(
            "INSERT INTO refresh_tokens (user_id, token, expires_at, created_at) 
             VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$userId, $token, $expiresAt]);

        return $token;
    }

    /**
     * Generate both access and refresh tokens
     */
    public function generateTokenPair(array $user): array
    {
        return [
            'access_token' => $this->generateAccessToken($user),
            'refresh_token' => $this->generateRefreshToken($user['id'])
        ];
    }

    /**
     * Verify access token
     */
    public function verifyAccessToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            $payload = (array) $decoded;

            // Check if it's an access token
            if (!isset($payload['type']) || $payload['type'] !== 'access') {
                throw new \Exception('Invalid token type');
            }

            // Return the data portion
            return (array) $payload['data'];
        } catch (\Exception $e) {
            throw new \Exception('Invalid or expired access token: ' . $e->getMessage());
        }
    }

    /**
     * Verify refresh token from database
     */
    public function verifyRefreshToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT rt.*, u.id, u.email, u.name, u.auth_provider
             FROM refresh_tokens rt
             JOIN users u ON rt.user_id = u.id
             WHERE rt.token = ? AND rt.expires_at > NOW()"
        );
        $stmt->execute([$token]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Revoke a specific refresh token
     */
    public function revokeRefreshToken(string $token): bool
    {
        $stmt = $this->db->prepare("DELETE FROM refresh_tokens WHERE token = ?");
        return $stmt->execute([$token]);
    }

    /**
     * Revoke all refresh tokens for a user (logout from all devices)
     */
    public function revokeAllUserTokens(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM refresh_tokens WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    /**
     * Clean up expired tokens (call this periodically, e.g., via cron)
     */
    public function cleanupExpiredTokens(): int
    {
        $stmt = $this->db->prepare("DELETE FROM refresh_tokens WHERE expires_at < NOW()");
        $stmt->execute();
        return $stmt->rowCount();
    }

    // ==========================================
    // BACKWARD COMPATIBILITY METHODS
    // Keep these so existing code doesn't break
    // ==========================================

    /**
     * Generate JWT token (OLD METHOD - for backward compatibility)
     * @deprecated Use generateAccessToken() or generateTokenPair() instead
     */
    public function generate(array $payload): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $this->accessTokenExpiry;

        $token = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => $payload
        ];

        return JWT::encode($token, $this->secret, $this->algorithm);
    }

    /**
     * Verify and decode JWT token (OLD METHOD - for backward compatibility)
     * @deprecated Use verifyAccessToken() instead
     */
    public function verify(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            
            // Handle both old format and new format
            if (isset($decoded->data)) {
                return (array) $decoded->data;
            }
            
            // New format
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }

    /**
     * Decode token without verification (for debugging)
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \Exception('Invalid token format');
        }

        $payload = json_decode(base64_decode($parts[1]), true);
        return $payload;
    }
}