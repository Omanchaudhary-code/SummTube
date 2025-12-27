<?php
namespace App\Core;

class Router {

    private array $routes = [];

    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
    }

    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }

    public function run() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (!isset($this->routes[$method][$path])) {
            http_response_code(404);
            echo json_encode(["error" => "Route not found"]);
            return;
        }

        $handler = $this->routes[$method][$path];

        if (is_array($handler)) {
            [$class, $methodName] = $handler;
            (new $class())->$methodName();
        } else {
            call_user_func($handler);
        }
    }
}
