<?php

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;

class CorsMiddleware extends Middleware
{
    /**
     * Handle CORS preflight and add CORS headers
     */
    public function handle(Request $request, Response $response, callable $next)
    {
        $config = require __DIR__ . '/../../config/cors.php';
        
        // Get origin from request
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Check if origin is allowed
        $allowedOrigins = $config['allowed_origins'];
        $isOriginAllowed = in_array($origin, $allowedOrigins) || in_array('*', $allowedOrigins);
        
        // Set Access-Control-Allow-Origin
        if ($isOriginAllowed && !empty($origin)) {
            // Must use specific origin when credentials are true
            $response->header('Access-Control-Allow-Origin', $origin);
        } elseif (in_array('*', $allowedOrigins) && !$config['allow_credentials']) {
            // Only use wildcard if credentials are false
            $response->header('Access-Control-Allow-Origin', '*');
        }
        
        // Set other CORS headers
        $response->header('Access-Control-Allow-Methods', implode(', ', $config['allowed_methods']));
        $response->header('Access-Control-Allow-Headers', implode(', ', $config['allowed_headers']));
        $response->header('Access-Control-Max-Age', (string) $config['max_age']);
        
        // Critical for cookie-based authentication
        if ($config['allow_credentials']) {
            $response->header('Access-Control-Allow-Credentials', 'true');
        }
        
        // Set exposed headers (if configured)
        if (!empty($config['exposed_headers'])) {
            $response->header('Access-Control-Expose-Headers', implode(', ', $config['exposed_headers']));
        }
        
        // Fixes Google OAuth COOP warning
        $response->header('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        
        // Additional security headers
        $response->header('X-Content-Type-Options', 'nosniff');
        
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            // Return empty 204 response for OPTIONS
            $response->json([], 204);
            return false;
        }
        
        return $next();
    }
}