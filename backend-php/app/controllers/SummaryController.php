<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Usage;
use App\Services\PythonAPIService;

class SummaryController {

    public function summarize() {
        $body = Request::body();
        $url = $body['url'] ?? null;

        if (!$url) {
            Response::json(["error" => "YouTube URL required"], 400);
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'];

        if (!Usage::canUse($ip)) {
            Response::json(["error" => "Free limit reached"], 403);
            return;
        }

        $summary = PythonAPIService::summarize($url);
        Usage::increment($ip);

        Response::json(["summary" => $summary]);
    }
}
