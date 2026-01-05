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
        
        // Get origin
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Check if origin is allowed
        if (in_array($origin, $config['allowed_origins']) || in_array('*', $config['allowed_origins'])) {
            $response->header('Access-Control-Allow-Origin', $origin);
        }
        
        $response->header('Access-Control-Allow-Methods', implode(', ', $config['allowed_methods']));
        $response->header('Access-Control-Allow-Headers', implode(', ', $config['allowed_headers']));
        $response->header('Access-Control-Max-Age', (string) $config['max_age']);
        
        if ($config['allow_credentials']) {
            $response->header('Access-Control-Allow-Credentials', 'true');
        }
        
        // ✅ ADD THIS - Fixes Google OAuth COOP warning
        $response->header('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response->json(['status' => 'OK'], 200);
            return false;
        }
        
        return $next();
    }
}