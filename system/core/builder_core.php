<?php
// === /includes/builder_core.php ===
// Gemeinsame Core-Funktionen für Live- und Preview-Builder
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

/* ===========================================================
   SECURITY / INIT
   =========================================================== */
if (!defined('BASE_PATH')) {
  define('BASE_PATH', dirname(__DIR__, 2)); // zwei Ebenen hoch: /system/core → /
}
require_once BASE_PATH . '/system/config.inc.php';

// --- DB Connection ---
global $_database;
/** @var mysqli|null $_database */
$_database = $_database ?? new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($_database->connect_errno) {
  http_response_code(500);
  die('DB connection failed: ' . $_database->connect_error);
}
$_database->set_charset('utf8mb4');

// --- CSRF Token ---
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf_token'];

/* ===========================================================
   KONSTANTEN / GRUNDWERTE
   =========================================================== */

// Definiert die gültigen Widget-Zonen
if (!defined('NX_ZONES')) {
  define('NX_ZONES', ['top','undertop','left','maintop','mainbottom','right','bottom']);
}

/* ===========================================================
   HELPER-FUNKTIONEN
   =========================================================== */

/**
 * Sicherheit: SQL-Escape Wrapper
 */
function nx_escape(string $value): string {
  global $_database;
  return $_database->real_escape_string($value);
}

/**
 * Lädt alle registrierten Widgets aus der Tabelle settings_widgets.
 * Gibt ein Array mit widget_key, title, plugin, allowed_zones usw. zurück.
 */
function nx_load_available_widgets(): array {
  global $_database;
  $out = [];
  if (!$_database) return $out;

  $res = $_database->query("
    SELECT widget_key, COALESCE(NULLIF(title,''), widget_key) AS title,
           plugin, allowed_zones
    FROM settings_widgets
    ORDER BY title ASC
  ");
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $out[] = [
        'widget_key'   => $row['widget_key'],
        'title'        => $row['title'],
        'plugin'       => $row['plugin'],
        'allowed_zones'=> $row['allowed_zones'] ?? '',
      ];
    }
    $res->free();
  }
  return $out;
}

/**
 * Lädt alle gespeicherten Widget-Instanzen einer Seite.
 * Gibt ein Array [position => [widgets...]] zurück.
 */

function nx_load_widgets_for_page(string $page): array {
    global $_database;

    $out = [];
    if (!$_database || $_database->connect_errno) return $out;

    $stmt = $_database->prepare("
        SELECT 
            p.position,
            p.widget_key,
            p.instance_id,
            p.settings,
            w.title,
            w.allowed_zones
        FROM settings_widgets_positions AS p
        LEFT JOIN settings_widgets AS w ON w.widget_key = p.widget_key
        WHERE p.page = ?
        ORDER BY p.position ASC, p.sort_order ASC
    ");
    if (!$stmt) return $out;

    $stmt->bind_param('s', $page);
    if (!$stmt->execute()) {
        $stmt->close();
        return $out;
    }

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $pos = $row['position'];
        if (!isset($out[$pos])) $out[$pos] = [];

        $settings = json_decode($row['settings'] ?? '{}', true);
        if (!is_array($settings)) $settings = [];

        $out[$pos][] = [
            'widget_key'     => $row['widget_key'],
            'instance_id'    => $row['instance_id'],
            'title'          => $row['title'] ?? $row['widget_key'],
            'settings'       => $settings,
            'allowed_zones'  => $row['allowed_zones'] ?? ''
        ];
    }

    $stmt->close();
    return $out;
}


/**
 * Gibt ein Widget-HTML aus (optional für spätere Integration)
 */
function nx_render_widget(array $widget): string {
  $title = htmlspecialchars($widget['title'] ?? 'Untitled');
  return "<div class='nx-live-item border p-2 mb-1 rounded bg-light'>{$title}</div>";
}

/**
 * Einfacher Log-Helper (optional)
 */
function nx_log(string $msg): void {
  // file_put_contents(BASE_PATH.'/logs/builder.log', '['.date('c').'] '.$msg.PHP_EOL, FILE_APPEND);
}
