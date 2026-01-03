<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use App\Services\GuestService;

class GuestLimitMiddleware extends Middleware
{
    /**
     * Check if guest has tries remaining
     */
    public function handle(Request $request, Response $response, callable $next)
    {
        $guestService = new GuestService();

        // Generate guest identifier
        $identifier = $guestService->generateIdentifier(
            $request->ip(),
            $request->userAgent()
        );

        // Check limit
        $status = $guestService->checkLimit($identifier);

        if (!$status['hasTriesLeft']) {
            $response->json([
                'error' => 'Daily limit reached',
                'message' => 'You have used all 3 free summaries today. Please login to get unlimited access.',
                'status' => $status
            ], 429);
            
            return false;
        }

        // ✅ FIXED: Attach both status and identifier to request
        $request->guestStatus = $status;
        $request->guestIdentifier = $identifier;

        return $next();
    }
}
