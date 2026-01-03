<?php

namespace App\Services;

use App\Models\GuestUsage;

class GuestService
{
    private GuestUsage $guestModel;
    private int $maxTries;
    private int $resetHours;

    public function __construct()
    {
        $this->guestModel = new GuestUsage();
        
        // Load config with proper error handling
        $config = require __DIR__ . '/../../config/app.php';
        $this->maxTries = $config['guest_limits']['daily_summaries'] ?? 3;
        $this->resetHours = $config['guest_limits']['reset_hours'] ?? 24;
    }

    /**
     * Generate unique identifier for guest (IP address)
     */
    public function generateIdentifier(?string $ip = null, ?string $userAgent = null): string
    {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        // Just use IP for simplicity (matches your table structure)
        return $ip;
    }

    /**
     * Get current guest identifier
     */
    public function getCurrentIdentifier(): string
    {
        return $this->generateIdentifier();
    }

    /**
     * Check if guest has tries remaining
     * 
     * @param string|null $identifier Guest identifier (IP address)
     * @return array Status information
     */
    public function checkLimit(?string $identifier = null): array
    {
        $identifier = $identifier ?? $this->getCurrentIdentifier();
        $usage = $this->guestModel->getOrCreate($identifier);
        
        // getOrCreate already handles reset logic
        $triesLeft = $this->maxTries - (int)$usage['summaries_count'];
        
        return [
            'hasTriesLeft' => $triesLeft > 0,
            'triesUsed' => (int)$usage['summaries_count'],
            'triesLeft' => max(0, $triesLeft),
            'maxTries' => $this->maxTries,
            'resetsAt' => $usage['reset_at']
        ];
    }

    /**
     * Check if guest has reached the limit
     * 
     * @param string|null $identifier Guest identifier
     * @return bool True if limit reached
     */
    public function hasReachedLimit(?string $identifier = null): bool
    {
        $identifier = $identifier ?? $this->getCurrentIdentifier();
        $status = $this->checkLimit($identifier);
        
        return !$status['hasTriesLeft'];
    }

    /**
     * Increment guest usage counter
     * 
     * @param string|null $identifier Guest identifier
     * @return bool Success status
     */
    public function incrementUsage(?string $identifier = null): bool
    {
        $identifier = $identifier ?? $this->getCurrentIdentifier();
        
        return $this->guestModel->incrementSummaries($identifier);
    }

    /**
     * Track usage (alias for incrementUsage)
     * 
     * @param string|null $identifier Guest identifier
     * @return void
     */
    public function trackUsage(?string $identifier = null): void
    {
        $this->incrementUsage($identifier);
    }

    /**
     * Record a summary creation
     */
    public function recordSummary(?string $identifier = null): bool
    {
        $identifier = $identifier ?? $this->getCurrentIdentifier();
        return $this->guestModel->incrementSummaries($identifier);
    }

    /**
     * Get remaining tries for guest
     * 
     * @param string|null $identifier Guest identifier
     * @return int Number of tries remaining
     */
    public function getRemainingTries(?string $identifier = null): int
    {
        $identifier = $identifier ?? $this->getCurrentIdentifier();
        $status = $this->checkLimit($identifier);
        
        return $status['triesLeft'];
    }

    /**
     * Get remaining count (alias)
     */
    public function getRemainingCount(?string $identifier = null): int
    {
        return $this->getRemainingTries($identifier);
    }

    /**
     * Get guest status information
     * 
     * @param string|null $identifier Guest identifier
     * @return array Status information
     */
    public function getStatus(?string $identifier = null): array
    {
        $identifier = $identifier ?? $this->getCurrentIdentifier();
        
        return $this->checkLimit($identifier);
    }

    /**
     * Clean up old guest records (optional - for maintenance)
     * 
     * @return int Number of records cleaned up
     */
    public function cleanup(): int
    {
        return $this->guestModel->cleanupExpired();
    }
}
