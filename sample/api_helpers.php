<?php

$origin = $_SERVER['HTTP_origin'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function json_response(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function get_json_input(): ?array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return null;
    }
    return json_decode($raw, true);
}

