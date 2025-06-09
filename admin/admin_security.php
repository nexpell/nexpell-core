<?php

// Überprüfen, ob die Session bereits gestartet wurde
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use webspell\LoginSecurity;
use webspell\AccessControl;
// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_security');

echo'<h2>Registrierungs- und Login-Aktivitäten</h2>';

echo '<div class="card"><div class="card-body"><div class="container py-5">';
echo '<h4>Registrierungsversuche (Erfolgreich & Fehlgeschlagen)</h4>';

// Pagination-Einstellungen
$limit = 10;
$page = isset($_GET['regpage']) ? (int)$_GET['regpage'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Gesamtanzahl der Versuche zählen
$countResult = safe_query("SELECT COUNT(*) AS total FROM user_register_attempts");
$countRow = mysqli_fetch_array($countResult);
$totalAttempts = (int)$countRow['total'];
$totalPages = ceil($totalAttempts / $limit);

// Versuche abrufen
$query = safe_query("SELECT * FROM user_register_attempts ORDER BY attempt_time DESC LIMIT $limit OFFSET $offset");

echo '<table class="table table-bordered table-striped bg-white shadow-sm">
        <thead class="table-light">
            <tr>
                <th scope="col">#</th>
                <th>Username</th>
                <th>Email</th>
                <th scope="col">IP-Adresse</th>
                <th scope="col">Zeitpunkt</th>
                <th scope="col">Status</th>
                <th scope="col">Grund</th>
            </tr>
        </thead>
        <tbody>';

while ($row = mysqli_fetch_array($query)) {
    $status_badge = $row['status'] === 'success'
        ? '<span class="badge bg-success">Erfolg</span>'
        : '<span class="badge bg-danger">Fehlgeschlagen</span>';

    echo '<tr>
            <td>' . (int)$row['id'] . '</td>
            <td>' . htmlspecialchars($row['username']) . '</td>
            <td>' . htmlspecialchars($row['email']) . '</td>
            <td>' . htmlspecialchars($row['ip_address']) . '</td>
            <td>' . date("d.m.Y H:i", strtotime($row['attempt_time'])) . '</td>
            <td>' . $status_badge . '</td>
            <td>' . htmlspecialchars($row['reason'] ?? '-') . '</td>
        </tr>';
}

echo '</tbody></table>';

// Pagination Links
if ($totalPages > 1) {
    echo '<nav class="mt-3"><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($i == $page) ? 'active' : '';
        echo '<li class="page-item ' . $activeClass . '">
                  <a class="page-link" href="?site=admin_security&regpage=' . $i . '">' . $i . '</a>
              </li>';
    }
    echo '</ul></nav>';
}

echo '</div></div></div>';
echo '<div class="card"><div class="card-body"><div class="container py-5">';
echo '<h4>Benutzer</h4>';

// Pagination-Einstellungen
$limit = 10;
$page = isset($_GET['userpage']) ? (int)$_GET['userpage'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Gesamtanzahl Benutzer zählen
$countResult = $_database->query("SELECT COUNT(*) AS total FROM users");
$countRow = $countResult->fetch_assoc();
$totalUsers = (int)$countRow['total'];
$totalPages = ceil($totalUsers / $limit);

// Benutzer abrufen
$get = $_database->query("
    SELECT userID, username, email, is_active, registerdate
    FROM users
    ORDER BY registerdate DESC
    LIMIT $limit OFFSET $offset
");

echo '<table class="table table-bordered table-striped bg-white shadow-sm">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Aktiviert</th>
                <th>Registriert</th>
            </tr>
        </thead>';
echo '<tbody>';

while ($ds = $get->fetch_assoc()) {
    echo '<tr>
        <td>' . (int)$ds['userID'] . '</td>
        <td>' . htmlspecialchars($ds['username']) . '</td>
        <td>' . htmlspecialchars($ds['email']) . '</td>
        <td>' . ($ds['is_active'] ? '✔️' : '❌') . '</td>
        <td>' . $ds['registerdate'] . '</td>
    </tr>';
}

echo '</tbody></table>';

// Pagination Links
if ($totalPages > 1) {
    echo '<nav class="mt-3"><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($i == $page) ? 'active' : '';
        echo '<li class="page-item ' . $activeClass . '">
                  <a class="page-link" href="?site=admin_security&userpage=' . $i . '">' . $i . '</a>
              </li>';
    }
    echo '</ul></nav>';
}

echo '</div></div></div>';


// ------------------------------------

if (isset($_POST['session_id'])) {
    $sessionID = $_POST['session_id'];

    $deleteQuery = $_database->prepare("DELETE FROM user_sessions WHERE session_id = ?");
    $deleteQuery->bind_param('s', $sessionID);
    $deleteQuery->execute();

    header("Location: /admin/admincenter.php?site=admin_security&deleted=true");
    exit;
}

// Erfolgsmeldung (wird nur bei vollem Seitenaufruf angezeigt)
if (isset($_GET['deleted'])) {
    echo '<div class="alert alert-success">Session wurde erfolgreich gelöscht.</div>';
}

// Pagination-Einstellungen
$limit = 10; // Maximal 10 Sessions pro Seite
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Gesamtanzahl der Sessions
$countResult = $_database->query("SELECT COUNT(*) AS total FROM user_sessions");
$countRow = $countResult->fetch_assoc();
$totalSessions = (int)$countRow['total'];
$totalPages = ceil($totalSessions / $limit);

// Sessions abrufen
$getSessions = $_database->query("
    SELECT s.session_id, s.userID, u.username, s.user_ip, s.session_data, s.browser, s.last_activity
    FROM user_sessions s
    LEFT JOIN users u ON s.userID = u.userID
    ORDER BY s.last_activity DESC
    LIMIT $limit OFFSET $offset
");


?>

<div class="card mt-4">
    <div class="card-body"><div class="container py-5">
        <h4>Aktive Sessions</h4>
        <div id="session-table-container">
            <table class="table table-bordered table-striped bg-white shadow-sm">
                <thead class="table-light">
                    <tr>
                        <th>Session ID</th>
                        <th>Username</th>
                        <th>IP</th>
                        <th>Letzte Aktion</th>
                        <th>Browser</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                while ($ds = $getSessions->fetch_assoc()) {
                    $username = isset($ds['username']) && !empty($ds['username']) ? $ds['username'] : 'Unbekannt';
                    $lastActivityTimestamp = (int)$ds['last_activity'];
                    if ($lastActivityTimestamp == 0) {
                        $lastActivityTimestamp = time();
                    }
                    $sessionTime = date("d.m.Y H:i", $lastActivityTimestamp);

                    echo '<tr>
                        <td>' . htmlspecialchars($ds['session_id']) . '</td>
                        <td>' . htmlspecialchars($username) . '</td>
                        <td>' . htmlspecialchars($ds['user_ip']) . '</td>
                        <td>' . $sessionTime . '</td>
                        <td>' . htmlspecialchars(substr($ds['browser'], 0, 40)) . '...</td>
                        <td>
                            <form method="POST" action="" onsubmit="return confirm(\'Session wirklich löschen?\');" class="d-inline">
                                <input type="hidden" name="session_id" value="' . htmlspecialchars($ds['session_id']) . '">
                                <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                            </form>
                        </td>
                    </tr>';
                }
                ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1) : ?>
            <nav>
                <ul id="pagination-container" class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++) :
                        $activeClass = ($i == $page) ? 'active' : '';
                    ?>
                        <li class="page-item <?= $activeClass ?>">
                            <a class="page-link" href="javascript:void(0);" onclick="loadPage(<?= $i ?>)"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div></div>

<script>
// AJAX-Funktion für das Nachladen der Sessions
function loadPage(page) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', '/admin/admincenter.php?site=admin_security&page=' + page + '&ajax=1', true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            // Antwort erwartet nur neuen Tabelleninhalt + Pagination
            var response = JSON.parse(xhr.responseText);

            document.getElementById('session-table-container').innerHTML = response.table;
            document.getElementById('pagination-container').innerHTML = response.pagination;
        }
    };
    xhr.send();
}
</script>

<?php
// AJAX-Anfrage erkennen und nur reinen Inhalt liefern
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ob_clean(); // Ausgabe-Puffer leeren für saubere Antwort

    $tableHTML = '<table class="table table-bordered table-striped bg-white shadow-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Session ID</th>
                            <th>Username</th>
                            <th>IP</th>
                            <th>Letzte Aktion</th>
                            <th>Browser</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>';

    $getSessions = $_database->query("
        SELECT s.session_id, s.userID, u.username, s.user_ip, s.session_data, s.browser, s.last_activity
        FROM user_sessions s
        LEFT JOIN users u ON s.userID = u.userID
        ORDER BY s.last_activity DESC
        LIMIT $limit OFFSET $offset
    ");

    while ($ds = $getSessions->fetch_assoc()) {
        $username = isset($ds['username']) && !empty($ds['username']) ? $ds['username'] : 'Unbekannt';
        $lastActivityTimestamp = (int)$ds['last_activity'];
        if ($lastActivityTimestamp == 0) {
            $lastActivityTimestamp = time();
        }
        $sessionTime = date("d.m.Y H:i", $lastActivityTimestamp);

        $tableHTML .= '<tr>
            <td>' . htmlspecialchars($ds['session_id']) . '</td>
            <td>' . htmlspecialchars($username) . '</td>
            <td>' . htmlspecialchars($ds['user_ip']) . '</td>
            <td>' . $sessionTime . '</td>
            <td>' . htmlspecialchars(substr($ds['browser'], 0, 40)) . '...</td>
            <td>
                <form method="POST" action="" onsubmit="return confirm(\'Session wirklich löschen?\');" class="d-inline">
                    <input type="hidden" name="session_id" value="' . htmlspecialchars($ds['session_id']) . '">
                    <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                </form>
            </td>
        </tr>';
    }

    $tableHTML .= '</tbody></table>';

    // Pagination neu bauen
    $paginationHTML = '';
    if ($totalPages > 1) {
        $paginationHTML = '<ul class="pagination justify-content-center">';
        for ($i = 1; $i <= $totalPages; $i++) {
            $activeClass = ($i == $page) ? 'active' : '';
            $paginationHTML .= '<li class="page-item ' . $activeClass . '">
                                    <a class="page-link" href="javascript:void(0);" onclick="loadPage(' . $i . ')">' . $i . '</a>
                                </li>';
        }
        $paginationHTML .= '</ul>';
    }

    echo json_encode([
        'table' => $tableHTML,
        'pagination' => $paginationHTML
    ]);
    exit;
}




// AJAX-Handler für IP-Sperren
if (isset($_POST['ban_ip']) && filter_var($_POST['ban_ip'], FILTER_VALIDATE_IP)) {
    $ipToBan = $_POST['ban_ip'];

    // prüfen, ob IP bereits gesperrt ist
    $check = $_database->prepare("SELECT banID FROM banned_ips WHERE ip = ?");
    $check->bind_param('s', $ipToBan);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows == 0) {
        // IP eintragen
        $stmt = $_database->prepare("
            INSERT INTO banned_ips (ip, deltime, reason, userID, email)
            VALUES (?, NOW() + INTERVAL 7 DAY, 'Automatische Sperre nach fehlgeschlagenen Logins', 0, '')
        ");
        $stmt->bind_param('s', $ipToBan);
        $stmt->execute();
    }

    echo 'OK';
    exit;
}

// Fehlgeschlagene Login-Versuche (letzte 15 Minuten) - Anzeige
echo '<div class="card mt-4"><div class="card-body"><div class="container py-5">';
echo '<h4>Fehlgeschlagene Login-Versuche (letzte 15 Minuten)</h4>';

// Pagination-Einstellungen
$limit = 10;
$page = isset($_GET['failpage']) ? (int)$_GET['failpage'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Gesamtanzahl an gruppierten IPs holen
$countResult = $_database->query("
    SELECT COUNT(*) AS total
    FROM (
        SELECT ip
        FROM failed_login_attempts
        WHERE attempt_time > NOW() - INTERVAL 15 MINUTE
        GROUP BY ip
    ) AS grouped
");
$countRow = $countResult->fetch_assoc();
$totalIps = (int)$countRow['total'];
$totalPages = ceil($totalIps / $limit);

// IPs abrufen mit LIMIT und OFFSET
$get = $_database->query("
    SELECT ip, COUNT(*) AS attempts, MAX(UNIX_TIMESTAMP(attempt_time)) AS last_attempt
    FROM failed_login_attempts
    WHERE attempt_time > NOW() - INTERVAL 15 MINUTE
    GROUP BY ip
    ORDER BY attempts DESC
    LIMIT $limit OFFSET $offset
");

echo '<table class="table table-bordered table-striped bg-white shadow-sm">
        <thead class="table-light">
            <tr><th>IP-Adresse</th><th>Versuche</th><th>Letzter Versuch</th><th>Aktion</th></tr></thead><tbody>';

while ($ds = $get->fetch_assoc()) {
    echo '<tr>
            <td>' . htmlspecialchars($ds['ip']) . '</td>
            <td>' . (int)$ds['attempts'] . '</td>
            <td>' . date("d.m.Y H:i:s", $ds['last_attempt']) . '</td>
            <td><button class="btn btn-sm btn-danger ban-ip-btn" data-ip="' . htmlspecialchars($ds['ip']) . '">Sperren</button></td>
          </tr>';
}

echo '</tbody></table>';

// Pagination Links
if ($totalPages > 1) {
    echo '<nav class="mt-3"><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($i == $page) ? 'active' : '';
        echo '<li class="page-item ' . $activeClass . '">
                  <a class="page-link" href="?site=admin_security&failpage=' . $i . '">' . $i . '</a>
              </li>';
    }
    echo '</ul></nav>';
}

echo '</div></div></div>';

// JavaScript für AJAX-Handling (Sperren-Button)
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const banButtons = document.querySelectorAll('.ban-ip-btn');
    banButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const ip = this.getAttribute('data-ip');
            if (confirm('Willst du die IP ' + ip + ' wirklich sperren?')) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ban_ip=' + encodeURIComponent(ip)
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === 'OK') {
                        alert('IP wurde gesperrt.');
                        location.reload();
                    } else {
                        alert('Fehler beim Sperren der IP.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Netzwerkfehler.');
                });
            }
        });
    });
});
</script>

<?php

echo '<div class="card mt-4"><div class="card-body"><div class="container py-5">';
echo '<h4>Gesperrte IPs</h4>';

// Pagination-Einstellungen für gebannte IPs
$limit = 10;
$page = isset($_GET['banpage']) ? (int)$_GET['banpage'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $limit;

// Gesamtanzahl gesperrter IPs holen
$countResult = $_database->query("SELECT COUNT(*) AS total FROM banned_ips");
$countRow = $countResult->fetch_assoc();
$totalIps = (int)$countRow['total'];
$totalPages = ceil($totalIps / $limit);

// IPs abrufen mit LIMIT und OFFSET
$query = "
    SELECT 
        b.ip, 
        b.deltime, 
        b.reason,
        b.email, 
        u.username, 
        r.role_name AS role_name
    FROM banned_ips b
    LEFT JOIN users u ON b.userID = u.userID
    LEFT JOIN user_role_assignments ura ON u.userID = ura.userID
    LEFT JOIN user_roles r ON ura.roleID = r.roleID
    ORDER BY b.deltime DESC
    LIMIT $limit OFFSET $offset
";

$get = $_database->query($query);

echo '<table class="table table-bordered table-striped bg-white shadow-sm">
        <thead class="table-light">
            <tr>
        <th>IP</th>
        <th>Benutzername</th>
        <th>Email</th>
        <th>Rolle</th>
        <th>Entbannzeit</th>
        <th>Grund</th>
        <th>Aktion</th>
    </tr></thead><tbody>';

while ($ds = $get->fetch_assoc()) {
    echo '<tr>
        <td>' . htmlspecialchars($ds['ip']) . '</td>
        <td>' . (!empty($ds['username']) ? htmlspecialchars($ds['username']) : '<em>Unbekannt</em>') . '</td>
        <td>' . (!empty($ds['email']) ? htmlspecialchars($ds['email']) : '<em>Unbekannt</em>') . '</td>
        <td>' . (isset($ds['role_name']) ? htmlspecialchars($ds['role_name']) : '<em>Keine</em>') . '</td>
        <td>' . date("d.m.Y H:i", strtotime($ds['deltime'])) . '</td>
        <td>' . htmlspecialchars($ds['reason']) . '</td>
        <td>
            <form method="post" onsubmit="return confirm(\'IP wirklich löschen?\');" style="display:inline;">
                <input type="hidden" name="delete_ip" value="' . htmlspecialchars($ds['ip']) . '">
                <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
            </form>
        </td>
    </tr>';
}
echo '</tbody></table>';

// Pagination Links für gebannte IPs
if ($totalPages > 1) {
    echo '<nav class="mt-3"><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($i == $page) ? 'active' : '';
        echo '<li class="page-item ' . $activeClass . '">
                  <a class="page-link" href="?site=admin_security&banpage=' . $i . '">' . $i . '</a>
              </li>';
    }
    echo '</ul></nav>';
}
echo '</div></div></div>';
