<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;

class RateLimitMiddleware extends Middleware
{
    private string $cacheFile = 'storage/rate_limit_cache.json';

    /**
     * Simple rate limiting (IP-based)
     */
    public function handle(Request $request, Response $response, callable $next)
    {
        $config = require __DIR__ . '/../../config/app.php';
        $maxRequests = $config['rate_limit']['requests'];
        $window = $config['rate_limit']['window'];

        $ip = $request->ip();
        $cacheFile = __DIR__ . '/../../' . $this->cacheFile;

        // Load cache
        $cache = $this->loadCache($cacheFile);

        // Clean old entries
        $cache = $this->cleanCache($cache, $window);

        // Check rate limit
        if (isset($cache[$ip])) {
            $count = count($cache[$ip]);
            
            if ($count >= $maxRequests) {
                $response->json([
                    'error' => 'Rate limit exceeded',
                    'message' => "Too many requests. Maximum $maxRequests requests per hour.",
                    'retry_after' => $window
                ], 429);
                
                return false;
            }
        }

        // Add current request
        if (!isset($cache[$ip])) {
            $cache[$ip] = [];
        }
        $cache[$ip][] = time();

        // Save cache
        $this->saveCache($cacheFile, $cache);

        return $next();
    }

    private function loadCache(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        return json_decode($content, true) ?? [];
    }

    private function saveCache(string $file, array $cache): void
    {
        file_put_contents($file, json_encode($cache));
    }

    private function cleanCache(array $cache, int $window): array
    {
        $now = time();

        foreach ($cache as $ip => $timestamps) {
            $cache[$ip] = array_filter($timestamps, function($timestamp) use ($now, $window) {
                return ($now - $timestamp) < $window;
            });

            if (empty($cache[$ip])) {
                unset($cache[$ip]);
            }
        }

        return $cache;
    }
}