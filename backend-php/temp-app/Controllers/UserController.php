<?php
namespace App\Controllers;

use Core\Request;
use Core\Response;
use App\Services\AuthService;
use App\Models\Usage;

class UserController
{
    private AuthService $authService;
    private Usage $usageModel;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->usageModel = new Usage();
    }

    /**
     * Get user profile
     * GET /api/user/profile
     */
    public function getProfile(Request $request, Response $response): void
    {
        $userId = $request->user['user_id'];

        try {
            $user = $this->authService->getUserProfile($userId);

            if (!$user) {
                $response->notFound('User not found');
                return;
            }

            // Get usage stats
            $usage = $this->usageModel->getByUserId($userId);

            $response->json([
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'auth_provider' => $user['auth_provider'],
                    'created_at' => $user['created_at']
                ],
                'stats' => [
                    'total_summaries' => $usage['total_summaries'] ?? 0,
                    'last_summary_at' => $usage['last_summary_at'] ?? null
                ]
            ], 200);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Failed to fetch profile'
            ], 500);
        }
    }
}
