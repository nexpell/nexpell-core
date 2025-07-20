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
$languageService->readModule('visitor_statistic', true);

use nexpell\AccessControl;

// Admin-Zugriff für das Modul prüfen
AccessControl::checkAdminAccess('ac_visitor_statistic');



// Aktuell Online
$time_limit = time() - 300; // 5 Minuten
$result = $_database->query("
    SELECT COUNT(DISTINCT ip_address) AS online_users
    FROM visitor_statistics
    WHERE UNIX_TIMESTAMP(created_at) > $time_limit
");
$online_users = (int) $result->fetch_assoc()['online_users'];

// Besucher heute
$result_today = $_database->query("
    SELECT COUNT(DISTINCT ip_address) AS visitors_today
    FROM visitor_statistics
    WHERE DATE(created_at) = CURDATE()
");
$visitors_today = (int) $result_today->fetch_assoc()['visitors_today'];

// Besucher gestern
$result_yesterday = $_database->query("
    SELECT COUNT(DISTINCT ip_address) AS visitors_yesterday
    FROM visitor_statistics
    WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY
");
$visitors_yesterday = (int) $result_yesterday->fetch_assoc()['visitors_yesterday'];

// Besucher diese Woche
$result_week = $_database->query("
    SELECT COUNT(DISTINCT ip_address) AS visitors_week
    FROM visitor_statistics
    WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
");
$visitors_week = (int) $result_week->fetch_assoc()['visitors_week'];

// Top 10 Seiten
$top_pages = [];
$result = $_database->query("
    SELECT page, COUNT(*) AS visits
    FROM visitor_statistics
    GROUP BY page
    ORDER BY visits DESC
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $top_pages[] = $row;
}

// Top 10 Länder
$top_countries = [];
$result = $_database->query("
    SELECT country_code, COUNT(*) AS visitors
    FROM visitor_statistics
    GROUP BY country_code
    ORDER BY visitors DESC
    LIMIT 10
");
while ($row = $result->fetch_assoc()) {
    $top_countries[] = $row;
}
?>

<!-- Frontend Ausgabe -->

<div class="container py-4">

    <h1 class="mb-4"><?php echo $languageService->get('visitor_statistics'); ?></h1>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary mb-3">
                <div class="card-header"><?php echo $languageService->get('online_users'); ?></div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $online_users; ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success mb-3">
                <div class="card-header"><?php echo $languageService->get('visitors_today'); ?></div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $visitors_today; ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning mb-3">
                <div class="card-header"><?php echo $languageService->get('visitors_yesterday'); ?></div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $visitors_yesterday; ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info mb-3">
                <div class="card-header"><?php echo $languageService->get('visitors_week'); ?></div>
                <div class="card-body">
                    <h5 class="card-title"><?php echo $visitors_week; ?></h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Top Seiten -->
    <div class="card mb-4">
        <div class="card-header"><?php echo $languageService->get('top_pages'); ?></div>
        <div class="card-body">
            <canvas id="topPagesChart"></canvas>
        </div>
    </div>

    <!-- Chart Top Länder -->
    <div class="card mb-4">
        <div class="card-header"><?php echo $languageService->get('top_countries'); ?></div>
        <div class="card-body">
            <canvas id="topCountriesChart"></canvas>
        </div>
    </div>

</div>

<table class="table table-striped">
  <thead>
    <tr>
      <th>Land</th>
      <th>Besucher</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><img src="flags/de.png" style="width:24px;height:16px;"> Deutschland</td>
      <td>123</td>
    </tr>
    <tr>
      <td><img src="flags/us.png" style="width:24px;height:16px;"> USA</td>
      <td>98</td>
    </tr>
  </tbody>
</table>

<!-- Chart.js einbinden -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Top Pages Chart
var ctxPages = document.getElementById('topPagesChart').getContext('2d');
var topPagesChart = new Chart(ctxPages, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($top_pages, 'page')); ?>,
        datasets: [{
            label: '<?php echo $languageService->get('visits'); ?>',
            data: <?php echo json_encode(array_column($top_pages, 'visits')); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)'
        }]
    }
});

// Top Countries Chart
var ctxCountries = document.getElementById('topCountriesChart').getContext('2d');
var topCountriesChart = new Chart(ctxCountries, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($top_countries, 'country_code')); ?>,
        datasets: [{
            label: '<?php echo $languageService->get('visitors'); ?>',
            data: <?php echo json_encode(array_column($top_countries, 'visitors')); ?>,
            backgroundColor: 'rgba(255, 206, 86, 0.7)'
        }]
    }
});
</script>
