<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;

header("Content-Type: application/json");

$router = new Router();

// Load API routes
require_once __DIR__ . '/../app/routes/api.php';

// Run router
$router->run();
