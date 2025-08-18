<?php
use nexpell\LanguageService;

// Session absichern
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sprache initialisieren
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

global $languageService;
$languageService = new LanguageService($_database);
$languageService->readModule('info', true);

// Angemeldeter Benutzer
$userID = (int)$_SESSION['userID'];


// Beispiel-Dummy-Daten (ersetzte das mit echten Abfragen)
$adminName = $_SESSION['username'] ?? 'Admin';
$activeUsers = 124;
$pendingTickets = 3;
$unpublishedPosts = 5;
$lastBackups = 'vor 2 Tagen';
$latestUsers = [
    ['name'=>'Anna', 'when'=>'10 Min', 'role'=>'Editor'],
    ['name'=>'Max', 'when'=>'1 Std', 'role'=>'Admin'],
    ['name'=>'Lena', 'when'=>'3 Std', 'role'=>'Gast'],
];
$recentLogs = [
    '2025-08-01 12:12 - Login: userID=1 ip=1.2.3.4',
    '2025-08-01 11:55 - Backup completed',
    '2025-08-01 10:30 - Plugin updated: seo-tool',
];
$updateAvailable = true;
$updateText = 'v2.1.0 verfügbar – Sicherheitsupdate';
?>


  <style>
    /* Kleine UI-Tweaks */
    .card-shortcuts .btn { min-width: 140px; }
    .metric { font-size: 1.5rem; font-weight: 600; }
    .small-muted { color: #6c757d; font-size: .9rem; }
    .fixed-col { min-width: 160px; }
    .activity-pre { max-height: 220px; overflow:auto; background:#f8f9fa; padding:10px; border-radius:.375rem; }
  </style>


<div class="container-fluid p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h3 mb-0">Hallo, <?= htmlspecialchars($adminName) ?> 👋</h1>
      <div class="small-muted">Übersicht & Schnellzugriffe</div>
    </div>
    <div class="text-end">
      <?php if ($updateAvailable): ?>
        <a href="/admin/updates.php" class="btn btn-warning btn-sm">
          <i class="bi bi-arrow-up-circle"></i> Update: <?= htmlspecialchars($updateText) ?>
        </a>
      <?php else: ?>
        <span class="badge bg-success">System aktuell</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Top metrics -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="small-muted">Aktive Nutzer</div>
          <div class="metric"><?= number_format($activeUsers) ?></div>
          <div class="small-muted mt-2">in den letzten 24 Std.</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="small-muted">Offene Tickets</div>
          <div class="metric text-danger"><?= (int)$pendingTickets ?></div>
          <a href="/admin/tickets.php" class="small-muted">Zum Ticket-System</a>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="small-muted">Unveröffentlichte Beiträge</div>
          <div class="metric"><?= (int)$unpublishedPosts ?></div>
          <a href="/admin/posts.php?filter=draft" class="small-muted">Beiträge verwalten</a>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="small-muted">Letztes Backup</div>
          <div class="metric"><?= htmlspecialchars($lastBackups) ?></div>
          <a href="/admin/backups.php" class="small-muted">Backup-Verwaltung</a>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Left column: shortcuts + activity -->
    <div class="col-lg-7">
      <div class="row g-3">
        <!-- Shortcuts -->
        <div class="col-12">
          <div class="card card-shortcuts shadow-sm">
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap">
              <div>
                <h5 class="card-title mb-1">Schnellzugriffe</h5>
                <div class="small-muted mb-2">Aktionen, die Admins häufig nutzen</div>
                <div class="d-flex gap-2 flex-wrap">
                  <a href="/admin/posts_new.php" class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Neuer Beitrag</a>
                  <a href="/admin/users.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-people"></i> Benutzer</a>
                  <a href="/admin/plugins.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-plug"></i> Plugins</a>
                  <a href="/admin/settings.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear"></i> Einstellungen</a>
                  <a href="/admin/media.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-image"></i> Medien</a>
                </div>
              </div>
              <div class="text-end small-muted">
                <div>Letzte Anmeldung: <?= date('d.m.Y H:i') ?></div>
                <div class="mt-2"><a href="/admin/profile.php" class="link-primary">Profil & Sicherheit</a></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent activity / logs -->
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title">Kürzliche System-Aktivitäten</h5>
              <div class="small-muted mb-2">Wichtige Log-Einträge & Aktionen</div>
              <div class="row">
                <div class="col-md-7">
                  <ul class="list-group list-group-flush">
                    <?php foreach ($recentLogs as $log): ?>
                      <li class="list-group-item small-muted"><?= htmlspecialchars($log) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
                <div class="col-md-5">
                  <div class="small-muted mb-1">Schnellaktionen</div>
                  <div class="d-grid gap-2">
                    <a href="/admin/maintenance.php" class="btn btn-outline-primary btn-sm">Wartungsmodus</a>
                    <a href="/admin/backups.php" class="btn btn-outline-success btn-sm">Backup jetzt starten</a>
                    <a href="/admin/security.php" class="btn btn-outline-danger btn-sm">Sicherheits-Scan</a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Right column: users + stats -->
    <div class="col-lg-5">
      <div class="row g-3">
        <!-- Latest users -->
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
                    <?php foreach ($latestUsers as $u): ?>
                      <tr>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td class="text-end"><span class="badge bg-secondary"><?= htmlspecialchars($u['role']) ?></span></td>
                        <td class="text-end small-muted"><?= htmlspecialchars($u['when']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <div class="mt-2 text-end"><a href="/admin/users.php" class="link-primary">Alle Benutzer</a></div>
            </div>
          </div>
        </div>

        <!-- Mini stats / health -->
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title">System-Health</h5>
              <div class="small-muted mb-2">Schnellüberblick</div>

              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="small-muted">CPU Load</div>
                <div class="small-muted">35%</div>
              </div>
              <div class="progress mb-3" style="height:10px"><div class="progress-bar" style="width:35%"></div></div>

              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="small-muted">DB Größe</div>
                <div class="small-muted">1.2 GB</div>
              </div>
              <div class="progress mb-1" style="height:10px"><div class="progress-bar bg-info" style="width:24%"></div></div>

              <div class="mt-3 small-muted">Letzte Backups: <?= htmlspecialchars($lastBackups) ?></div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Footer quick links -->
  <div class="row mt-4">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="small-muted">Schnellhilfe: <a href="/docs" class="link-primary">Dokumentation</a> · <a href="/forum" class="link-primary">Forum</a> · <a href="https://discord.gg/..." target="_blank" class="link-primary">Discord</a></div>
          <div class="small-muted">Version: <strong>2.0.0</strong></div>
        </div>
      </div>
    </div>
  </div>

</div>




    <!-- News -->
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

