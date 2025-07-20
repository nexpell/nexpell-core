<?php

use nexpell\LanguageService;

// Session absichern
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Standard setzen, wenn nicht vorhanden
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

// Initialisieren
global $languageService;
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('admin_log', true);

use nexpell\AccessControl;
// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_admin_log');

$action_labels = [
    1 => 'Erstellen',
    2 => 'Bearbeiten',
    3 => 'Löschen',
    4 => 'Login',
    5 => 'Logout',
    // Weitere Aktionscodes ggf. ergänzen
];

$entries_per_page = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $entries_per_page;

// Suchfilter
$where = [];

if (!empty($_GET['admin'])) {
    $admin = $_database->real_escape_string($_GET['admin']);
    $where[] = "u.username LIKE '%$admin%'";
}
if (!empty($_GET['action'])) {
    $action_search = strtolower(trim($_GET['action']));
    $matched_keys = array_keys(array_filter($action_labels, function ($label) use ($action_search) {
        return stripos($label, $action_search) !== false;
    }));

    if (!empty($matched_keys)) {
        $escaped_keys = implode(',', array_map('intval', $matched_keys));
        $where[] = "log.action IN ($escaped_keys)";
    } else {
        $where[] = "0"; // keine passenden Aktionen gefunden
    }
}
if (!empty($_GET['module'])) {
    $module = $_database->real_escape_string($_GET['module']);
    $where[] = "log.module LIKE '%$module%'";
}
if (!empty($_GET['table'])) {
    $table = $_database->real_escape_string($_GET['table']);
    $where[] = "log.affected_table LIKE '%$table%'";
}
if (!empty($_GET['ip'])) {
    $ip = $_database->real_escape_string($_GET['ip']);
    $where[] = "log.ip_address LIKE '%$ip%'";
}

$where_sql = '';
if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// Gesamtanzahl berechnen
$total_result = $_database->query("
    SELECT COUNT(*) AS total
    FROM admin_audit_log log
    JOIN users u ON log.adminID = u.userID
    $where_sql
");
$total_row = $total_result->fetch_assoc();
$total_entries = $total_row['total'];
$total_pages = ceil($total_entries / $entries_per_page);

// Datenabfrage mit Limit
$result = $_database->query("
    SELECT log.*, u.username
    FROM admin_audit_log log
    JOIN users u ON log.adminID = u.userID
    $where_sql
    ORDER BY timestamp DESC
    LIMIT $entries_per_page OFFSET $offset
");

echo '
<div class="card">
    <div class="card-header">
        <i class="bi bi-paragraph"></i> ' . $languageService->get('admin_log') . '
    </div>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb t-5 p-2 bg-light">
            <li class="breadcrumb-item active">' . $languageService->get('admin_log') . '</li>
        </ol>
    </nav>

    <div class="card-body">
        <div class="container py-5">

<form method="get" class="mb-4">
    <div class="row g-2">
        <div class="col-md-2">
            <input type="text" name="admin" class="form-control" placeholder="Admin" value="' . htmlspecialchars($_GET['admin'] ?? '') . '">
        </div>
        <div class="col-md-2">
            <input type="text" name="action" class="form-control" placeholder="Aktion" value="' . htmlspecialchars($_GET['action'] ?? '') . '">
        </div>
        <div class="col-md-2">
            <input type="text" name="module" class="form-control" placeholder="Modul" value="' . htmlspecialchars($_GET['module'] ?? '') . '">
        </div>
        <div class="col-md-2">
            <input type="text" name="table" class="form-control" placeholder="Tabelle" value="' . htmlspecialchars($_GET['table'] ?? '') . '">
        </div>
        <div class="col-md-2">
            <input type="text" name="ip" class="form-control" placeholder="IP-Adresse" value="' . htmlspecialchars($_GET['ip'] ?? '') . '">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Suchen</button>
        </div>
    </div>
</form>';

while ($row = $result->fetch_assoc()) {
    $old_data = !empty($row['old_value']) ? json_decode($row['old_value'], true) : [];
    $new_data = !empty($row['new_value']) ? json_decode($row['new_value'], true) : [];
    $action_label = $action_labels[(int)$row['action']] ?? 'Unbekannt';
    


    echo '
    <table class="table table-bordered table-striped bg-white shadow-sm mb-4">
        <thead class="table-light">
            <tr>
                <th>' . ($languageService->get('property') ?? 'Eigenschaft') . '</th>
                <th>' . ($languageService->get('value') ?? 'Wert') . '</th>
            </tr>
        </thead>
        <tbody>
            <tr><td style="width: 15%;"><strong>Datum:</strong></td><td>' . htmlspecialchars($row['timestamp']) . '</td></tr>
            <tr><td><strong>Admin:</strong></td><td>' . htmlspecialchars($row['username']) . '</td></tr>
            <tr><td><strong>Aktion:</strong></td><td>' . htmlspecialchars($action_label) . '</td></tr>
            <tr><td><strong>Modul:</strong></td><td>' . htmlspecialchars($row['module']) . '</td></tr>
            <tr><td><strong>Tabelle:</strong></td><td>' . htmlspecialchars($row['affected_table']) . '</td></tr>
            <tr><td><strong>ID:</strong></td><td>' . htmlspecialchars($row['affected_id'] ?? '') . '</td></tr>
            <tr>
                <td><strong>Alte Daten:</strong></td>
                <td><pre style="white-space: pre-wrap; word-break: break-word; max-height: 300px; overflow: auto;">' . 
                    (!empty($old_data) 
                        ? htmlspecialchars(json_encode($old_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) 
                        : 'Keine alten Daten') . 
                '</pre></td>
            </tr>
            <tr>
                <td><strong>Neue Daten:</strong></td>
                <td><pre style="white-space: pre-wrap; word-break: break-word; max-height: 300px; overflow: auto;">' . 
                    (!empty($new_data) 
                        ? htmlspecialchars(json_encode($new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) 
                        : 'Keine neuen Daten') . 
                '</pre></td>
            </tr>
            <tr><td><strong>IP:</strong></td><td>' . htmlspecialchars($row['ip_address']) . '</td></tr>
        </tbody>
    </table>';
}

// Pagination
echo '<nav><ul class="pagination justify-content-center">';
for ($i = 1; $i <= $total_pages; $i++) {
    $active = $i == $page ? ' active' : '';
    $query = $_GET;
    $query['page'] = $i;
    $url = '?' . http_build_query($query);
    echo '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
}
echo '</ul></nav>';

echo '</div></div></div>';
?>
