<?php
// === /admin/plugin_widgets_save.php ===
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
set_error_handler(function($errno, $errstr, $errfile, $errline){
  echo json_encode(['ok'=>false,'php_error'=>"$errstr in $errfile:$errline"]);
  exit;
});

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

// Nur POST erlaubt
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'method not allowed']);
  exit;
}

// DB laden
require_once __DIR__ . '/../system/core/builder_core.php';
global $_database;

/* === Input lesen === */
$raw = file_get_contents('php://input') ?: '';
$input = json_decode($raw, true);
if (!is_array($input)) $input = [];

// CSRF prüfen
$hdr   = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$bodyC = (string)($input['csrf'] ?? '');
$sess  = $_SESSION['csrf_token'] ?? '';
$token = $hdr ?: $bodyC;

if (!$token || !$sess || !hash_equals($sess, $token)) {
  http_response_code(403);
  echo json_encode(['ok'=>false,'error'=>'CSRF invalid']);
  exit;
}

$page = trim((string)($input['page'] ?? ''));
$data = $input['data'] ?? [];
$removedInstanceIds = (array)($input['removedInstanceIds'] ?? []);

if ($page === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'missing page']);
  exit;
}

/* === Gültige Positionen === */
$validPositions = NX_ZONES;

/* === Widgets Metadaten laden === */
$titles = [];
$modMap = [];
$res = $_database->query("
  SELECT widget_key, COALESCE(NULLIF(title,''), widget_key) AS t, plugin
  FROM settings_widgets
");
if ($res) {
  while ($r = $res->fetch_assoc()) {
    $titles[$r['widget_key']] = $r['t'];
    $modMap[$r['widget_key']] = $r['plugin'];
  }
  $res->free();
}

/* === Spalte modulname? === */
$has_modulname = false;
$res = $_database->query("SHOW COLUMNS FROM `settings_widgets_positions` LIKE 'modulname'");
if ($res && $res->num_rows > 0) $has_modulname = true;

/* === DB Transaktion === */
try {
  $_database->begin_transaction();

  /* === 1. Einzelne Widgets löschen === */
  if (!empty($removedInstanceIds)) {
    $ph = implode(',', array_fill(0, count($removedInstanceIds), '?'));
    $sql = "DELETE FROM settings_widgets_positions WHERE page=? AND instance_id IN ($ph)";
    $stmt = $_database->prepare($sql);
    if (!$stmt) throw new RuntimeException($_database->error);
    $types = 's' . str_repeat('s', count($removedInstanceIds));
    $params = array_merge([$page], $removedInstanceIds);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
  }

  /* === 2. Speichern / Upsert === */
  if (!empty($data)) {
    if ($has_modulname) {
      $sql = "
        INSERT INTO settings_widgets_positions
          (page, position, sort_order, widget_key, instance_id, settings, title, modulname)
        VALUES (?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
          sort_order=VALUES(sort_order),
          settings=VALUES(settings),
          title=VALUES(title),
          modulname=VALUES(modulname)
      ";
      $stmt = $_database->prepare($sql);
      if (!$stmt) throw new RuntimeException($_database->error);

      foreach ($data as $pos => $items) {
        if (!in_array($pos, $validPositions, true) || !is_array($items)) continue;
        $sort = 0;
        foreach ($items as $it) {
          $widget_key = (string)($it['widget_key'] ?? '');
          $instance_id = (string)($it['instance_id'] ?? '');
          $settings = $it['settings'] ?? [];
          if ($widget_key === '' || $instance_id === '') continue;
          $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
          $title = $titles[$widget_key] ?? $widget_key;
          $modulname = $modMap[$widget_key] ?? '';

          // alte Position entfernen (sicherer Upsert)
          $del = $_database->prepare("DELETE FROM settings_widgets_positions WHERE page=? AND instance_id=?");
          $del->bind_param('ss', $page, $instance_id);
          $del->execute();
          $del->close();

          $stmt->bind_param('ssisssss', $page, $pos, $sort, $widget_key, $instance_id, $settingsJson, $title, $modulname);
          $stmt->execute();
          $sort++;
        }
      }
      $stmt->close();
    } else {
      $sql = "
        INSERT INTO settings_widgets_positions
          (page, position, sort_order, widget_key, instance_id, settings, title)
        VALUES (?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
          sort_order=VALUES(sort_order),
          settings=VALUES(settings),
          title=VALUES(title)
      ";
      $stmt = $_database->prepare($sql);
      if (!$stmt) throw new RuntimeException($_database->error);

      foreach ($data as $pos => $items) {
        if (!in_array($pos, $validPositions, true) || !is_array($items)) continue;
        $sort = 0;
        foreach ($items as $it) {
          $widget_key = (string)($it['widget_key'] ?? '');
          $instance_id = (string)($it['instance_id'] ?? '');
          $settings = $it['settings'] ?? [];
          if ($widget_key === '' || $instance_id === '') continue;
          $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
          $title = $titles[$widget_key] ?? $widget_key;

          $del = $_database->prepare("DELETE FROM settings_widgets_positions WHERE page=? AND instance_id=?");
          $del->bind_param('ss', $page, $instance_id);
          $del->execute();
          $del->close();

          $stmt->bind_param('ssissss', $page, $pos, $sort, $widget_key, $instance_id, $settingsJson, $title);
          $stmt->execute();
          $sort++;
        }
      }
      $stmt->close();
    }
  }

  $_database->commit();
  echo json_encode(['ok'=>true]);

} catch (Throwable $e) {
  $_database->rollback();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
