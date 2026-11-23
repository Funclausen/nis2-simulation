<?php
declare(strict_types=1);
require __DIR__ . '/_db.php';
require __DIR__ . '/_admin.php';
nis2_require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') nis2_json(['error'=>'method_not_allowed'],405);
nis2_verify_csrf();
$b=nis2_body();
$title=trim((string)($b['title']??'NIS2 Ledelsessimulation'));
$expected=(int)($b['expectedParticipants']??15);
$expected=max(1,min(500,$expected));
$code='';
for($i=0;$i<10;$i++){
  $code=strtoupper(substr(bin2hex(random_bytes(5)),0,8));
  try{
    $q=nis2_db()->prepare('INSERT INTO nis2_sessions (session_code,title,expected_participants,created_by) VALUES (?,?,?,?)');
    $q->execute([$code,mb_substr($title,0,160),$expected,nis2_admin_user_id()]);
    nis2_json(['ok'=>true,'session'=>['code'=>$code,'status'=>'waiting','activeRound'=>0]]);
  }catch(PDOException $e){
    if((string)$e->getCode()!=='23000') throw $e;
  }
}
nis2_json(['error'=>'code_generation_failed'],500);
