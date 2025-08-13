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

// Parameter Access Control Log
$pageAccess = isset($_GET['page_access']) ? max(1, (int)$_GET['page_access']) : 1;
$searchAccess = isset($_GET['search_access']) ? trim($_GET['search_access']) : '';

// Parameter Suspicious Access Log
$pageSuspicious = isset($_GET['page_suspicious']) ? max(1, (int)$_GET['page_suspicious']) : 1;
$searchSuspicious = isset($_GET['search_suspicious']) ? trim($_GET['search_suspicious']) : '';

$linesPerPage = 50;

function loadLogLines(string $file, string $search, int $page, int $linesPerPage): array {
    if (!file_exists($file)) {
        return ['lines' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
    }
    $logLines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($logLines === false) {
        return ['lines' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
    }
    if ($search !== '') {
        $logLines = array_filter($logLines, fn($line) => stripos($line, $search) !== false);
    }
    $logLines = array_reverse($logLines);

    $totalLines = count($logLines);
    $totalPages = max(1, (int)ceil($totalLines / $linesPerPage));
    if ($page > $totalPages) $page = $totalPages;

    $start = ($page - 1) * $linesPerPage;
    $displayLines = array_slice($logLines, $start, $linesPerPage);

    return ['lines' => $displayLines, 'total' => $totalLines, 'pages' => $totalPages, 'page' => $page];
}

$accessLog = loadLogLines($logFileAccess, $searchAccess, $pageAccess, $linesPerPage);
$suspiciousLog = loadLogLines($logFileSuspicious, $searchSuspicious, $pageSuspicious, $linesPerPage);
?>

<style>
  pre {
    background: #222;
    color: #eee;
    padding: 10px;
    max-height: 400px;
    overflow-x: auto;
    white-space: pre-wrap;
  }
  .container {
    margin-bottom: 3rem;
  }
  
</style>

<div class="container">
    <h2>Admin Log Viewer: Access Control Log</h2>

    <form method="get" class="mb-3 row g-2">
        <div class="col-auto">
            <input type="text" name="search_access" class="form-control" placeholder="Filter (z.B. IP, userID, Modulname)" value="<?= htmlspecialchars($searchAccess) ?>">
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
                    <a class="page-link" href="?page_access=<?= $i ?>&search_access=<?= urlencode($searchAccess) ?>&search_suspicious=<?= urlencode($searchSuspicious) ?>&page_suspicious=<?= $pageSuspicious ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<div class="container">
    <h2>Admin Log Viewer: Suspicious Access Log</h2>

    <form method="get" class="mb-3 row g-2">
        <div class="col-auto">
            <input type="text" name="search_suspicious" class="form-control" placeholder="Filter (z.B. IP, userID, Modulname)" value="<?= htmlspecialchars($searchSuspicious) ?>">
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
                    <a class="page-link" href="?page_suspicious=<?= $i ?>&search_suspicious=<?= urlencode($searchSuspicious) ?>&search_access=<?= urlencode($searchAccess) ?>&page_access=<?= $pageAccess ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
