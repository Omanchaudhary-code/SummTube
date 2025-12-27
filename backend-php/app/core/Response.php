<?php
namespace App\Core;

class Response {
    public static function json($data, int $code = 200) {
        http_response_code($code);
        echo json_encode($data);
    }
}
