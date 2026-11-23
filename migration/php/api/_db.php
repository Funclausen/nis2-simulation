<?php
declare(strict_types=1);

function nis2_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $local = __DIR__ . '/config.local.php';
    if (!is_file($local)) {
        throw new RuntimeException('NIS2 database configuration is missing.');
    }
    $cfg = require $local;
    $pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function nis2_json(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function nis2_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data)) nis2_json(['error' => 'invalid_json'], 400);
    return $data;
}
