<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['language'] = $_SESSION['language'] ?? 'de';

use nexpell\AccessControl;
AccessControl::checkAdminAccess('ac_log_viewer');

// Log-Dateien
$logFileAccess = __DIR__ . '/logs/access_control.log';
$logFileSuspicious = __DIR__ . '/logs/suspicious_access.log';
$blockedIPsFile = __DIR__ . '/logs/blocked_ips.json';

// Parameter Access Control Log
$pageAccess = isset($_GET['page_access']) ? max(1, (int)$_GET['page_access']) : 1;
$searchAccess = isset($_GET['search_access']) ? trim($_GET['search_access']) : '';

// Parameter Suspicious Access Log
$pageSuspicious = isset($_GET['page_suspicious']) ? max(1, (int)$_GET['page_suspicious']) : 1;
$searchSuspicious = isset($_GET['search_suspicious']) ? trim($_GET['search_suspicious']) : '';

// Parameter Blocked IPs
$pageBlocked = isset($_GET['page_blocked']) ? max(1, (int)$_GET['page_blocked']) : 1;
$searchBlocked = isset($_GET['search_blocked']) ? trim($_GET['search_blocked']) : '';

$linesPerPage = 50;

/* =========================
   POST-Handler: IP block/unblock
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blockedData = file_exists($blockedIPsFile) ? json_decode(file_get_contents($blockedIPsFile), true) : [];
    if (!is_array($blockedData)) $blockedData = [];

    // Neue IP blockieren
    if (isset($_POST['block_ip'], $_POST['block_reason'])) {
        $ip = trim($_POST['block_ip']);
        $reason = trim($_POST['block_reason']);
        if ($ip !== '') {
            $blockedData[] = ['ip' => $ip, 'reason' => $reason, 'date' => date('Y-m-d H:i:s')];
        }
    }

    // IP freigeben
    if (isset($_POST['unblock_ip'])) {
        $unblockIP = $_POST['unblock_ip'];
        $blockedData = array_filter($blockedData, fn($item) => ($item['ip'] ?? '') !== $unblockIP);
    }

    file_put_contents($blockedIPsFile, json_encode(array_values($blockedData), JSON_PRETTY_PRINT));
    // Redirect nach POST
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

/* =========================
   Funktionen laden Logs
========================= */
function loadLogLines(string $file, string $search, int $page, int $linesPerPage): array {
    if (!file_exists($file)) return ['lines' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
    $logLines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($logLines === false) return ['lines' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
    if ($search !== '') $logLines = array_filter($logLines, fn($line) => stripos($line, $search) !== false);
    $logLines = array_reverse($logLines);

    $totalLines = count($logLines);
    $totalPages = max(1, (int)ceil($totalLines / $linesPerPage));
    $page = min($page, $totalPages);

    $start = ($page - 1) * $linesPerPage;
    $displayLines = array_slice($logLines, $start, $linesPerPage);

    return ['lines' => $displayLines, 'total' => $totalLines, 'pages' => $totalPages, 'page' => $page];
}

function loadBlockedIPs(string $file, string $search, int $page, int $linesPerPage): array {
    if (!file_exists($file)) return ['lines' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) $data = [];

    if ($search !== '') {
        $data = array_filter($data, fn($item) => stripos($item['ip'] ?? '', $search) !== false || stripos($item['reason'] ?? '', $search) !== false);
    }

    $totalLines = count($data);
    $totalPages = max(1, (int)ceil($totalLines / $linesPerPage));
    $page = min($page, $totalPages);

    $start = ($page - 1) * $linesPerPage;
    $displayLines = array_slice($data, $start, $linesPerPage);

    return ['lines' => $displayLines, 'total' => $totalLines, 'pages' => $totalPages, 'page' => $page];
}





/* =========================
   Logs laden
========================= */
$accessLog = loadLogLines($logFileAccess, $searchAccess, $pageAccess, $linesPerPage);
$suspiciousLog = loadLogLines($logFileSuspicious, $searchSuspicious, $pageSuspicious, $linesPerPage);
$blockedIPs = loadBlockedIPs($blockedIPsFile, $searchBlocked, $pageBlocked, $linesPerPage);



// Level automatisch bestimmen
foreach ($blockedIPs['lines'] as &$item) {
    if (stripos($item['reason'], 'fehlgeschlagen') !== false) {
        $item['level'] = 'critical';
    } elseif (stripos($item['reason'], 'test') !== false) {
        $item['level'] = 'warning';
    } else {
        $item['level'] = 'info';
    }
}
unset($item);
?>

<style>
  pre { background: #222; color: #eee; padding: 10px; max-height: 400px; overflow-x: auto; white-space: pre-wrap; }
  .container { margin-bottom: 3rem; }
</style>

<!-- =========================
   Access Control Log
========================= -->
<div class="container">
    <h2>Admin Log Viewer: Access Control Log</h2>

    <form method="get" class="mb-3 row g-2">
        <div class="col-auto">
            <input type="text" name="search_access" class="form-control" placeholder="Filter (IP, userID, Modulname)" value="<?= htmlspecialchars($searchAccess) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Suchen</button>
        </div>
    </form>

    <p>Zeige <?= count($accessLog['lines']) ?> von <?= $accessLog['total'] ?> Einträgen (Seite <?= $accessLog['page'] ?> von <?= $accessLog['pages'] ?>)</p>
    <pre><?= htmlspecialchars(implode("\n", $accessLog['lines'])) ?></pre>

    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $accessLog['pages']; $i++): ?>
                <li class="page-item <?= $i === $accessLog['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="admincenter.php?site=log_viewer&page_access=<?= $i ?>&search_access=<?= urlencode($searchAccess) ?>&page_suspicious=<?= $pageSuspicious ?>&search_suspicious=<?= urlencode($searchSuspicious) ?>&page_blocked=<?= $pageBlocked ?>&search_blocked=<?= urlencode($searchBlocked) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- =========================
   Suspicious Log
========================= -->
<div class="container">
    <h2>Admin Log Viewer: Suspicious Access Log</h2>

    <form method="get" class="mb-3 row g-2">
        <div class="col-auto">
            <input type="text" name="search_suspicious" class="form-control" placeholder="Filter (IP, userID, Modulname)" value="<?= htmlspecialchars($searchSuspicious) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-danger">Suchen</button>
        </div>
    </form>

    <p>Zeige <?= count($suspiciousLog['lines']) ?> von <?= $suspiciousLog['total'] ?> Einträgen (Seite <?= $suspiciousLog['page'] ?> von <?= $suspiciousLog['pages'] ?>)</p>
    <pre><?= htmlspecialchars(implode("\n", $suspiciousLog['lines'])) ?></pre>

    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $suspiciousLog['pages']; $i++): ?>
                <li class="page-item <?= $i === $suspiciousLog['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="admincenter.php?site=log_viewer&page_suspicious=<?= $i ?>&search_suspicious=<?= urlencode($searchSuspicious) ?>&page_access=<?= $pageAccess ?>&search_access=<?= urlencode($searchAccess) ?>&page_blocked=<?= $pageBlocked ?>&search_blocked=<?= urlencode($searchBlocked) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<!-- =========================
   Blocked IPs
========================= -->
<div class="container">
    <h2>Gesperrte IPs</h2>

    <!-- Formular: Neue IP sperren -->
    <form method="post" class="mb-3 row g-2">
        <div class="col-auto">
            <input type="text" name="block_ip" class="form-control" placeholder="IP-Adresse" required>
        </div>
        <div class="col-auto">
            <input type="text" name="block_reason" class="form-control" placeholder="Grund">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-warning">IP sperren</button>
        </div>
    </form>

    <!-- Suchformular -->
    <form method="get" class="mb-3 row g-2">
        <div class="col-auto">
            <input type="text" name="search_blocked" class="form-control" placeholder="Filter IP/Grund" value="<?= htmlspecialchars($searchBlocked) ?>">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-secondary">Suchen</button>
        </div>
    </form>

    <table class="table table-striped">
        <thead>
            <tr><th>IP</th><th>Grund</th><th>Datum</th><th>Status</th><th>Aktion</th></tr>
        </thead>
        <tbody>
            <?php foreach ($blockedIPs['lines'] as $item): ?>
                <?php
                    // Zeilenfarbe basierend auf 'level'
                    $rowClass = '';
                    $statusBadge = '';
                    if (!empty($item['level'])) {
                        switch ($item['level']) {
                            case 'critical':
                                $rowClass = 'table-danger'; // rot
                                $statusBadge = '<span class="badge bg-danger">Kritisch</span>';
                                break;
                            case 'warning':
                                $rowClass = 'table-warning'; // gelb
                                $statusBadge = '<span class="badge bg-warning text-dark">Warnung</span>';
                                break;
                            case 'info':
                                $rowClass = 'table-info'; // blau
                                $statusBadge = '<span class="badge bg-info text-dark">Info</span>';
                                break;
                        }
                    }
                ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= htmlspecialchars($item['ip'] ?? '') ?></td>
                    <td><?= htmlspecialchars($item['reason'] ?? '') ?></td>
                    <td><?= htmlspecialchars($item['date'] ?? '') ?></td>
                    <td><?= $statusBadge ?></td>
                    <td>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="unblock_ip" value="<?= htmlspecialchars($item['ip'] ?? '') ?>">
                            <button type="submit" class="btn btn-success btn-sm">Freigeben</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p>Zeige <?= count($blockedIPs['lines']) ?> von <?= $blockedIPs['total'] ?> Einträgen (Seite <?= $blockedIPs['page'] ?> von <?= $blockedIPs['pages'] ?>)</p>

    <nav>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $blockedIPs['pages']; $i++): ?>
                <li class="page-item <?= $i === $blockedIPs['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="admincenter.php?site=log_viewer&page_blocked=<?= $i ?>&search_blocked=<?= urlencode($searchBlocked) ?>&page_access=<?= $pageAccess ?>&search_access=<?= urlencode($searchAccess) ?>&page_suspicious=<?= $pageSuspicious ?>&search_suspicious=<?= urlencode($searchSuspicious) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
