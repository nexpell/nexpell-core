<?php
// admin/plugin_widgets_save.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* -------- Security / Response Headers -------- */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'method not allowed'], JSON_UNESCAPED_UNICODE);
  exit;
}

/* -------- Optionales Debug-Log -------- */
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
function nx_log($msg): void {
  // file_put_contents(__DIR__.'/_save_debug.log', '['.date('c').'] '.(is_string($msg)?$msg:json_encode($msg, JSON_UNESCAPED_UNICODE)).PHP_EOL, FILE_APPEND);
}

/* -------- DB Bootstrap -------- */
require_once __DIR__ . '/../system/config.inc.php';
$_database = $_database ?? new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($_database->connect_errno) {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>'db connect: '.$_database->connect_error], JSON_UNESCAPED_UNICODE);
  nx_log(['db_connect_error'=>$_database->connect_error]);
  exit;
}
$_database->set_charset('utf8mb4');

/* -------- INPUT -------- */
$raw   = file_get_contents('php://input') ?: '';
$input = json_decode($raw, true);
if (!is_array($input)) $input = [];

/* -------- CSRF -------- */
$hdr   = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$bodyC = (string)($input['csrf'] ?? '');
$sess  = $_SESSION['csrf_token'] ?? '';
$token = $hdr ?: $bodyC;

if (!$token || !$sess || !hash_equals($sess, $token)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'CSRF invalid'], JSON_UNESCAPED_UNICODE);
  nx_log(['csrf_fail'=>['sess'=>$sess, 'recv'=>$token]]);
  exit;
}

/* -------- Pflichtfelder -------- */
$page = isset($input['page']) && is_string($input['page']) ? trim($input['page']) : '';
$data = isset($input['data']) && is_array($input['data']) ? $input['data'] : [];

if ($page === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'missing page'], JSON_UNESCAPED_UNICODE);
  nx_log('missing page');
  exit;
}
if (!preg_match('#^[A-Za-z0-9/_-]{1,128}$#', $page)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'invalid page'], JSON_UNESCAPED_UNICODE);
  exit;
}

/* -------- optionale Flags -------- */
$validPositions = ['top','undertop','left','maintop','mainbottom','right','bottom'];

$flt = static function(array $arr) use ($validPositions): array {
  $out=[]; foreach ($arr as $p) if (is_string($p) && in_array($p, $validPositions, true)) $out[$p]=true;
  return array_keys($out);
};

$replacePositions   = isset($input['replacePositions']) ? $flt((array)$input['replacePositions']) : [];
$clearPositions     = isset($input['clearPositions'])   ? $flt((array)$input['clearPositions'])   : [];
$removedInstanceIds = [];
if (!empty($input['removedInstanceIds']) && is_array($input['removedInstanceIds'])) {
  foreach ($input['removedInstanceIds'] as $iid) {
    if (is_string($iid) && $iid!=='') $removedInstanceIds[] = $iid;
  }
}

/* -------- Prefetch: Titel & modulname pro Widget -------- */
$titles = [];
$widgetToModule = [];

if ($res = $_database->query("
  SELECT w.widget_key,
         COALESCE(NULLIF(w.title,''), w.widget_key) AS t,
         w.plugin AS modulname
  FROM settings_widgets w
")) {
  while ($row = $res->fetch_assoc()) {
     $wk = (string)$row['widget_key'];
     $titles[$wk] = ($row['t'] ?? $wk) ?: $wk;
     $widgetToModule[$wk] = (string)($row['modulname'] ?? '');
  }
  $res->free();
}

/* -------- Schema-Check: hat Tabelle 'modulname'? -------- */
$has_modulname = false;
if ($res = $_database->query("SHOW COLUMNS FROM `settings_widgets_positions` LIKE 'modulname'")) {
  $has_modulname = (bool)$res->num_rows;
  $res->free();
}

/* -------- Hinweis: notwendige Indizes/Unique-Key (Migration) --------
ALTER TABLE `settings_widgets_positions`
  ADD UNIQUE KEY `uniq_page_pos_iid` (`page`,`position`,`instance_id`);
CREATE INDEX `idx_page_pos_sort` ON `settings_widgets_positions` (`page`,`position`,`sort_order`);
CREATE INDEX `idx_page_iid`      ON `settings_widgets_positions` (`page`,`instance_id`);
-- falls modulname NOT NULL:
-- ALTER TABLE `settings_widgets_positions` MODIFY `modulname` VARCHAR(100) NOT NULL DEFAULT '';
--------------------------------------------------------------------- */

try {
  $_database->begin_transaction();

  // 0) gezielte Instanz-Löschung
  if (!empty($removedInstanceIds)) {
    $ph = implode(',', array_fill(0, count($removedInstanceIds), '?'));
    $sql = "DELETE FROM settings_widgets_positions WHERE page = ? AND instance_id IN ($ph)";
    $stmt = $_database->prepare($sql);
    if (!$stmt) throw new RuntimeException('prepare delete iids: '.$_database->error);
    $types = 's' . str_repeat('s', count($removedInstanceIds));
    $params = array_merge([$page], $removedInstanceIds);
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) throw new RuntimeException('exec delete iids: '.$stmt->error);
    $stmt->close();
  }

  // 1) clearPositions
  if (!empty($clearPositions)) {
    $ph = implode(',', array_fill(0, count($clearPositions), '?'));
    $sql = "DELETE FROM settings_widgets_positions WHERE page = ? AND position IN ($ph)";
    $stmt = $_database->prepare($sql);
    if (!$stmt) throw new RuntimeException('prepare clear pos: '.$_database->error);
    $types = 's' . str_repeat('s', count($clearPositions));
    $params = array_merge([$page], $clearPositions);
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) throw new RuntimeException('exec clear pos: '.$stmt->error);
    $stmt->close();
  }

  // 2) replacePositions
  if (!empty($replacePositions)) {
    $ph = implode(',', array_fill(0, count($replacePositions), '?'));
    $sql = "DELETE FROM settings_widgets_positions WHERE page = ? AND position IN ($ph)";
    $stmt = $_database->prepare($sql);
    if (!$stmt) throw new RuntimeException('prepare replace pos: '.$_database->error);
    $types = 's' . str_repeat('s', count($replacePositions));
    $params = array_merge([$page], $replacePositions);
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) throw new RuntimeException('exec replace pos: '.$stmt->error);
    $stmt->close();
  }

  // 3) Additives Upsert
  if ($has_modulname) {
    $sql = "
      INSERT INTO settings_widgets_positions
        (page, position, sort_order, widget_key, instance_id, settings, title, modulname)
      VALUES (?,?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE
        sort_order = VALUES(sort_order),
        settings   = VALUES(settings),
        title      = VALUES(title),
        modulname  = VALUES(modulname)
    ";
    $ins = $_database->prepare($sql);
    if (!$ins) throw new RuntimeException('prepare upsert(+modulname): ' . $_database->error);

    foreach ($data as $position => $items) {
      if (!in_array($position, $validPositions, true) || !is_array($items)) continue;

      $sort = 0;
      foreach ($items as $item) {
        $widget_key  = (string)($item['widget_key']  ?? '');
        $instance_id = (string)($item['instance_id'] ?? '');
        $settings    = $item['settings'] ?? new stdClass();
        if ($widget_key === '' || $instance_id === '') continue;

        if (is_array($settings) && empty($settings)) $settings = new stdClass();
        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if ($settingsJson === false) {
          throw new RuntimeException('json encode failed for instance '.$instance_id.': '.json_last_error_msg());
        }

        $title_val = $titles[$widget_key] ?? $widget_key;
        $modulname = $widgetToModule[$widget_key] ?? '';

        $ins->bind_param('ssisssss', $page, $position, $sort, $widget_key, $instance_id, $settingsJson, $title_val, $modulname);
        if (!$ins->execute()) throw new RuntimeException('exec upsert(+modulname): '.$ins->error);
        $sort++;
      }
    }
    $ins->close();

  } else {
    $sql = "
      INSERT INTO settings_widgets_positions
        (page, position, sort_order, widget_key, instance_id, settings, title)
      VALUES (?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE
        sort_order = VALUES(sort_order),
        settings   = VALUES(settings),
        title      = VALUES(title)
    ";
    $ins = $_database->prepare($sql);
    if (!$ins) throw new RuntimeException('prepare upsert: ' . $_database->error);

    foreach ($data as $position => $items) {
      if (!in_array($position, $validPositions, true) || !is_array($items)) continue;

      $sort = 0;
      foreach ($items as $item) {
        $widget_key  = (string)($item['widget_key']  ?? '');
        $instance_id = (string)($item['instance_id'] ?? '');
        $settings    = $item['settings'] ?? new stdClass();
        if ($widget_key === '' || $instance_id === '') continue;

        if (is_array($settings) && empty($settings)) $settings = new stdClass();
        $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if ($settingsJson === false) {
          throw new RuntimeException('json encode failed for instance '.$instance_id.': '.json_last_error_msg());
        }

        $title_val = $titles[$widget_key] ?? $widget_key;

        $ins->bind_param('ssissss', $page, $position, $sort, $widget_key, $instance_id, $settingsJson, $title_val);
        if (!$ins->execute()) throw new RuntimeException('exec upsert: '.$ins->error);
        $sort++;
      }
    }
    $ins->close();
  }

  $_database->commit();
  echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
  nx_log(['commit_ok'=>['page'=>$page]]);

} catch (Throwable $e) {
  $_database->rollback();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'db error: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
  nx_log(['exception'=>$e->getMessage()]);
}
