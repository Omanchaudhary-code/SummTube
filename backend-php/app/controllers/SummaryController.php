<?php
namespace App\Controllers;

use Core\Request;
use Core\Response;
use Core\Validator;
use App\Services\AIService;
use App\Services\GuestService;
use App\Models\Summary;
use App\Models\Usage;

class SummaryController
{
    private AIService $aiService;
    private GuestService $guestService;
    private Summary $summaryModel;
    private Usage $usageModel;

    public function __construct()
    {
        $this->aiService = new AIService();
        $this->guestService = new GuestService();
        $this->summaryModel = new Summary();
        $this->usageModel = new Usage();
    }

    /**
     * Create summary for guest users (limited to 3 per day)
     * POST /api/summary/guest
     */
    public function guestSummary(Request $request, Response $response): void
    {
        $data = $request->body();
        $videoUrl = $data['video_url'] ?? '';

        // Validate
        if (empty($videoUrl) || !$this->isValidYouTubeUrl($videoUrl)) {
            $response->json([
                'error' => 'Invalid YouTube URL'
            ], 400);
            return;
        }

        try {
            // Generate summary via AI service
            $result = $this->aiService->generateSummary($videoUrl);

            // Increment guest usage (identifier from middleware)
            $identifier = $request->guestIdentifier;
            $this->guestService->incrementUsage($identifier);

            // Get updated status
            $status = $this->guestService->checkLimit($identifier);

            $response->json([
                'success' => true,
                'summary' => $result['summary'],
                'video_title' => $result['title'],
                'video_duration' => $result['duration'],
                'guest_status' => [
                    'triesLeft' => $status['triesLeft'],
                    'triesUsed' => $status['triesUsed'],
                    'maxTries' => $status['maxTries'],
                    'resetsAt' => $status['resetsAt']
                ],
                'message' => $status['triesLeft'] === 0 
                    ? 'You have used all free summaries. Please login for unlimited access.'
                    : ($status['triesLeft'] === 1 
                        ? 'This is your last free summary! Login for unlimited access.'
                        : null)
            ], 200);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Failed to generate summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create summary for logged-in users (unlimited)
     * POST /api/summary
     */
    public function createSummary(Request $request, Response $response): void
    {
        $data = $request->body();
        $videoUrl = $data['video_url'] ?? '';
        $userId = $request->user['user_id'];

        // Validate
        if (empty($videoUrl) || !$this->isValidYouTubeUrl($videoUrl)) {
            $response->json([
                'error' => 'Invalid YouTube URL'
            ], 400);
            return;
        }

        try {
            // Generate summary via AI service
            $result = $this->aiService->generateSummary($videoUrl);

            // Save to database
            $summaryId = $this->summaryModel->create([
                'user_id' => $userId,
                'video_url' => $videoUrl,
                'video_title' => $result['title'],
                'video_duration' => $result['duration'],
                'summary_text' => $result['summary']
            ]);

            // Update usage stats
            $this->usageModel->incrementSummaries($userId);

            $response->json([
                'success' => true,
                'id' => $summaryId,
                'summary' => $result['summary'],
                'video_title' => $result['title'],
                'video_duration' => $result['duration'],
                'created_at' => date('Y-m-d H:i:s')
            ], 201);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Failed to generate summary',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary history for logged-in user
     * GET /api/summary/history
     */
    public function getHistory(Request $request, Response $response): void
    {
        $userId = $request->user['user_id'];
        $page = (int) ($request->query('page') ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        try {
            $summaries = $this->summaryModel->getByUserId($userId, $limit, $offset);
            $total = $this->summaryModel->countByUserId($userId);

            $response->json([
                'success' => true,
                'summaries' => $summaries,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ], 200);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Failed to fetch history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single summary by ID
     * GET /api/summary/:id
     */
    public function getSummary(Request $request, Response $response): void
    {
        $summaryId = (int) $request->param('id');
        $userId = $request->user['user_id'];

        try {
            $summary = $this->summaryModel->getByIdAndUserId($summaryId, $userId);

            if (!$summary) {
                $response->notFound('Summary not found');
                return;
            }

            $response->json([
                'success' => true,
                'summary' => $summary
            ], 200);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Failed to fetch summary'
            ], 500);
        }
    }

    /**
     * Delete summary
     * DELETE /api/summary/:id
     */
    public function deleteSummary(Request $request, Response $response): void
    {
        $summaryId = (int) $request->param('id');
        $userId = $request->user['user_id'];

        try {
            $deleted = $this->summaryModel->delete($summaryId, $userId);

            if (!$deleted) {
                $response->notFound('Summary not found');
                return;
            }

            $response->json([
                'success' => true,
                'message' => 'Summary deleted'
            ], 200);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Failed to delete summary'
            ], 500);
        }
    }

    /**
     * Get guest status (tries remaining)
     * GET /api/guest/status
     */
    public function getGuestStatus(Request $request, Response $response): void
    {
        $identifier = $this->guestService->generateIdentifier(
            $request->ip(),
            $request->userAgent()
        );

        $status = $this->guestService->checkLimit($identifier);

        $response->json([
            'success' => true,
            'status' => $status
        ], 200);
    }

    /**
     * Validate YouTube URL
     */
    private function isValidYouTubeUrl(string $url): bool
    {
        return preg_match(
            '/^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|embed\/|v\/)|youtu\.be\/)[\w-]+/',
            $url
        );
    }
}
