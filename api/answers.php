<?php
declare(strict_types=1);
require __DIR__ . '/_db.php';
require __DIR__ . '/_admin.php';
nis2_require_admin();

$code = strtoupper(trim((string)($_GET['code'] ?? '')));
$round = isset($_GET['round']) ? filter_var($_GET['round'], FILTER_VALIDATE_INT) : null;
if ($code === '' || $round === false || ($round !== null && ($round < 0 || $round > 7))) {
    nis2_json(['error' => 'invalid_query'], 422);
}
$sql = 'SELECT a.round_no, t.browser_id, t.team_name, a.quality, a.short_text, a.answered_at
        FROM nis2_answers a
        JOIN nis2_sessions s ON s.id = a.session_id
        JOIN nis2_teams t ON t.id = a.team_id
        WHERE s.session_code = ?';
$params = [$code];
if ($round !== null) {
    $sql .= ' AND a.round_no = ?';
    $params[] = $round;
}
$q = nis2_db()->prepare($sql . ' ORDER BY a.round_no, a.answered_at');
$q->execute($params);
nis2_json(['answers' => $q->fetchAll()]);
