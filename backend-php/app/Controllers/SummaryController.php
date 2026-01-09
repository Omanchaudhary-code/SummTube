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
        $summaryType = $data['summary_type'] ?? 'detailed';

        // Validate
        if (empty($videoUrl) || !$this->isValidYouTubeUrl($videoUrl)) {
            $response->json([
                'error' => 'Invalid YouTube URL'
            ], 400);
            return;
        }

        try {
            // Check guest limit BEFORE generating (don't waste API calls)
            $identifier = $request->guestIdentifier ?? $this->guestService->generateIdentifier(
                $request->ip(),
                $request->userAgent()
            );

            $status = $this->guestService->checkLimit($identifier);

            if ($status['triesLeft'] <= 0) {
                $response->json([
                    'error' => 'Guest limit reached',
                    'message' => 'You have used all free summaries. Please login for unlimited access.',
                    'guest_status' => $status
                ], 429);
                return;
            }

            // Generate summary via AI service
            $result = $this->aiService->generateSummary($videoUrl, $summaryType);

            // Increment guest usage
            $this->guestService->incrementUsage($identifier);

            // Get updated status
            $status = $this->guestService->checkLimit($identifier);

            $response->json([
                'success' => true,
                'video_id' => $result['video_id'] ?? null,
                'video_title' => $result['title'] ?? 'Unknown',
                'video_url' => $videoUrl,
                'thumbnail' => $result['thumbnail'] ?? null,
                'duration' => $result['duration'] ?? 0,
                'summary' => $result['summary'] ?? '',
                'summary_type' => $summaryType,
                'transcript_length' => $result['transcript_length'] ?? 0,
                'processing_time' => $result['processing_time'] ?? 0,
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
                'error' => $e->getMessage(),
                'message' => 'Try checking if the AI service URL is correct in your environment settings.'
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
        $summaryType = $data['summary_type'] ?? 'detailed';
        $userId = $request->user['user_id'];

        // Validate
        if (empty($videoUrl) || !$this->isValidYouTubeUrl($videoUrl)) {
            $response->json([
                'error' => 'Invalid YouTube URL'
            ], 400);
            return;
        }

        try {
            error_log("🚀 Starting summary generation for User ID: $userId");

            // Generate summary via AI service
            $result = $this->aiService->generateSummary($videoUrl, $summaryType);
            error_log("✅ AI Service summary generated");

            // Save to database
            try {
                $summaryId = $this->summaryModel->create([
                    'user_id' => $userId,
                    'video_url' => $videoUrl,
                    'video_id' => $result['video_id'] ?? null,
                    'video_title' => $result['title'] ?? 'Unknown',
                    'thumbnail' => $result['thumbnail'] ?? null,
                    'duration' => $result['duration'] ?? 0,
                    'summary_text' => $result['summary'] ?? '',
                    'summary_type' => $summaryType,
                    'original_text' => '', // Can store transcript if needed
                    'transcript_length' => $result['transcript_length'] ?? 0,
                    'processing_time' => $result['processing_time'] ?? 0
                ]);
                error_log("✅ Database record created: ID $summaryId");
            } catch (\Exception $dbError) {
                error_log("❌ Database save failed: " . $dbError->getMessage());
                throw new \Exception("Failed to save summary to history: " . $dbError->getMessage());
            }

            // Update usage stats
            try {
                $this->usageModel->incrementSummaries($userId);
                error_log("✅ Usage incremented");
            } catch (\Exception $usageError) {
                error_log("⚠️ Usage increment failed (non-critical): " . $usageError->getMessage());
                // Don't fail the whole request for usage tracking
            }

            $response->json([
                'success' => true,
                'id' => $summaryId,
                'video_id' => $result['video_id'] ?? null,
                'video_title' => $result['title'] ?? 'Unknown',
                'video_url' => $videoUrl,
                'thumbnail' => $result['thumbnail'] ?? null,
                'duration' => $result['duration'] ?? 0,
                'summary' => $result['summary'] ?? '',
                'summary_type' => $summaryType,
                'transcript_length' => $result['transcript_length'] ?? 0,
                'processing_time' => $result['processing_time'] ?? 0,
                'created_at' => date('Y-m-d H:i:s')
            ], 201);

        } catch (\Exception $e) {
            error_log("🔥 Summary Creation Error: " . $e->getMessage());
            $response->json([
                'error' => 'Failed to generate summary',
                'message' => $e->getMessage(),
                'trace' => ($_ENV['APP_DEBUG'] ?? false) ? $e->getTraceAsString() : null
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
        $limit = (int) ($request->query('limit') ?? 20);
        $offset = ($page - 1) * $limit;

        try {
            $summaries = $this->summaryModel->getByUserId($userId, $limit, $offset);
            $total = $this->summaryModel->countByUserId($userId);

            $response->json([
                'success' => true,
                'data' => $summaries,
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
                $response->json([
                    'error' => 'Summary not found'
                ], 404);
                return;
            }

            $response->json([
                'success' => true,
                'data' => $summary
            ], 200);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Failed to fetch summary',
                'message' => $e->getMessage()
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
                $response->json([
                    'error' => 'Summary not found'
                ], 404);
                return;
            }

            $response->json([
                'success' => true,
                'message' => 'Summary deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            $response->json([
                'error' => 'Failed to delete summary',
                'message' => $e->getMessage()
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