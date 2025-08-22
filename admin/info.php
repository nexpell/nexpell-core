<?php
use nexpell\LanguageService;

// Sicherstellen, dass Session läuft
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sprache initialisieren, Standard ist Deutsch
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

// Die globale Variable für die MySQLi-Verbindung wird angenommen
global $languageService, $_database;
$languageService = new LanguageService($_database);
$languageService->readModule('info', true);

// Angemeldeter Benutzer
$userID = (int)$_SESSION['userID'];
$adminName = $_SESSION['username'] ?? 'Admin';

// =======================================================
// DYNAMISCHE DATEN AUS DER DATENBANK MIT MYSQLI LADEN
// =======================================================

// 1. Aktive Nutzer (Nutzer mit Aktivität in den letzten 5 Minuten)
$stmt = $_database->prepare("SELECT COUNT(*) FROM users WHERE last_activity > NOW() - INTERVAL 5 MINUTE AND is_online = 1");
$stmt->execute();
$stmt->bind_result($onlineUsers);
$stmt->fetch();
$stmt->close();
$onlineUsers = $onlineUsers ?: 0;

// 2. Anzahl installierter Plugins
$stmt = $_database->prepare("SELECT COUNT(*) FROM settings_plugins_installed");
$stmt->execute();
$stmt->bind_result($installedPlugins);
$stmt->fetch();
$stmt->close();
$installedPlugins = $installedPlugins ?: 0;

// 3. Anzahl installierter Themes
$stmt = $_database->prepare("SELECT COUNT(*) FROM settings_themes_installed");
$stmt->execute();
$stmt->bind_result($installedThemes);
$stmt->fetch();
$stmt->close();
$installedThemes = $installedThemes ?: 0;

// 4. Gesamtbesucher und Seitenaufrufe
$stmt = $_database->prepare("SELECT COUNT(DISTINCT ip_address) AS total_visitors, SUM(pageviews) AS total_pageviews FROM visitor_statistics");
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$totalVisitors = number_format($stats['total_visitors'] ?? 0);
$totalPageviews = number_format($stats['total_pageviews'] ?? 0);
$stmt->close();

// 5. Letztes Backup
$stmt = $_database->prepare("SELECT createdate FROM backups ORDER BY createdate DESC LIMIT 1");
$stmt->execute();
$stmt->bind_result($lastBackupDate);
$stmt->fetch();
$stmt->close();
$lastBackups = $lastBackupDate ? date('d.m.Y H:i', strtotime($lastBackupDate)) : 'Kein Backup gefunden';

// 6. Neueste Benutzer
$stmt = $_database->prepare("
    SELECT u.username, ur.role_name AS role, u.registerdate AS registered_at
    FROM users u
    JOIN user_roles ur ON u.role = ur.roleID
    ORDER BY u.registerdate DESC
    LIMIT 3
");
$stmt->execute();
$result = $stmt->get_result();
$latestUsers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Formatieren der 'when'-Spalte
foreach ($latestUsers as &$user) {
    $registeredTimestamp = strtotime($user['registered_at']);
    $diff = time() - $registeredTimestamp;
    $user['when'] = '';
    if ($diff < 60) {
        $user['when'] = $diff . ' Sek.';
    } elseif ($diff < 3600) {
        $user['when'] = floor($diff / 60) . ' Min.';
    } elseif ($diff < 86400) {
        $user['when'] = floor($diff / 3600) . ' Std.';
    } else {
        $user['when'] = floor($diff / 86400) . ' Tagen';
    }
}
unset($user);

// 7. Kürzliche System-Aktivitäten
// Diese Daten sind weiterhin statisch, da keine Log-Tabelle existiert.
$recentLogs = [
    '2025-08-22 10:15 - Login: user: Admin',
    '2025-08-22 09:40 - Backup-Erstellung gestartet',
    '2025-08-21 16:30 - System-Update: v2.0.1 installiert',
];

// 8. Versionsprüfung für Updates
$current_version = file_exists(__DIR__ . '/../system/version.php') ? include __DIR__ . '/../system/version.php' : '2.0.0';
$update_info_url = "https://update.nexpell.de/updates/update_info.json";
$update_info = json_decode(@file_get_contents($update_info_url), true);
$updateAvailable = false;
$updateText = 'Aktuell';
if (isset($update_info['updates']) && is_array($update_info['updates'])) {
    foreach ($update_info['updates'] as $update) {
        if (version_compare($update['version'], $current_version, '>')) {
            $updateAvailable = true;
            $updateText = "v{$update['version']} verfügbar";
            break;
        }
    }
}
?>

<style>
    /* UI-Tweaks für ein besseres Layout */
    .card-shortcuts .btn { min-width: 140px; }
    .metric { font-size: 1.5rem; font-weight: 600; }
    .small-muted { color: #6c757d; font-size: .9rem; }
    .fixed-col { min-width: 160px; }
    .activity-pre { max-height: 220px; overflow: auto; background: #f8f9fa; padding: 10px; border-radius: .375rem; }
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Hallo, <?= htmlspecialchars($adminName) ?> 👋</h1>
            <div class="small-muted">Übersicht & Schnellzugriffe</div>
        </div>
        <div class="text-end">
            <?php if ($updateAvailable): ?>
                <a href="admincenter.php?site=update_core" class="btn btn-warning btn-sm">
                    <i class="bi bi-arrow-up-circle"></i> Update: <?= htmlspecialchars($updateText) ?>
                </a>
            <?php else: ?>
                <span class="badge bg-success">System aktuell</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="small-muted">Online-Nutzer</div>
                    <div class="metric"><?= number_format($onlineUsers) ?></div>
                    <div class="small-muted mt-2">Jetzt online</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="small-muted">Installierte Plugins</div>
                    <div class="metric"><?= (int)$installedPlugins ?></div>
                    <a href="admincenter.php?site=plugin_manager" class="small-muted">Plugins verwalten</a>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="small-muted">Installierte Themes</div>
                    <div class="metric"><?= (int)$installedThemes ?></div>
                    <a href="admincenter.php?site=theme_installer" class="small-muted">Themes verwalten</a>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="small-muted">Letztes Backup</div>
                    <div class="metric"><?= htmlspecialchars($lastBackups) ?></div>
                    <a href="admincenter.php?site=database" class="small-muted">Backup-Verwaltung</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card card-shortcuts shadow-sm">
                        <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
                            <div>
                                <h5 class="card-title mb-1">Schnellzugriffe</h5>
                                <div class="small-muted mb-2">Aktionen, die Admins häufig nutzen</div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="admincenter.php?site=posts_new" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Neuer Beitrag</a>
                                    <a href="admincenter.php?site=user_roles" class="btn btn-outline-secondary btn-sm"><i class="bi bi-people"></i> Benutzer</a>
                                    <a href="admincenter.php?site=plugin_manager" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plug"></i> Plugins</a>
                                    <a href="admincenter.php?site=settings" class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear"></i> Einstellungen</a>
                                    <a href="admincenter.php?site=media" class="btn btn-outline-secondary btn-sm"><i class="bi bi-image"></i> Medien</a>
                                </div>
                            </div>
                            <div class="text-end small-muted">
                                <div>Letzte Anmeldung: <?= date('d.m.Y H:i') ?></div>
                                <div class="mt-2"><a href="admincenter.php?site=profile" class="link-primary">Profil & Sicherheit</a></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Kürzliche System-Aktivitäten</h5>
                            <div class="small-muted mb-2">Wichtige Log-Einträge & Aktionen</div>
                            <div class="row">
                                <div class="col-md-7">
                                    <ul class="list-group list-group-flush">
                                        <?php if (!empty($recentLogs)): ?>
                                            <?php foreach ($recentLogs as $log): ?>
                                                <li class="list-group-item small-muted"><?= htmlspecialchars($log) ?></li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="list-group-item text-muted">Keine Log-Einträge gefunden.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="col-md-5">
                                    <div class="small-muted mb-1">Schnellaktionen</div>
                                    <div class="d-grid gap-2">
                                        <a href="admincenter.php?site=site_lock" class="btn btn-outline-primary btn-sm">Wartungsmodus</a>
                                        <a href="admincenter.php?site=database" class="btn btn-outline-success btn-sm">Backup jetzt starten</a>
                                        <a href="admincenter.php?site=security_overview" class="btn btn-outline-danger btn-sm">Sicherheits-Scan</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Webseiten-Statistiken</h5>
                            <div class="d-flex justify-content-between">
                                <div class="text-center">
                                    <div class="metric text-primary"><?= $totalVisitors ?></div>
                                    <div class="small-muted">Besucher</div>
                                </div>
                                <div class="text-center">
                                    <div class="metric text-primary"><?= $totalPageviews ?></div>
                                    <div class="small-muted">Seitenaufrufe</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Neueste Benutzer</h5>
                            <div class="small-muted mb-2">Letzte Registrierungen / Anmeldungen</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th class="text-end">Rolle</th>
                                            <th class="text-end">Vor</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($latestUsers)): ?>
                                            <?php foreach ($latestUsers as $u): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($u['username']) ?></td>
                                                    <td class="text-end"><span class="badge bg-secondary"><?= htmlspecialchars($u['role']) ?></span></td>
                                                    <td class="text-end small-muted"><?= htmlspecialchars($u['when']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">Keine neuen Benutzer gefunden.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2 text-end"><a href="admincenter.php?site=user_roles" class="link-primary">Alle Benutzer</a></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small-muted">Schnellhilfe: <a href="/docs" class="link-primary">Dokumentation</a> · <a href="/forum" class="link-primary">Forum</a> · <a href="https://discord.gg/..." target="_blank" class="link-primary">Discord</a></div>
                    <div class="small-muted">Version: <strong><?= htmlspecialchars($current_version) ?></strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid p-4">
    <div class="row">
        <div class="col-12">
            <h4 class="mb-3">Neuigkeiten vom nexpell-Team</h4>
            <div class="list-group shadow-sm mb-4">
                <?php if (!empty($news_updates)): ?>
                    <?php foreach ($news_updates as $news): ?>
                        <a href="<?php echo htmlspecialchars($news['link'] ?? '#'); ?>" class="list-group-item list-group-item-action" target="_blank">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo htmlspecialchars($news['title']); ?></strong>
                                <small class="text-muted"><?php echo htmlspecialchars($news['date']); ?></small>
                            </div>
                            <p class="mb-0 small"><?php echo htmlspecialchars($news['summary']); ?></p>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="list-group-item text-muted">Keine News verfügbar</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>