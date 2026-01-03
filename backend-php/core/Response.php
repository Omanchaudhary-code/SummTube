<?php

namespace Core;

/**
 * HTTP Response Wrapper
 * Like res object in Express.js
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private bool $sent = false;

    /**
     * Send JSON response
     * Like res.json() in Express
     * 
     * @param array $data
     * @param int $statusCode
     * @return void
     */
    public function json(array $data, int $statusCode = 200): void
    {
        if ($this->sent) {
            return;
        }

        $this->statusCode = $statusCode;
        $this->header('Content-Type', 'application/json');
        
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        $this->sent = true;
    }

    /**
     * Send plain text response
     * 
     * @param string $text
     * @param int $statusCode
     * @return void
     */
    public function text(string $text, int $statusCode = 200): void
    {
        if ($this->sent) {
            return;
        }

        $this->statusCode = $statusCode;
        $this->header('Content-Type', 'text/plain');
        
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        echo $text;
        
        $this->sent = true;
    }

    /**
     * Send HTML response
     * 
     * @param string $html
     * @param int $statusCode
     * @return void
     */
    public function html(string $html, int $statusCode = 200): void
    {
        if ($this->sent) {
            return;
        }

        $this->statusCode = $statusCode;
        $this->header('Content-Type', 'text/html');
        
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        echo $html;
        
        $this->sent = true;
    }

    /**
     * Set response header
     * Like res.header() in Express
     * 
     * @param string $name
     * @param string $value
     * @return self
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set response status code
     * Like res.status() in Express
     * 
     * @param int $code
     * @return self
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Redirect to URL
     * Like res.redirect() in Express
     * 
     * @param string $url
     * @param int $statusCode
     * @return void
     */
    public function redirect(string $url, int $statusCode = 302): void
    {
        http_response_code($statusCode);
        header("Location: $url");
        exit;
    }

    /**
     * Send 404 Not Found
     * 
     * @param string $message
     * @return void
     */
    public function notFound(string $message = 'Not Found'): void
    {
        $this->json(['error' => $message], 404);
    }

    /**
     * Send 401 Unauthorized
     * 
     * @param string $message
     * @return void
     */
    public function unauthorized(string $message = 'Unauthorized'): void
    {
        $this->json(['error' => $message], 401);
    }

    /**
     * Send 403 Forbidden
     * 
     * @param string $message
     * @return void
     */
    public function forbidden(string $message = 'Forbidden'): void
    {
        $this->json(['error' => $message], 403);
    }

    /**
     * Send 500 Internal Server Error
     * 
     * @param string $message
     * @return void
     */
    public function serverError(string $message = 'Internal Server Error'): void
    {
        $this->json(['error' => $message], 500);
    }

    /**
     * Check if response has been sent
     * 
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->sent;
    }
}