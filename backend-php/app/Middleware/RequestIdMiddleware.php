<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;

/**
 * Request ID Middleware
 * Adds unique request ID to all requests for tracing
 */
class RequestIdMiddleware extends Middleware
{
    /**
     * Generate or use existing request ID
     */
    public function handle(Request $request, Response $response, callable $next)
    {
        // Check if request ID already exists in header
        $requestId = $request->header('X-Request-ID');
        
        if (!$requestId) {
            // Generate unique request ID
            $requestId = $this->generateRequestId();
        }
        
        // Set request ID on request and response
        $request->requestId = $requestId;
        $response->setRequestId($requestId);
        
        // Add request ID to response headers
        $response->header('X-Request-ID', $requestId);
        
        return $next();
    }
    
    /**
     * Generate unique request ID
     * Format: timestamp-randomhex
     * 
     * @return string
     */
    private function generateRequestId(): string
    {
        return bin2hex(random_bytes(8));
    }
}
