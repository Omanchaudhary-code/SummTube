<?php
namespace App\Core;

class Request {
    public static function body(): array {
        return json_decode(file_get_contents("php://input"), true) ?? [];
    }
}
