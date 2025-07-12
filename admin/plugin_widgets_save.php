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

function escape($string) {
    global $_database;
    return $_database->real_escape_string($string);
}

function safe_query($sql) {
    global $_database;
    return $_database->query($sql);
}

// ---------- Laden ----------
if (isset($_GET['mode']) && $_GET['mode'] === 'load') {
    $page = isset($_GET['page']) ? escape($_GET['page']) : '';

    // Alle Widgets laden
    $allWidgetsRes = safe_query("SELECT widget_key, title FROM settings_widgets");
    $allWidgets = [];
    while ($row = $allWidgetsRes->fetch_assoc()) {
        $allWidgets[$row['widget_key']] = $row;
    }

    // Zugewiesene Widgets für Seite laden
    $assignedRes = safe_query(
        "SELECT wp.widget_key, wp.position, wp.sort_order, w.title
         FROM settings_widgets_positions wp
         JOIN settings_widgets w ON w.widget_key = wp.widget_key
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

    // Nicht zugewiesene Widgets ermitteln
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
safe_query("DELETE FROM settings_widgets_positions WHERE page='$page'");

// Modulnamen zwischenspeichern (alle)
$modulMap = [];
$res = safe_query("SELECT widget_key, modulname FROM settings_widgets");
while ($row = $res->fetch_assoc()) {
    $modulMap[$row['widget_key']] = $row['modulname'];
}

foreach (['top', 'undertop', 'left', 'maintop', 'mainbottom', 'right', 'bottom'] as $pos) {
    if (!empty($data['data'][$pos]) && is_array($data['data'][$pos])) {
        $order = 1;
        foreach ($data['data'][$pos] as $wkey) {
            $wkeyEsc = escape($wkey);
            $modulname = isset($modulMap[$wkey]) ? escape($modulMap[$wkey]) : '';
            safe_query(
                "INSERT INTO settings_widgets_positions (widget_key, position, page, sort_order, modulname)
                 VALUES ('$wkeyEsc', '$pos', '$page', $order, '$modulname')"
            );
            $order++;
        }
    }
}

echo "Gespeichert.";
