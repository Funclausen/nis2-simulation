<?php
declare(strict_types=1);
require __DIR__ . '/_db.php';
require __DIR__ . '/_admin.php';
nis2_require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') nis2_json(['error'=>'method_not_allowed'],405);
nis2_verify_csrf();
$b=nis2_body(); $code=strtoupper(trim((string)($b['code']??'')));
if($code==='') nis2_json(['error'=>'invalid_payload'],422);
$pdo=nis2_db(); $pdo->beginTransaction();
try{
 $q=$pdo->prepare('SELECT id FROM nis2_sessions WHERE session_code=? FOR UPDATE'); $q->execute([$code]); $id=$q->fetchColumn();
 if(!$id){$pdo->rollBack();nis2_json(['error'=>'session_not_found'],404);}
 $q=$pdo->prepare('DELETE FROM nis2_answers WHERE session_id=?'); $q->execute([$id]);
 $q=$pdo->prepare('DELETE FROM nis2_teams WHERE session_id=?'); $q->execute([$id]);
 $q=$pdo->prepare('UPDATE nis2_sessions SET status="waiting",active_round=0,reset_version=reset_version+1,started_at=NULL,finished_at=NULL WHERE id=?'); $q->execute([$id]);
 $pdo->commit(); nis2_json(['ok'=>true]);
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();nis2_json(['error'=>'server_error'],500);}
