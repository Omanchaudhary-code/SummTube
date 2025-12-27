<?php
namespace App\Services;

class PythonAPIService {

    public static function summarize(string $url): string {
        $ch = curl_init($_ENV['PYTHON_API_URL'].'/summarize');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(["url" => $url])
        ]);

        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true);
        return $data['summary'] ?? "Summary failed";
    }
}
