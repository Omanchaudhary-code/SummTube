<?php
namespace App\Services;

/**
 * AI Service
 * Handles communication with Python FastAPI backend for video summarization
 * 
 * File: app/services/AIService.php
 */
class AIService
{
    private string $aiServiceUrl;
    private int $timeout;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/app.php';
        // Remove trailing slashes for consistent URL building
        $this->aiServiceUrl = rtrim($config['ai_service']['url'], '/');
        $this->timeout = $config['ai_service']['timeout'];
    }

    /**
     * Generate summary from YouTube video URL
     * 
     * @param string $videoUrl YouTube video URL
     * @return array ['summary' => string, 'title' => string, 'duration' => mixed]
     * @throws \Exception if summary generation fails
     */
    public function generateSummary(string $videoUrl): array
    {
        try {
            // Build full endpoint URL
            $url = $this->aiServiceUrl . '/summarize';
            
            error_log("🔍 AI Service Request URL: $url");
            error_log("🔍 Video URL: $videoUrl");
            
            // Initialize cURL
            $ch = curl_init($url);
            
            // Prepare JSON payload
            $payload = json_encode(['video_url' => $videoUrl]);
            
            // Configure cURL options
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload)
                ],
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 10
            ]);

            // Execute request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);
            
            curl_close($ch);

            // Log response details for debugging
            error_log("📡 AI Service Response Code: $httpCode");
            if ($curlError) {
                error_log("❌ cURL Error ($curlErrno): $curlError");
            }
            error_log("📦 AI Response (first 500 chars): " . substr($response, 0, 500));

            // Check for network/connection errors
            if ($curlErrno !== 0) {
                throw new \Exception("Network error connecting to AI service: $curlError");
            }

            // Check HTTP status code
            if ($httpCode !== 200) {
                $errorMsg = "AI Service returned HTTP $httpCode";
                if ($response) {
                    $errorMsg .= ". Response: " . substr($response, 0, 200);
                }
                throw new \Exception($errorMsg);
            }

            // Decode JSON response
            $result = json_decode($response, true);

            // Check for JSON decode errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to decode JSON response: ' . json_last_error_msg());
            }

            // Validate response is not empty
            if (!$result) {
                throw new \Exception('Empty response from AI service');
            }

            // Check for required 'summary' field
            if (!isset($result['summary'])) {
                error_log("❌ Response structure: " . print_r($result, true));
                throw new \Exception('Response missing summary field');
            }

            error_log("✅ Summary generated successfully");

            // ✅ FIXED: Map Python response fields correctly
            // Python service returns: title, duration (not video_title, video_duration)
            return [
                'summary' => $result['summary'],
                'title' => $result['title'] ?? 'Unknown Title',
                'duration' => $result['duration'] ?? null
            ];

        } catch (\Exception $e) {
            error_log('❌ AI Service Error: ' . $e->getMessage());
            throw new \Exception('Failed to generate summary: ' . $e->getMessage());
        }
    }

    /**
     * Test AI service connection
     * 
     * @return array Connection status and details
     */
    public function testConnection(): array
    {
        try {
            // Try to connect to health endpoint
            $healthUrl = $this->aiServiceUrl . '/health';
            
            $ch = curl_init($healthUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return [
                'connected' => $httpCode === 200,
                'status_code' => $httpCode,
                'url' => $healthUrl,
                'response' => $response
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get AI service base URL (for debugging)
     * 
     * @return string
     */
    public function getServiceUrl(): string
    {
        return $this->aiServiceUrl;
    }
}