<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
const DATA_FILE = __DIR__ . '/tfms-shared-data.json';
$members = [
 'sadi' => ['name'=>'Sadi','role'=>'Full-stack developer'],
 'tanvir' => ['name'=>'Tanvir','role'=>'Operations head'],
 'shevik' => ['name'=>'Shevik','role'=>'Strategy, QC & marketing head'],
 'masud' => ['name'=>'Masud','role'=>'Security head']
];
function out(array $v, int $status=200): never { http_response_code($status); echo json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function data_read(): array { if (!is_file(DATA_FILE)) return ['tasks'=>[],'standups'=>[],'notes'=>[]]; $v=json_decode(file_get_contents(DATA_FILE) ?: '', true); return is_array($v) ? $v : ['tasks'=>[],'standups'=>[],'notes'=>[]]; }
function data_write(array $v): bool { $s=json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); if ($s===false) return false; $tmp=DATA_FILE.'.tmp'; if (file_put_contents($tmp,$s,LOCK_EX)===false) return false; return rename($tmp,DATA_FILE); }
function auth_user(): array { if (empty($_SESSION['tfms_user'])) out(['ok'=>false,'error'=>'Please sign in again.'],401); return $_SESSION['tfms_user']; }
$action=$_GET['action'] ?? '';
if ($action==='login' && $_SERVER['REQUEST_METHOD']==='POST') {
 $v=json_decode(file_get_contents('php://input') ?: '',true); $name=strtolower(trim((string)($v['name'] ?? ''))); $password=(string)($v['password'] ?? '');
 if (!isset($members[$name]) || !hash_equals('1234',$password)) out(['ok'=>false,'error'=>'Name or password does not match.'],401);
 session_regenerate_id(true); $_SESSION['tfms_user']=$members[$name]; out(['ok'=>true,'user'=>$_SESSION['tfms_user']]);
}
if ($action==='me' && $_SERVER['REQUEST_METHOD']==='GET') { out(['ok'=>true,'user'=>$_SESSION['tfms_user'] ?? null]); }
if ($action==='logout' && $_SERVER['REQUEST_METHOD']==='POST') { $_SESSION=[]; if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']); } session_destroy(); out(['ok'=>true]); }
if ($action==='load' && $_SERVER['REQUEST_METHOD']==='GET') { auth_user(); out(['ok'=>true,'data'=>data_read()]); }
if ($action==='save' && $_SERVER['REQUEST_METHOD']==='POST') {
 $user=auth_user(); $incoming=json_decode(file_get_contents('php://input') ?: '',true);
 if (!is_array($incoming) || !isset($incoming['tasks']) || !is_array($incoming['tasks'])) out(['ok'=>false,'error'=>'Invalid task data.'],400);
 $current=data_read(); $old=[]; $new=[];
 foreach (($current['tasks'] ?? []) as $t) if (isset($t['id'])) $old[(string)$t['id']]=$t;
 foreach ($incoming['tasks'] as $t) if (isset($t['id'])) $new[(string)$t['id']]=$t;
 $manager=in_array($user['name'],['Tanvir','Shevik'],true);
 foreach ($old as $id=>$t) {
  if (!isset($new[$id]) && !$manager) out(['ok'=>false,'error'=>'Only Tanvir and Shevik can delete tasks.'],403);
  if (isset($new[$id]) && ($t['status'] ?? '')==='Done' && ($new[$id]['status'] ?? '')==='In progress' && !$manager) out(['ok'=>false,'error'=>'Only Tanvir and Shevik can reopen completed tasks.'],403);
 }
 $incoming['standups']=is_array($incoming['standups'] ?? null) ? $incoming['standups'] : ($current['standups'] ?? []);
 $incoming['notes']=is_array($incoming['notes'] ?? null) ? $incoming['notes'] : ($current['notes'] ?? []);
 if (!data_write($incoming)) out(['ok'=>false,'error'=>'Could not write shared data. Check PHP write permissions.'],500);
 out(['ok'=>true]);
}
out(['ok'=>false,'error'=>'Unknown action.'],404);
?>