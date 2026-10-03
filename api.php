<?php
declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'None');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure', '1');
// GitHub Pages frontend -> PHP API requires credentialed CORS.
$allowedOrigin = 'https://infotechrocksolution-sudo.github.io';
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($requestOrigin === $allowedOrigin) {
  header('Access-Control-Allow-Origin: '.$allowedOrigin);
  header('Access-Control-Allow-Credentials: true');
  header('Vary: Origin');
  header('Access-Control-Allow-Headers: Content-Type');
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  if ($requestOrigin !== $allowedOrigin) { http_response_code(403); exit; }
  http_response_code(204); exit;
}
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

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
    // Backward-compatible migration for installations created before optimistic sync versions.
    $column = $pdo->query("SHOW COLUMNS FROM tfms_app_state LIKE 'version'")->fetch();
    if (!$column) $pdo->exec('ALTER TABLE tfms_app_state ADD COLUMN version BIGINT UNSIGNED NOT NULL DEFAULT 0');
  } catch (Throwable $e) {
    error_log('TFMS database connection/migration error: '.$e->getMessage());
    out(['ok'=>false,'error'=>'Could not connect to or prepare the database. Check config.php and database.sql setup.'], 503);
  }
  return $pdo;
}
function empty_state(): array { return ['tasks'=>[],'standups'=>[],'notes'=>[],'roadmap'=>[]]; }
function read_state(PDO $pdo): array {
  $stmt=$pdo->query('SELECT state_json, updated_by, version FROM tfms_app_state WHERE state_id=1');
  $row=$stmt->fetch();
  if (!$row) return ['data'=>empty_state(), 'initialized'=>false, 'version'=>0];
  $state=json_decode((string)$row['state_json'], true);
  return [
    'data'=>is_array($state) ? $state : empty_state(),
    'initialized'=>($row['updated_by'] ?? 'system') !== 'system',
    'version'=>(int)($row['version'] ?? 0)
  ];
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
if ($action==='health' && $_SERVER['REQUEST_METHOD']==='GET') {
  $result=['ok'=>true,'php'=>true,'database'=>false,'table'=>false,'versioning'=>false,'message'=>'PHP API is running.'];
  if (!is_file(__DIR__.'/config.php')) {
    $result['ok']=false;
    $result['message']='PHP API is running, but config.php is missing. Create the server-only config.php from config.example.php.';
    out($result,503);
  }
  try {
    $pdo=db();
    $check=$pdo->query("SHOW TABLES LIKE 'tfms_app_state'")->fetchColumn();
    $result['database']=true;
    $result['table']=(bool)$check;
    $result['versioning']=(bool)$pdo->query("SHOW COLUMNS FROM tfms_app_state LIKE 'version'")->fetch();
    if (!$result['table'] || !$result['versioning']) {
      $result['ok']=false;
      $result['message']='Database table or sync-version column is missing. Check database.sql and database permissions.';
      out($result,503);
    }
    $result['message']='PHP API, database and optimistic sync versioning are reachable.';
    out($result);
  } catch (Throwable $e) {
    error_log('TFMS health check error: '.$e->getMessage());
    out(['ok'=>false,'php'=>true,'database'=>false,'table'=>false,'versioning'=>false,'message'=>'PHP API is running, but database connection/check failed. Verify config.php and database setup.'],503);
  }
}
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
  try { $state=read_state(db()); out(['ok'=>true,'data'=>$state['data'],'initialized'=>$state['initialized'],'version'=>$state['version']]); }
  catch (Throwable $e) { error_log('TFMS load error: '.$e->getMessage()); out(['ok'=>false,'error'=>'Unable to load shared database data.'],500); }
}
if ($action==='save' && $_SERVER['REQUEST_METHOD']==='POST') {
  $user=signed_in();
  $payload=json_decode(file_get_contents('php://input') ?: '',true);
  // Accept the old raw-state format for one-time compatibility with older clients.
  $incoming=is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
  $baseVersion=array_key_exists('baseVersion',$payload ?? []) ? (int)$payload['baseVersion'] : null;
  if (!is_array($incoming) || !isset($incoming['tasks']) || !is_array($incoming['tasks'])) out(['ok'=>false,'error'=>'Invalid task data.'],400);
  $pdo=db();
  try {
    $pdo->beginTransaction();
    $stmt=$pdo->query('SELECT state_json, updated_by, version FROM tfms_app_state WHERE state_id=1 FOR UPDATE');
    $row=$stmt->fetch();
    $current=$row ? (json_decode((string)$row['state_json'],true) ?: empty_state()) : empty_state();
    $currentVersion=(int)($row['version'] ?? 0);
    // Versioned clients must reload/merge when another member saved first.
    if ($baseVersion !== null && $baseVersion !== $currentVersion) {
      $pdo->rollBack();
      out(['ok'=>false,'conflict'=>true,'error'=>'Shared data changed on another device. The latest data has been returned for a safe merge.','version'=>$currentVersion,'data'=>$current],409);
    }
    $incoming['roadmap']=is_array($incoming['roadmap'] ?? null) ? $incoming['roadmap'] : ($current['roadmap'] ?? []);
    $roadmapChanged=json_encode($incoming['roadmap']) !== json_encode($current['roadmap'] ?? []);
    if ($row && array_key_exists('roadmap',$current) && $roadmapChanged && $user['name'] !== 'Shevik') {
      $pdo->rollBack();
      out(['ok'=>false,'error'=>'Only the Super Admin can edit roadmap milestones.'],403);
    }
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
    if ($row) {
      $upsert=$pdo->prepare('UPDATE tfms_app_state SET state_json=:state, updated_by=:user, version=version+1 WHERE state_id=1');
      $upsert->execute(['state'=>$json,'user'=>$user['name']]);
    } else {
      $upsert=$pdo->prepare('INSERT INTO tfms_app_state (state_id,state_json,updated_by,version) VALUES (1,:state,:user,1)');
      $upsert->execute(['state'=>$json,'user'=>$user['name']]);
    }
    $newVersion=$currentVersion+1;
    $pdo->commit();
    out(['ok'=>true,'version'=>$newVersion]);
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('TFMS save error: '.$e->getMessage());
    out(['ok'=>false,'error'=>'Unable to save shared database data. Check database setup and permissions.'],500);
  }
}
out(['ok'=>false,'error'=>'Unknown action.'],404);
