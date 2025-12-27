<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    public static function connect(): PDO {
        try {
            return new PDO(
                "mysql:host=".$_ENV['DB_HOST'].";dbname=".$_ENV['DB_NAME'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "DB connection failed"]);
            exit;
        }
    }
}
