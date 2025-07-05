<?php
use webspell\LanguageService;

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

// Benutzerdaten laden
$statement = $_database->prepare("SELECT username, lastlogin FROM users WHERE userID = ?");
$statement->bind_param('i', $userID);
$statement->execute();
$statement->bind_result($username, $lastlogin);
$statement->fetch();
$statement->close();

$lastlogin_formatted = (new DateTime($lastlogin))->format('d.m.Y \u\m H:i \U\h\r');

// Dashboard-Mockdaten
$nexpell_version = '1.0.3';
$update_available = true;









$latest_system_messages = [
    ['type' => 'warning', 'message' => 'Speicherplatz knapp!'],
    ['type' => 'info', 'message' => 'Backup erfolgreich abgeschlossen.']
];
$visitor_today = 123;
$visitor_last7days = 890;
$new_registrations = 5;
$active_users = 12;
$latest_comments = [
    ['user' => 'Anna', 'comment' => 'Tolles Update!', 'date' => '2025-07-04'],
    ['user' => 'Markus', 'comment' => 'Bug im Forum gemeldet', 'date' => '2025-07-03']
];
$latest_forum_posts = [
    ['title' => 'Neue Features', 'author' => 'Chris', 'date' => '2025-07-02'],
    ['title' => 'Feedback zur Beta', 'author' => 'Laura', 'date' => '2025-07-01']
];
$open_support_tickets = 3;

// News per API laden
$news_updates = [];
$json = @file_get_contents('https://nexpell.de/admin/api_news.php');
if ($json) {
    $news_updates = json_decode($json, true) ?? [];
} else {
    $news_updates = [];
}
?>

<div class="card mb-4 shadow-sm rounded-3">
  <div class="card-header d-flex align-items-center">
    <i class="bi bi-speedometer me-2"></i>
    <?php echo $languageService->get('title'); ?>
  </div>
  <div class="card-body">
  <div class="container mt-4">  
    <!-- Welcome Card -->
    <div class="card shadow-sm mb-4 border-0">
      <div class="card-body d-flex flex-column flex-md-row align-items-center gap-3">
        <img src="/admin/images/logo.png" alt="Logo" class="img-fluid" style="height:60px;">
        <div>
          <h5 class="mb-1"><?php echo $languageService->get('welcome'); ?></h5>
          <p class="mb-0 small text-muted">
            <?php echo $languageService->get('hello'); ?> <strong><?php echo $username; ?></strong>,
            <?php echo $languageService->get('last_login'); ?> <?php echo $lastlogin_formatted; ?>.
          </p>
          <p class="mb-0 mt-1">
            <?php echo $languageService->get('welcome_message'); ?>
          </p>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm border-0">
          <div class="card-body">
            <i class="bi bi-people-fill fs-2 text-primary mb-2"></i>
            <h6>Besucher heute</h6>
            <h3 class="fw-bold"><?php echo $visitor_today; ?></h3>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm border-0">
          <div class="card-body">
            <i class="bi bi-calendar3 fs-2 text-success mb-2"></i>
            <h6>Besucher letzte 7 Tage</h6>
            <h3 class="fw-bold"><?php echo $visitor_last7days; ?></h3>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm border-0">
          <div class="card-body">
            <i class="bi bi-person-plus-fill fs-2 text-info mb-2"></i>
            <h6>Neue Registrierungen</h6>
            <h3 class="fw-bold"><?php echo $new_registrations; ?></h3>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm border-0">
          <div class="card-body">
            <i class="bi bi-person-check-fill fs-2 text-warning mb-2"></i>
            <h6>Aktive Nutzer</h6>
            <h3 class="fw-bold"><?php echo $active_users; ?></h3>
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

    <!-- Version Info -->
    <h4 class="mb-3">Systemstatus</h4>
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <h5>Version <strong><?php echo $nexpell_version; ?></strong></h5>
          <?php if ($update_available): ?>
            <p class="text-warning mb-0">Update verfügbar!
              <a href="admincenter.php?site=update" class="fw-bold">Jetzt aktualisieren</a>
            </p>
          <?php else: ?>
            <p class="text-success mb-0">System ist aktuell</p>
          <?php endif; ?>
        </div>
        <i class="bi bi-arrow-repeat fs-2 text-primary"></i>
      </div>
    </div>

    <!-- Kommentare & Forenbeiträge -->
    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <h4 class="mb-2">Neueste Kommentare</h4>
        <div class="list-group shadow-sm">
          <?php if (!empty($latest_comments)): ?>
            <?php foreach ($latest_comments as $comment): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between">
                  <strong><?php echo htmlspecialchars($comment['user']); ?></strong>
                  <small class="text-muted"><?php echo $comment['date']; ?></small>
                </div>
                <p class="mb-0"><?php echo htmlspecialchars($comment['comment']); ?></p>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="list-group-item text-muted">Keine Kommentare verfügbar</div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-md-6">
        <h4 class="mb-2">Neueste Forenbeiträge</h4>
        <div class="list-group shadow-sm">
          <?php if (!empty($latest_forum_posts)): ?>
            <?php foreach ($latest_forum_posts as $post): ?>
              <a href="#" class="list-group-item list-group-item-action">
                <div class="d-flex justify-content-between">
                  <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                  <small class="text-muted">von <?php echo htmlspecialchars($post['author']); ?> am <?php echo $post['date']; ?></small>
                </div>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="list-group-item text-muted">Keine Forenbeiträge verfügbar</div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Systemmeldungen & Tickets -->
    <div class="row g-4">
      <div class="col-md-6">
        <h4 class="mb-2">Systemmeldungen</h4>
        <ul class="list-group shadow-sm">
          <?php if (!empty($latest_system_messages)): ?>
            <?php foreach ($latest_system_messages as $msg): ?>
              <li class="list-group-item <?php echo ($msg['type'] === 'warning') ? 'list-group-item-warning' : 'list-group-item-info'; ?>">
                <?php echo htmlspecialchars($msg['message']); ?>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="list-group-item text-muted">Keine Systemmeldungen verfügbar</li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="col-md-6">
        <h4 class="mb-2">Support-Tickets</h4>
        <div class="list-group shadow-sm">
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <span>Offene Tickets</span>
            <span class="badge rounded-pill bg-primary"><?php echo $open_support_tickets; ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
</div>

