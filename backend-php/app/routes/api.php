<?php
$router->get('/', function () {
    echo json_encode([
        "status" => "OK",
        "message" => "SummTube PHP API running"
    ]);
});

use App\Controllers\AuthController;
use App\Controllers\SummaryController;

/** @var $router \App\Core\Router */

$router->post('/auth/google', [AuthController::class, 'googleLogin']);
$router->post('/summarize', [SummaryController::class, 'summarize']);
