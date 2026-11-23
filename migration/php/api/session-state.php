<?php
declare(strict_types=1);
require __DIR__ . '/_db.php';
require __DIR__ . '/_admin.php';
nis2_require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') nis2_json(['error'=>'method_not_allowed'],405);
nis2_verify_csrf();
$b=nis2_body();
$code=strtoupper(trim((string)($b['code']??'')));
$status=(string)($b['status']??'');
if($code==='' || !in_array($status,['waiting','active','finished'],true)) nis2_json(['error'=>'invalid_payload'],422);
$sql='UPDATE nis2_sessions SET status=?, started_at=CASE WHEN ?="active" AND started_at IS NULL THEN CURRENT_TIMESTAMP ELSE started_at END, finished_at=CASE WHEN ?="finished" THEN CURRENT_TIMESTAMP ELSE finished_at END WHERE session_code=?';
$q=nis2_db()->prepare($sql); $q->execute([$status,$status,$status,$code]);
if(!$q->rowCount()) nis2_json(['error'=>'session_not_found_or_unchanged'],404);
nis2_json(['ok'=>true]);
