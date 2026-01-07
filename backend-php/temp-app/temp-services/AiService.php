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
     * @param string $summaryType Type of summary: 'brief', 'detailed', or 'bullet_points'
     * @return array Complete video and summary information
     * @throws \Exception if summary generation fails
     */
    public function generateSummary(string $videoUrl, string $summaryType = 'detailed'): array
    {
        try {
            // Validate summary type
            $validTypes = ['brief', 'detailed', 'bullet_points'];
            if (!in_array($summaryType, $validTypes)) {
                $summaryType = 'detailed';
            }

            // Build full endpoint URL
            $url = $this->aiServiceUrl . '/summarize';
            
            error_log("🔍 AI Service Request URL: $url");
            error_log("🔍 Video URL: $videoUrl");
            error_log("🔍 Summary Type: $summaryType");
            
            // Initialize cURL
            $ch = curl_init($url);
            
            // Prepare JSON payload with summary_type
            $payload = json_encode([
                'video_url' => $videoUrl,
                'summary_type' => $summaryType
            ]);
            
            // Configure cURL options
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Content-Length: ' . strlen($payload)
                ],
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
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
                
                // Try to parse error response
                if ($response) {
                    $errorData = json_decode($response, true);
                    if ($errorData && isset($errorData['detail'])) {
                        $errorMsg .= ": " . $errorData['detail'];
                    } elseif ($errorData && isset($errorData['message'])) {
                        $errorMsg .= ": " . $errorData['message'];
                    } else {
                        $errorMsg .= ". Response: " . substr($response, 0, 200);
                    }
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

            // Map Python backend response to consistent format
            // Python backend returns:
            // {
            //   "summary": "...",
            //   "video_url": "...",
            //   "video_id": "...",
            //   "video_title": "..." (UPDATED field name),
            //   "thumbnail": "...",
            //   "duration": 123,
            //   "original_length": 5000 (transcript length),
            //   "summary_length": 500,
            //   "summary_type": "detailed"
            // }
            
            return [
                // Core summary data
                'summary' => $result['summary'],
                
                // Video metadata - handle both old and new field names
                'video_id' => $result['video_id'] ?? null,
                'title' => $result['video_title'] ?? $result['title'] ?? 'Unknown Title',
                'thumbnail' => $result['thumbnail'] ?? null,
                'duration' => $result['duration'] ?? 0,
                
                // Summary metadata
                'summary_type' => $result['summary_type'] ?? $summaryType,
                'transcript_length' => $result['original_length'] ?? $result['transcript_length'] ?? 0,
                'processing_time' => $result['processing_time'] ?? 0,
                
                // Optional: include raw response for debugging
                // 'raw_response' => $result
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
            
            error_log("🔍 Testing AI Service connection: $healthUrl");
            
            $ch = curl_init($healthUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            $isConnected = $httpCode === 200;
            
            error_log($isConnected ? "✅ AI Service is reachable" : "❌ AI Service connection failed");
            
            return [
                'connected' => $isConnected,
                'status_code' => $httpCode,
                'url' => $healthUrl,
                'response' => $response ? json_decode($response, true) : null,
                'error' => $curlError ?: null
            ];
            
        } catch (\Exception $e) {
            error_log("❌ AI Service connection test failed: " . $e->getMessage());
            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'url' => $this->aiServiceUrl . '/health'
            ];
        }
    }

    /**
     * Check if AI service is healthy and responding
     * 
     * @return bool True if service is healthy
     */
    public function isHealthy(): bool
    {
        $result = $this->testConnection();
        return $result['connected'] === true;
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

    /**
     * Get timeout setting
     * 
     * @return int Timeout in seconds
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get service information
     * 
     * @return array Service configuration details
     */
    public function getServiceInfo(): array
    {
        return [
            'url' => $this->aiServiceUrl,
            'timeout' => $this->timeout,
            'endpoints' => [
                'health' => $this->aiServiceUrl . '/health',
                'summarize' => $this->aiServiceUrl . '/summarize'
            ]
        ];
    }
}