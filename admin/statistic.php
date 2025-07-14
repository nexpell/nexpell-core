<?php

use webspell\LanguageService;

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
$languageService->readModule('user_roles', true);

// Überprüfen, ob die Session bereits gestartet wurde
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use webspell\AccessControl;
// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_statistic');



// Datenbankverbindung sicherstellen
#include 'db_connection.php'; // Beispiel für die Datenbankverbindung

// Holen der Anzahl der Benutzer im letzten Monat
/*$result = safe_query("SELECT COUNT(*) AS user_count FROM users WHERE created_at >= NOW() - INTERVAL 1 MONTH");
$row = mysqli_fetch_assoc($result);
$user_count_last_month = $row['user_count'];

// Holen der Benutzerregistrierungen nach Monat
$monthly_users = safe_query("SELECT YEAR(created_at) AS year, MONTH(created_at) AS month, COUNT(*) AS user_count FROM user GROUP BY year, month ORDER BY year DESC, month DESC");

// Holen der Benutzerrollen und -rechte
$userID = $_SESSION['userID'] ?? 0;

// Rolle des Benutzers ermitteln
$role_query = "SELECT roleID FROM user_role_assignments WHERE userID = ?";
$role_stmt = $_database->prepare($role_query);
$role_stmt->bind_param("i", $userID);
$role_stmt->execute();
$role_result = $role_stmt->get_result();
$roles = [];
while ($role_row = mysqli_fetch_assoc($role_result)) {
    $roles[] = $role_row['roleID'];
}
$role_stmt->close();

// Benutzerrechte ermitteln (z.B. für das Dashboard)
$access_query = "SELECT modulname, roleID FROM user_admin_access_rights WHERE roleID = ?";
$access_stmt = $_database->prepare($access_query);
$access_stmt->bind_param("i", $userID);
$access_stmt->execute();
$access_result = $access_stmt->get_result();
$access_rights = [];
while ($access_row = mysqli_fetch_assoc($access_result)) {
    $access_rights[$access_row['modulname']] = $access_row['roleID'];
}
$access_stmt->close();

// Ausgabe der Statistik
echo "<h2 class='my-4'>Benutzerstatistiken</h2>";
echo "<p>Benutzer im letzten Monat: <strong>" . $user_count_last_month . "</strong></p>";

// Anzeigen der Registrierungen pro Monat
echo "<h3 class='my-3'>Registrierungen nach Monat</h3>";
echo "<table class='table table-striped'>";
echo "<thead><tr><th>Jahr</th><th>Monat</th><th>Registrierungen</th></tr></thead>";
echo "<tbody>";
while ($row = mysqli_fetch_assoc($monthly_users)) {
    echo "<tr><td>" . $row['year'] . "</td><td>" . $row['month'] . "</td><td>" . $row['user_count'] . "</td></tr>";
}
echo "</tbody></table>";

// Ausgabe der Benutzerrechte und -rollen
#echo "<h3 class='my-3'>Benutzerrollen und -rechte</h3>";
#echo "<p>Benutzer hat die folgenden Rollen:</p>";
#echo "<ul>";
#foreach ($roles as $role) {
#    echo "<li>Rolle ID: " . $role . "</li>";
#}
#echo "</ul>";

#echo "<p>Benutzer hat folgende Rechte:</p>";
#echo "<ul>";
#foreach ($access_rights as $modulname => $roleID) {
#    echo "<li>Modul: " . $modulname . " - Recht: " . $roleID . "</li>";
#}
#echo "</ul>";


#require_once("../system/sql_connect.php");
/*

require_once("../system/sql.php");

echo '<h2>System-Statistiken</h2>';
echo '<table class="table table-striped">';

// Webspell-Version (optional aus Konstante oder Datei lesen)
$webspell_version = defined('WEBSPELL_VERSION') ? WEBSPELL_VERSION : 'Unbekannt';
echo "<tr><td>Webspell-Version</td><td>$version</td></tr>";

// PHP-Version
echo "<tr><td>PHP-Version</td><td>" . phpversion() . "</td></tr>";

// MySQL-Version
$result = safe_query("SELECT VERSION() as version");
$row = mysqli_fetch_assoc($result);
echo "<tr><td>MySQL-Version</td><td>" . $row['version'] . "</td></tr>";

// Server-Betriebssystem
echo "<tr><td>Server-OS</td><td>" . php_uname() . "</td></tr>";

// Speicherverbrauch (aktueller)
$mem = round(memory_get_usage() / 1024 / 1024, 2);
echo "<tr><td>Speichernutzung (PHP)</td><td>$mem MB</td></tr>";

// Datenbankgröße berechnen
$db_name = mysqli_fetch_array(safe_query("SELECT DATABASE() AS db"))['db'];
$res = safe_query("SELECT table_schema AS db, 
  ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size 
  FROM information_schema.tables 
  WHERE table_schema = '$db_name' 
  GROUP BY table_schema");
$db = mysqli_fetch_assoc($res);
echo "<tr><td>Datenbankgröße</td><td>" . $db['db_size'] . " MB</td></tr>";

// Anzahl Tabellen
$res2 = safe_query("SELECT COUNT(*) AS tables_count FROM information_schema.tables WHERE table_schema = '$db_name'");
$db2 = mysqli_fetch_assoc($res2);
echo "<tr><td>Anzahl Tabellen</td><td>" . $db2['tables_count'] . "</td></tr>";

// Fehler-Log (falls aktiviert)
$log_path = ini_get('error_log');
if ($log_path && file_exists($log_path)) {
    $errors = @file($log_path);
    $last_errors = array_slice($errors, -5);
    echo "<tr><td>Letzte Fehler (aus error_log)</td><td><pre>" . htmlspecialchars(implode("", $last_errors)) . "</pre></td></tr>";
} else {
    echo "<tr><td>Fehler-Log</td><td>Nicht gefunden oder deaktiviert</td></tr>";
}

echo '</table>';

*/

/*

//####################################################
#require_once('../system/sql_connect.php'); // ggf. anpassen

echo '<div class="card p-4 mb-4">';
echo '<h3 class="mb-4">👤 Benutzerstatistiken</h3>';

// Gesamtanzahl Benutzer
$res = safe_query("SELECT COUNT(*) AS total_users FROM users");
$total_users = mysqli_fetch_array($res)['total_users'];

// Neue Benutzer
$today = strtotime(date('Y-m-d 00:00:00'));
$weekly = strtotime('-7 days');
$monthly = strtotime('-30 days');

$today_users = mysqli_fetch_array(safe_query("SELECT COUNT(*) AS count FROM users WHERE registerdate >= $today"))['count'];
$week_users = mysqli_fetch_array(safe_query("SELECT COUNT(*) AS count FROM users WHERE registerdate >= $weekly"))['count'];
$month_users = mysqli_fetch_array(safe_query("SELECT COUNT(*) AS count FROM users WHERE registerdate >= $monthly"))['count'];

// Aktive vs. inaktive Nutzer (letzte 30 Tage)
$last30 = strtotime('-30 days');
$active = mysqli_fetch_array(safe_query("SELECT COUNT(*) AS count FROM users WHERE lastlogin >= $last30"))['count'];
$inactive = $total_users - $active;

// Benutzer mit/ohne Avatar
$with_avatar = mysqli_fetch_array(safe_query("SELECT COUNT(*) AS count FROM users WHERE avatar != '' AND avatar IS NOT NULL"))['count'];
$without_avatar = $total_users - $with_avatar;

// Ausgabe
echo "<ul class='list-group'>";
echo "<li class='list-group-item'>👥 Gesamtanzahl Benutzer: <strong>$total_users</strong></li>";
echo "<li class='list-group-item'>🆕 Heute registriert: <strong>$today_users</strong></li>";
echo "<li class='list-group-item'>📈 Letzte 7 Tage: <strong>$week_users</strong></li>";
echo "<li class='list-group-item'>📅 Letzte 30 Tage: <strong>$month_users</strong></li>";
echo "<li class='list-group-item'>🟢 Aktive Benutzer (30 Tage): <strong>$active</strong></li>";
echo "<li class='list-group-item'>⚪ Inaktive Benutzer: <strong>$inactive</strong></li>";
echo "<li class='list-group-item'>🖼 Mit Profilbild: <strong>$with_avatar</strong></li>";
echo "<li class='list-group-item'>🚫 Ohne Profilbild: <strong>$without_avatar</strong></li>";
echo "</ul>";
echo '</div>';

// Benutzer nach Rollen
echo '<div class="card p-4 mb-4">';
echo '<h4>👤 Benutzer nach Rollen</h4>';
$result = safe_query("
    SELECT r.role_name, COUNT(*) AS count
    FROM user_role_assignments ura
    JOIN user_roles r ON ura.roleID = r.roleID
    GROUP BY r.roleID
");
echo "<ul class='list-group'>";
while ($row = mysqli_fetch_array($result)) {
    echo "<li class='list-group-item'>{$row['role_name']}: <strong>{$row['count']}</strong></li>";
}
echo "</ul>";
echo '</div>';

// Letzte Logins
echo '<div class="card p-4 mb-4">';
echo '<h4>⏱ Letzte Logins</h4>';
$result = safe_query("SELECT username, FROM_UNIXTIME(lastlogin) AS login_time FROM users ORDER BY lastlogin DESC LIMIT 10");
echo "<ul class='list-group'>";
while ($row = mysqli_fetch_array($result)) {
    echo "<li class='list-group-item'>{$row['username']} – <small>{$row['login_time']}</small></li>";
}
echo "</ul>";
echo '</div>';

// Top 10 aktivste Benutzer (z. B. Kommentare)
echo '<div class="card p-4 mb-4">';
echo '<h4>🏆 Aktivste Benutzer (Kommentare)</h4>';
/*$result = safe_query("
    SELECT u.nickname, COUNT(c.commentID) AS comments
    FROM comments c
    JOIN user u ON c.userID = u.userID
    GROUP BY c.userID
    ORDER BY comments DESC
    LIMIT 10
");
echo "<ol class='list-group list-group-numbered'>";
while ($row = mysqli_fetch_array($result)) {
    echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
            {$row['nickname']}
            <span class='badge bg-primary rounded-pill'>{$row['comments']} Kommentare</span>
          </li>";
}
echo "</ol>";*/
#echo '</div>';

// Optional: Geo-Statistik
//  echo '<div class="card p-4 mb-4">';
//  echo '<h4>🌍 Geografische Verteilung</h4>';
//  $geo = safe_query("SELECT country_code, COUNT(*) AS count FROM user GROUP BY country_code");
//  while ($row = mysqli_fetch_array($geo)) {
//      echo $row['country_code'] . ': ' . $row['count'] . ' Benutzer<br>';
//  }
//  echo '</div>';



// Benutzerstatistiken vorbereiten
$res = safe_query("SELECT COUNT(*) AS total_users FROM users");
$total_users = mysqli_fetch_array($res)['total_users'];

$today = strtotime(date('Y-m-d 00:00:00'));
$weekly = strtotime('-7 days');
$monthly = strtotime('-30 days');

$today_users = mysqli_fetch_array(safe_query("SELECT COUNT(*) AS count FROM users WHERE registerdate >= $today"))['count'];
$week_users = mysqli_fetch_array(safe_query("SELECT COUNT(*) AS count FROM users WHERE registerdate >= $weekly"))['count'];
$month_users = mysqli_fetch_array(safe_query("SELECT COUNT(*) AS count FROM users WHERE registerdate >= $monthly"))['count'];

$last30 = strtotime('-30 days');
$active = mysqli_fetch_array(safe_query("SELECT COUNT(*) AS count FROM users WHERE lastlogin >= $last30"))['count'];
$inactive = $total_users - $active;

#$with_avatar = mysqli_fetch_array(safe_query("SELECT COUNT(*) AS count FROM users WHERE avatar != '' AND avatar IS NOT NULL"))['count'];
#$without_avatar = $total_users - $with_avatar;

// Benutzer nach Rollen
$roles = safe_query("
    SELECT r.role_name, COUNT(*) AS count
    FROM user_role_assignments ura
    JOIN user_roles r ON ura.roleID = r.roleID
    GROUP BY r.roleID
");

// Letzte Logins
#$logins = safe_query("SELECT username, FROM_UNIXTIME(lastlogin) AS login_time FROM users ORDER BY lastlogin DESC LIMIT 10");


// Klickstatistik vorbereiten
$startDate = date('Y-m-d', strtotime('-30 days'));
$endDate = date('Y-m-d');

$clicksPerDayRes = $_database->query("
    SELECT DATE(clicked_at) AS day, COUNT(*) AS clicks
    FROM link_clicks
    WHERE clicked_at BETWEEN '$startDate' AND '$endDate'
    GROUP BY day
    ORDER BY day DESC
");

$topUrlsRes = $_database->query("
    SELECT url, COUNT(*) AS clicks
    FROM link_clicks
    GROUP BY url
    ORDER BY clicks DESC
    LIMIT 10
");

$topIpsRes = $_database->query("
    SELECT ip_address, COUNT(*) AS clicks
    FROM link_clicks
    GROUP BY ip_address
    ORDER BY clicks DESC
    LIMIT 5
");

$totalClicks = $_database->query("SELECT COUNT(*) AS total FROM link_clicks")->fetch_assoc()['total'];

// Benutzer mit Avatar
$with_avatar_result = safe_query("
  SELECT COUNT(*) AS count
  FROM users u
  INNER JOIN user_profiles p ON u.userID = p.userID
  WHERE p.avatar IS NOT NULL AND p.avatar != ''
");
$row = mysqli_fetch_assoc($with_avatar_result);
$with_avatar = (int)$row['count'];

// Benutzer ohne Avatar
$without_avatar_result = safe_query("
  SELECT COUNT(*) AS count
  FROM users u
  LEFT JOIN user_profiles p ON u.userID = p.userID
  WHERE p.avatar IS NULL OR p.avatar = ''
");
$row2 = mysqli_fetch_assoc($without_avatar_result);
$without_avatar = (int)$row2['count'];


// Filter nur für 'sponsors'
#$plugin = 'sponsors';

// Pagination (optional)
$limit = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Anzahl Klicks zählen
$countResult = $_database->query("SELECT COUNT(*) as count FROM link_clicks");
$countRow = $countResult->fetch_assoc();
$totalClicks = $countRow['count'];

// Klicks holen mit Limit & Offset
$sql = "SELECT * FROM link_clicks";
$result = $_database->query($sql);
?>

<!-- Benutzerstatistiken -->
<!-- Benutzerstatistiken -->
<div class="row">
  <!-- Benutzerstatistiken -->
  <div class="col-md-4">
    <div class="card mb-4">
      <div class="card-header">
        <h3 class="mb-0">👤 Benutzerstatistiken</h3>
      </div>
      <div class="card-body">
        <ul class="list-group list-group-flush">
          <li class="list-group-item">👥 Gesamtanzahl Benutzer: <strong><?= $total_users ?></strong></li>
          <li class="list-group-item">🆕 Heute registriert: <strong><?= $today_users ?></strong></li>
          <li class="list-group-item">📈 Letzte 7 Tage: <strong><?= $week_users ?></strong></li>
          <li class="list-group-item">📅 Letzte 30 Tage: <strong><?= $month_users ?></strong></li>
          <li class="list-group-item">🟢 Aktive Benutzer (30 Tage): <strong><?= $active ?></strong></li>
          <li class="list-group-item">⚪ Inaktive Benutzer: <strong><?= $inactive ?></strong></li>
          <li class="list-group-item">🖼 Mit Profilbild: <strong><?= $with_avatar ?></strong></li>
          <li class="list-group-item">🚫 Ohne Profilbild: <strong><?= $without_avatar ?></strong></li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Benutzer nach Rollen -->
  <div class="col-md-4">
    <div class="card mb-4">
      <div class="card-header">
        <h4 class="mb-0">👤 Benutzer nach Rollen</h4>
      </div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php while ($row = mysqli_fetch_array($roles)): ?>
            <li class="list-group-item d-flex justify-content-between">
              <?= htmlspecialchars($row['role_name']) ?>
              <span class="badge bg-primary rounded-pill"><?= $row['count'] ?></span>
            </li>
          <?php endwhile; ?>
        </ul>
      </div>
    </div>
  </div>


<!-- Letzte Logins -->
<div class="col-md-4">
<div class="card mb-4">
  <div class="card-header">
    <h4 class="mb-0">⏱ Letzte Logins</h4>
  </div>
  <div class="card-body p-0">
    <ul class="list-group">
      <?php 
      $logins = safe_query("SELECT username, FROM_UNIXTIME(lastlogin) AS login_time FROM users ORDER BY lastlogin DESC LIMIT 10");
      while ($row = mysqli_fetch_assoc($logins)): 
          $login_time = $row['login_time'] ?? '';
      ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?= htmlspecialchars($row['username']) ?>
          <small class="text-muted" style="color: red; font-weight: bold;">
            <?= htmlspecialchars($login_time) ?>
          </small>
        </li>
      <?php endwhile; ?>
    </ul>
  </div>
</div>
</div>
</div>
<!-- Klickstatistiken -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="bi bi-bar-chart-line"></i> Link-Klick-Auswertung (letzte 30 Tage)</h5>
    <span class="badge bg-secondary">Gesamt: <?= $totalClicks ?></span>
  </div>
  <div class="card-body">

    <h6><i class="bi bi-calendar-week"></i> Klicks pro Tag</h6>
    <table class="table table-striped table-sm mb-4">
      <thead class="table-light">
        <tr><th>Datum</th><th>Klicks</th></tr>
      </thead>
      <tbody>
        <?php while ($row = $clicksPerDayRes->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['day']) ?></td>
            <td><?= $row['clicks'] ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <h6><i class="bi bi-link-45deg"></i> Top 10 URLs</h6>
    <table class="table table-striped table-sm mb-4">
      <thead class="table-light">
        <tr><th>URL</th><th>Klicks</th></tr>
      </thead>
      <tbody>
        <?php while ($row = $topUrlsRes->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($row['url']) ?></td>
            <td><?= $row['clicks'] ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <h6><i class="bi bi-pc-display"></i> Top IP-Adressen</h6>
    <ul class="list-group list-group-flush">
      <?php while ($row = $topIpsRes->fetch_assoc()): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <?= htmlspecialchars($row['ip_address']) ?>
          <span class="badge bg-dark rounded-pill"><?= $row['clicks'] ?></span>
        </li>
      <?php endwhile; ?>
    </ul>

  </div>
</div>


<!-- Klicks Verwaltung -->
<div class="card mb-4">
  <div class="card-header">
    <h3 class="mb-0">Klicks Verwaltung</h3>
  </div>
  <div class="card-body">
    <table class="table table-striped table-sm mb-4">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Plugin</th>
          <th>Klick URL</th>
          <th>Klickzeit</th>
          <th>IP-Adresse</th>
          <th>User-Agent</th>
          <th>Aktion</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['id']) ?></td>
              <td><?= htmlspecialchars($row['plugin']) ?></td>
              <td><a href="<?= htmlspecialchars($row['url']) ?>" target="_blank" rel="nofollow"><?= htmlspecialchars($row['url']) ?></a></td>
              <td><?= htmlspecialchars($row['clicked_at']) ?></td>
              <td><?= htmlspecialchars($row['ip_address']) ?></td>
              <td><?= htmlspecialchars($row['user_agent']) ?></td>
              <td>
                <form method="post" onsubmit="return confirm('Wirklich löschen?');" class="m-0">
                  <input type="hidden" name="delete_id" value="<?= (int)$row['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Löschen</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" class="text-center">Keine Klicks gefunden.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

    <!-- Pagination -->
    <?php
    $totalPages = ceil($totalClicks / $limit);
    if ($totalPages > 1): ?>
        <nav>
          <ul class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
    <?php endif; ?>


<?php
// Löschfunktion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    $_database->query("DELETE FROM link_clicks WHERE id = $deleteId AND plugin = '$plugin'");
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}
?>



