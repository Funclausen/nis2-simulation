<?php
declare(strict_types=1);
require __DIR__ . '/_db.php';
require __DIR__ . '/_admin.php';
nis2_require_admin();
$code=strtoupper(trim((string)($_GET['code']??'')));
$round=filter_var($_GET['round']??null,FILTER_VALIDATE_INT);
if($code==='' || $round===false || $round<0 || $round>7) nis2_json(['error'=>'invalid_query'],422);
$q=nis2_db()->prepare('SELECT t.browser_id,t.team_name,a.quality,a.short_text,a.answered_at FROM nis2_answers a JOIN nis2_sessions s ON s.id=a.session_id JOIN nis2_teams t ON t.id=a.team_id WHERE s.session_code=? AND a.round_no=? ORDER BY a.answered_at');
$q->execute([$code,$round]);
nis2_json(['answers'=>$q->fetchAll()]);
