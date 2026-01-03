<?php

namespace Core;

/**
 * Router Class
 * Like Express Router
 */
class Router
{
    private array $routes = [];
    private array $middleware = [];
    private $notFoundHandler = null;

    /**
     * Add GET route
     * Like app.get() in Express
     * 
     * @param string $path
     * @param array|callable $handler
     * @param array $middleware
     * @return void
     */
    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Add POST route
     * Like app.post() in Express
     * 
     * @param string $path
     * @param array|callable $handler
     * @param array $middleware
     * @return void
     */
    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Add PUT route
     * 
     * @param string $path
     * @param array|callable $handler
     * @param array $middleware
     * @return void
     */
    public function put(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Add DELETE route
     * 
     * @param string $path
     * @param array|callable $handler
     * @param array $middleware
     * @return void
     */
    public function delete(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Add route with any method
     * 
     * @param string $method
     * @param string $path
     * @param array|callable $handler
     * @param array $middleware
     * @return void
     */
    private function addRoute(string $method, string $path, $handler, array $middleware): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Add global middleware
     * Like app.use() in Express
     * 
     * @param string $middlewareClass
     * @return void
     */
    public function use(string $middlewareClass): void
    {
        $this->middleware[] = $middlewareClass;
    }

    /**
     * Set custom 404 not found handler
     * 
     * @param callable $handler
     * @return void
     */
    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Dispatch request to matching route
     * 
     * @param Request|null $request
     * @param Response|null $response
     * @return void
     */
    public function dispatch(?Request $request = null, ?Response $response = null): void
    {
        $request = $request ?? new Request();
        $response = $response ?? new Response();

        $method = $request->method();
        $uri = $request->uri();

        // Run global middleware first
        foreach ($this->middleware as $middlewareClass) {
            $middleware = new $middlewareClass();
            $result = $middleware->handle($request, $response, function() { return true; });
            
            if ($result === false || $response->isSent()) {
                return;
            }
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['path'], $uri);
            
            if ($params !== false) {
                $request->setParams($params);

                // Run route-specific middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    $result = $middleware->handle($request, $response, function() { return true; });
                    
                    if ($result === false || $response->isSent()) {
                        return;
                    }
                }

                // Execute handler
                $this->executeHandler($route['handler'], $request, $response);
                return;
            }
        }

        // No route found - use custom handler or default 404
        if ($this->notFoundHandler) {
            ($this->notFoundHandler)($request, $response);
        } else {
            $response->json([
                'error' => 'Route not found',
                'path' => $uri,
                'method' => $method
            ], 404);
        }
    }

    /**
     * Match route pattern with URI
     * 
     * @param string $pattern
     * @param string $uri
     * @return array|false
     */
    private function matchRoute(string $pattern, string $uri)
    {
        // Convert route pattern to regex
        // /api/users/:id -> /api/users/(?P<id>[^/]+)
        $patternRegex = preg_replace_callback(
            '/:(\w+)/',
            function($matches) {
                return '(?P<' . $matches[1] . '>[^/]+)';
            },
            $pattern
        );
        
        $patternRegex = '#^' . $patternRegex . '$#';

        if (preg_match($patternRegex, $uri, $matches)) {
            // Extract only named parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            
            return $params;
        }

        return false;
    }

    /**
     * Execute route handler
     * 
     * @param array|callable $handler
     * @param Request $request
     * @param Response $response
     * @return void
     */
    private function executeHandler($handler, Request $request, Response $response): void
    {
        try {
            if (is_array($handler)) {
                // [ControllerClass::class, 'method']
                [$controllerClass, $method] = $handler;
                $controller = new $controllerClass();
                $controller->$method($request, $response);
            } else {
                // Callable/closure
                $handler($request, $response);
            }
        } catch (\Exception $e) {
            $this->handleException($e, $response);
        }
    }

    /**
     * Handle exceptions
     * 
     * @param \Exception $e
     * @param Response $response
     * @return void
     */
    private function handleException(\Exception $e, Response $response): void
    {
        $config = require __DIR__ . '/../config/app.php';
        
        if ($config['debug']) {
            $response->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        } else {
            $response->json([
                'error' => 'Internal server error'
            ], 500);
        }

        // Log error
        error_log('[ERROR] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
}