<?php
declare(strict_types=1);
require __DIR__ . '/_db.php';
require __DIR__ . '/_admin.php';
nis2_require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') nis2_json(['error'=>'method_not_allowed'],405);
nis2_verify_csrf();
$b=nis2_body(); $code=strtoupper(trim((string)($b['code']??'')));
$round=filter_var($b['round']??null,FILTER_VALIDATE_INT);
if($code==='' || $round===false || $round<0 || $round>7) nis2_json(['error'=>'invalid_payload'],422);
$q=nis2_db()->prepare('UPDATE nis2_sessions SET active_round=? WHERE session_code=?');
$q->execute([$round,$code]);
if(!$q->rowCount()) nis2_json(['error'=>'session_not_found_or_unchanged'],404);
nis2_json(['ok'=>true,'activeRound'=>$round]);
