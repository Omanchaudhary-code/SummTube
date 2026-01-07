<?php
// ==========================================
// FILE 1: core/Request.php (COMPLETE FIXED)
// ==========================================

namespace Core;

/**
 * HTTP Request Wrapper
 * Like req object in Express.js
 */
class Request
{
    private array $bodyData = [];
    private array $params = [];
    public ?array $user = null;
    public ?array $guestStatus = null;
    public ?string $guestIdentifier = null;  // ✅ FIXED: Added this property

    /**
     * Get request method (GET, POST, etc.)
     */
    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get request URI
     */
    public function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return strtok($uri, '?');
    }

    /**
     * Get request body (JSON)
     * Like req.body in Express
     */
    public function body(): array
    {
        if (empty($this->bodyData)) {
            $input = file_get_contents('php://input');
            $this->bodyData = json_decode($input, true) ?? [];
        }
        return $this->bodyData;
    }

    /**
     * Get single body parameter
     */
    public function input(string $key, $default = null)
    {
        return $this->body()[$key] ?? $default;
    }

    /**
     * Get query parameter
     * Like req.query in Express
     */
    public function query(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Get route parameter
     * Like req.params in Express
     */
    public function param(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Set route parameters (called by Router)
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Get request header
     * Like req.headers in Express
     */
    public function header(string $name): ?string
    {
        $name = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$name] ?? null;
    }

    /**
     * Get authorization bearer token
     * Improved version with multiple fallbacks
     */
    public function bearerToken(): ?string
    {
        // Method 1: Check Authorization header via header() method
        $header = $this->header('Authorization');
        
        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }
        
        // Method 2: Check HTTP_AUTHORIZATION directly
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return trim($matches[1]);
            }
        }
        
        // Method 3: Check REDIRECT_HTTP_AUTHORIZATION (some server configs)
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
                return trim($matches[1]);
            }
        }
        
        // Method 4: Check apache_request_headers if available
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                    return trim($matches[1]);
                }
            }
            if (isset($headers['authorization'])) {
                if (preg_match('/Bearer\s+(.*)$/i', $headers['authorization'], $matches)) {
                    return trim($matches[1]);
                }
            }
        }
        
        return null;
    }

    /**
     * Get all request headers (useful for debugging)
     */
    public function headers(): array
    {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }
        
        return $headers;
    }

    /**
     * Get client IP address
     */
    public function ip(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }

    /**
     * Get user agent
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if request is JSON
     */
    public function isJson(): bool
    {
        return str_contains(
            $this->header('Content-Type') ?? '',
            'application/json'
        );
    }

    /**
     * Check if request method matches
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($this->method()) === strtoupper($method);
    }

    /**
     * Magic getter for dynamic properties
     */
    public function __get(string $name)
    {
        return $this->$name ?? null;
    }

    /**
     * Magic setter for dynamic properties
     */
    public function __set(string $name, $value): void
    {
        $this->$name = $value;
    }
}