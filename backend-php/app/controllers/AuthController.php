<?php
namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class AuthController {
    public function googleLogin() {
        Response::json(["message" => "Google OAuth placeholder"]);
    }
}
