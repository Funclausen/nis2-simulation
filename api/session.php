<?php
declare(strict_types=1);
require __DIR__ . '/_db.php';

$code = strtoupper(trim((string)($_GET['code'] ?? '')));
if ($code === '') nis2_json(['error' => 'missing_code'], 400);

$stmt = nis2_db()->prepare(
    'SELECT session_code, title, status, active_round, expected_participants, reset_version, started_at
     FROM nis2_sessions WHERE session_code = ? LIMIT 1'
);
$stmt->execute([$code]);
$row = $stmt->fetch();
if (!$row) nis2_json(['error' => 'session_not_found'], 404);

nis2_json(['session' => $row]);
