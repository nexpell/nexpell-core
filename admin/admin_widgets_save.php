<?php
// Konfigurationsdatei laden
$configPath = __DIR__ . '/../system/config.inc.php';
if (!file_exists($configPath)) {
    die("Fehler: Konfigurationsdatei nicht gefunden.");
}
require_once $configPath;

// Datenbankverbindung
$_database = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($_database->connect_error) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . $_database->connect_error);
}

// Escape-Helfer
function escape($string) {
    global $_database;
    return $_database->real_escape_string($string);
}

// Safe-Query-Helfer
function safe_query($sql) {
    global $_database;
    return $_database->query($sql);
}

// ---------- Laden ----------
if (isset($_GET['mode']) && $_GET['mode'] === 'load') {
    $page = isset($_GET['page']) ? escape($_GET['page']) : '';

    // 1. Alle Widgets aus Datenbank holen
    $allWidgetsRes = safe_query("SELECT widget_key, title FROM widgets");
    $allWidgets = [];
    while ($row = $allWidgetsRes->fetch_assoc()) {
        $allWidgets[$row['widget_key']] = $row;
    }

    // 2. Zugewiesene Widgets für Seite laden
    $assignedRes = safe_query(
        "SELECT wp.widget_key, wp.position, wp.sort_order, w.title
         FROM widgets_positions wp
         JOIN widgets w ON w.widget_key = wp.widget_key
         WHERE wp.page = '$page'
         ORDER BY wp.position, wp.sort_order ASC"
    );

    $assigned = [];
    $assignedKeys = [];

    while ($row = $assignedRes->fetch_assoc()) {
        $assigned[] = [
            'widget_key' => $row['widget_key'],
            'title' => $row['title'],
            'position' => $row['position']
        ];
        $assignedKeys[] = $row['widget_key'];
    }

    // 3. Nicht zugewiesene Widgets ermitteln = available
    $available = [];
    foreach ($allWidgets as $key => $widget) {
        if (!in_array($key, $assignedKeys, true)) {
            $available[] = [
                'widget_key' => $key,
                'title' => $widget['title']
            ];
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'assigned' => $assigned,
        'available' => $available
    ]);
    exit;
}

// ---------- Speichern ----------
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['page'], $data['data'])) {
    http_response_code(400);
    echo "Ungültige Daten";
    exit;
}

$page = escape($data['page']);

// Alte Einträge löschen (für diese Seite)
safe_query("DELETE FROM widgets_positions WHERE page='$page'");

// Neu speichern für Positionen left, right, top, bottom
foreach (['left', 'right', 'top', 'undertop', 'bottom'] as $pos) {
    if (!empty($data['data'][$pos]) && is_array($data['data'][$pos])) {
        $order = 1;
        foreach ($data['data'][$pos] as $wkey) {
            $wkeyEsc = escape($wkey);
            safe_query(
                "INSERT INTO widgets_positions (widget_key, position, page, sort_order)
                 VALUES ('$wkeyEsc', '$pos', '$page', $order)"
            );
            $order++;
        }
    }
}

echo "Gespeichert.";
