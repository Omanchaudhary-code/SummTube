<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Usage {

    public static function canUse(string $ip): bool {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT usage_count FROM free_usage WHERE ip_address=?");
        $stmt->execute([$ip]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return !$row || $row['usage_count'] < 3;
    }

    public static function increment(string $ip): void {
        $db = Database::connect();
        $stmt = $db->prepare("
            INSERT INTO free_usage (ip_address, usage_count)
            VALUES (?,1)
            ON DUPLICATE KEY UPDATE usage_count=usage_count+1
        ");
        $stmt->execute([$ip]);
    }
}
