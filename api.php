<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

function out(array $body, int $status=200): never {
  http_response_code($status);
  echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function db(): PDO {
  static $pdo = null;
  if ($pdo instanceof PDO) return $pdo;
  $configFile = __DIR__ . '/config.php';
  if (!is_file($configFile)) out(['ok'=>false,'error'=>'Database setup is incomplete: copy config.example.php to config.php and enter your MySQL credentials.'], 503);
  $cfg = require $configFile;
  try {
    $pdo = new PDO(
      'mysql:host='.$cfg['db_host'].';dbname='.$cfg['db_name'].';charset=utf8mb4',
      $cfg['db_user'],
      $cfg['db_pass'],
      [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]
    );
  } catch (Throwable $e) {
    error_log('TFMS database connection error: '.$e->getMessage());
    out(['ok'=>false,'error'=>'Could not connect to the database. Check config.php and database.sql setup.'], 503);
  }
  return $pdo;
}
function read_state(PDO $pdo): array {
  $stmt=$pdo->query('SELECT state_json, updated_by FROM tfms_app_state WHERE state_id=1');
  $row=$stmt->fetch();
  if (!$row) return ['data'=>['tasks'=>[],'standups'=>[],'notes'=>[]], 'initialized'=>false];
  $state=json_decode((string)$row['state_json'], true);
  return ['data'=>is_array($state) ? $state : ['tasks'=>[],'standups'=>[],'notes'=>[]], 'initialized'=>($row['updated_by'] ?? 'system') !== 'system'];
}
function signed_in(): array {
  if (empty($_SESSION['tfms_user'])) out(['ok'=>false,'error'=>'Please sign in again.'],401);
  return $_SESSION['tfms_user'];
}
$members = [
 'sadi'=>['name'=>'Sadi','role'=>'Full-stack developer'],
 'tanvir'=>['name'=>'Tanvir','role'=>'Operations head'],
 'shevik'=>['name'=>'Shevik','role'=>'Strategy, QC & marketing head'],
 'masud'=>['name'=>'Masud','role'=>'Security head']
];
$action=$_GET['action'] ?? '';
if ($action==='login' && $_SERVER['REQUEST_METHOD']==='POST') {
  $input=json_decode(file_get_contents('php://input') ?: '',true);
  $name=strtolower(trim((string)($input['name'] ?? '')));
  $password=(string)($input['password'] ?? '');
  if (!isset($members[$name]) || !hash_equals('1234',$password)) out(['ok'=>false,'error'=>'Name or password does not match.'],401);
  session_regenerate_id(true);
  $_SESSION['tfms_user']=$members[$name];
  out(['ok'=>true,'user'=>$_SESSION['tfms_user']]);
}
if ($action==='me' && $_SERVER['REQUEST_METHOD']==='GET') out(['ok'=>true,'user'=>$_SESSION['tfms_user'] ?? null]);
if ($action==='logout' && $_SERVER['REQUEST_METHOD']==='POST') {
  $_SESSION=[];
  if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); }
  session_destroy(); out(['ok'=>true]);
}
if ($action==='load' && $_SERVER['REQUEST_METHOD']==='GET') {
  signed_in();
  try { $state=read_state(db()); out(['ok'=>true,'data'=>$state['data'],'initialized'=>$state['initialized']]); }
  catch (Throwable $e) { error_log('TFMS load error: '.$e->getMessage()); out(['ok'=>false,'error'=>'Unable to load shared database data.'],500); }
}
if ($action==='save' && $_SERVER['REQUEST_METHOD']==='POST') {
  $user=signed_in();
  $incoming=json_decode(file_get_contents('php://input') ?: '',true);
  if (!is_array($incoming) || !isset($incoming['tasks']) || !is_array($incoming['tasks'])) out(['ok'=>false,'error'=>'Invalid task data.'],400);
  $pdo=db();
  try {
    $pdo->beginTransaction();
    $stmt=$pdo->query('SELECT state_json FROM tfms_app_state WHERE state_id=1 FOR UPDATE');
    $row=$stmt->fetch();
    $current=$row ? (json_decode((string)$row['state_json'],true) ?: ['tasks'=>[],'standups'=>[],'notes'=>[]]) : ['tasks'=>[],'standups'=>[],'notes'=>[]];
    $old=[]; $new=[];
    foreach (($current['tasks'] ?? []) as $task) if (isset($task['id'])) $old[(string)$task['id']]=$task;
    foreach ($incoming['tasks'] as $task) if (isset($task['id'])) $new[(string)$task['id']]=$task;
    $manager=in_array($user['name'],['Tanvir','Shevik'],true);
    foreach ($old as $id=>$task) {
      if (!isset($new[$id]) && !$manager) { $pdo->rollBack(); out(['ok'=>false,'error'=>'Only Tanvir and Shevik can delete tasks.'],403); }
      if (isset($new[$id]) && ($task['status'] ?? '')==='Done' && ($new[$id]['status'] ?? '')==='In progress' && !$manager) { $pdo->rollBack(); out(['ok'=>false,'error'=>'Only Tanvir and Shevik can reopen completed tasks.'],403); }
    }
    $incoming['standups']=is_array($incoming['standups'] ?? null) ? $incoming['standups'] : ($current['standups'] ?? []);
    $incoming['notes']=is_array($incoming['notes'] ?? null) ? $incoming['notes'] : ($current['notes'] ?? []);
    $json=json_encode($incoming,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if ($json===false) { $pdo->rollBack(); out(['ok'=>false,'error'=>'Could not encode data.'],400); }
    $upsert=$pdo->prepare('INSERT INTO tfms_app_state (state_id,state_json,updated_by) VALUES (1,:state,:user) ON DUPLICATE KEY UPDATE state_json=VALUES(state_json), updated_by=VALUES(updated_by)');
    $upsert->execute(['state'=>$json,'user'=>$user['name']]);
    $pdo->commit();
    out(['ok'=>true]);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('TFMS save error: '.$e->getMessage());
    out(['ok'=>false,'error'=>'Unable to save shared database data. Check database setup and permissions.'],500);
  }
}
out(['ok'=>false,'error'=>'Unknown action.'],404);
