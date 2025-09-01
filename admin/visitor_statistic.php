<?php

use nexpell\LanguageService;
use nexpell\AccessControl;

// Session absichern
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Standardsprache setzen
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

// Sprachservice initialisieren
global $languageService,$_database;;
$languageService = new LanguageService($_database);
$languageService->readModule('visitor_statistic', true);

// Admin-Zugriff prüfen
AccessControl::checkAdminAccess('ac_visitor_statistic');

// Zeitraum (week oder month)
// Range aus GET oder default
$range = $_GET['range'] ?? 'week';
if (!in_array($range, ['week','month','6months','12months'])) {
    $range = 'week';
}

// Tage bzw. Monate für Statistik-Berechnung
switch ($range) {
    case 'week':
        $days = 7;
        $since_date = date('Y-m-d', strtotime("-{$days} days"));
        break;

    case 'month':
        $days = 30;
        $since_date = date('Y-m-d', strtotime("-{$days} days"));
        break;

    case '6months':
        $days = 180; // ca. 6 Monate
        $since_date = date('Y-m-d', strtotime("-6 months"));
        break;

    case '12months':
        $days = 365; // ca. 12 Monate
        $since_date = date('Y-m-d', strtotime("-12 months"));
        break;

    default:
        $days = 7;
        $since_date = date('Y-m-d', strtotime("-7 days"));
        break;
}

// Besucher pro Tag (für Line-Chart)
$labels = [];
$visits = [];
$result = $_database->query("
    SELECT DATE(created_at) AS day, COUNT(DISTINCT ip_address) AS visits
    FROM visitor_statistics
    WHERE created_at >= '$since_date'
    GROUP BY day
    ORDER BY day ASC
");
while ($row = $result->fetch_assoc()) {
    $labels[] = $row['day'];
    $visits[] = (int)$row['visits'];
}

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

// Gesamtbesuche
$res_total = mysqli_fetch_assoc(safe_query(
    "SELECT COUNT(*) AS total FROM visitor_statistics"
));
$visitors_total = (int)$res_total['total'];

// Top 10 Seiten nach Klicks
$res_clicks = safe_query(
    "SELECT page, COUNT(*) AS total FROM visitor_statistics GROUP BY page ORDER BY total DESC LIMIT 10"
);
$top_pages = [];
while ($row = mysqli_fetch_assoc($res_clicks)) {
    $top_pages[] = $row;
}

$res_unique = mysqli_fetch_assoc(safe_query(
  "SELECT COUNT(DISTINCT ip_hash) AS unique_visitors FROM visitor_statistics WHERE created_at >= '$since_date'"
));
$unique_visitors = (int)$res_unique['unique_visitors'];

$avg_per_day = round($visitors_total / max($days, 1), 1);

$active_since = date('Y-m-d H:i:s', strtotime('-10 minutes'));
$res_online = mysqli_fetch_assoc(safe_query(
  "SELECT COUNT(DISTINCT ip_hash) AS online_visitors FROM visitor_statistics WHERE created_at >= '$active_since'"
));
$online_visitors = (int)$res_online['online_visitors'];

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


// Geräte-Auswertung
$res_devices = safe_query(
    "SELECT device_type, COUNT(*) AS total FROM visitor_statistics WHERE created_at >= '$since_date' GROUP BY device_type"
);
$device_data = [];
while ($row = mysqli_fetch_assoc($res_devices)) {
    $device_data[$row['device_type']] = (int)$row['total'];
}

// OS-Auswertung
$res_os = safe_query(
    "SELECT os, COUNT(*) AS total FROM visitor_statistics WHERE created_at >= '$since_date' GROUP BY os"
);
$os_data = [];
while ($row = mysqli_fetch_assoc($res_os)) {
    $os_data[$row['os']] = (int)$row['total'];
}

// Browser-Auswertung
$res_browser = safe_query(
    "SELECT browser, COUNT(*) AS total FROM visitor_statistics WHERE created_at >= '$since_date' GROUP BY browser"
);
$browser_data = [];
while ($row = mysqli_fetch_assoc($res_browser)) {
    $browser_data[$row['browser']] = (int)$row['total'];
}

// Top-Referer
$res_referer = safe_query(
    "SELECT referer, COUNT(*) AS hits FROM visitor_statistics WHERE created_at >= '$since_date' GROUP BY referer ORDER BY hits DESC LIMIT 5"
);
$top_referers = [];
while ($row = mysqli_fetch_assoc($res_referer)) {
    $top_referers[] = $row;
}


// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="visits_export.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Datum', 'IP (hash)', 'Gerät', 'OS', 'Browser', 'Referer']);

    $res_export = safe_query("SELECT * FROM visitor_statistics WHERE created_at >= '$since_date' ORDER BY created_at ASC");
    while ($row = mysqli_fetch_assoc($res_export)) {
        fputcsv($output, [
            $row['timestamp'],
            $row['ip_hash'],
            $row['device_type'],
            $row['os'],
            $row['browser'],
            $row['referer']
        ]);
    }
    fclose($output);
    exit;
}

// Beispiel: tägliche Besucher der letzten 6 Monate
$six_months_visitors = [];
for ($i = 180; $i >= 0; $i--) { // ca. 6 Monate = 180 Tage
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = (int)$_database->query("SELECT COUNT(*) AS cnt FROM visitor_statistics WHERE DATE(created_at) = '$date'")->fetch_assoc()['cnt'];
    $six_months_visitors[] = ['date' => $date, 'visitors' => $count];
}

// 12 Monate aggregiert nach Monat
$twelve_months_visitors = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $count = (int)$_database->query("SELECT COUNT(*) AS cnt FROM visitor_statistics WHERE DATE_FORMAT(created_at,'%Y-%m') = '$month'")->fetch_assoc()['cnt'];
    $twelve_months_visitors[] = ['month' => $month, 'visitors' => $count];
}

// Seit wann die Webseite läuft (erstes Statistikdatum)
$result_start = $_database->query("
    SELECT MIN(created_at) AS first_visit
    FROM visitor_statistics
");
$first_visit = $result_start->fetch_assoc()['first_visit'];
$first_visit_date = $first_visit ? date('d.m.Y', strtotime($first_visit)) : 'unbekannt';
















// Sprachlabels
$visitsLabel   = $languageService->get('visits');
$visitorsLabel = $languageService->get('visitors');
?>

<!-- Frontend Ausgabe -->
<div class="card">
    <div class="card-header">
        <?= $languageService->get('visitor_statistics'); ?>
    </div>
    <div class="card-body">
        <div class="container py-4">

    <h5 class="mb-4 text-center"><?= $languageService->get('visitor_statistics'); ?></h5>
  <div class="row g-3">

  <div class="col-md-6 col-xl-3">
    <div class="card bg-primary text-white">
      <div class="card-body">
        <h6><?php echo $languageService->get('online_users'); ?></h6>
        <h4 class="text-right">
          <i class="bi bi-person-check float-start"></i>
          <span class="ms-3"><?php echo $online_users; ?></span>
        </h4>
        <p class="mb-0">Gerade eingeloggt<span class="float-end"><?php echo $online_users; ?></span></p>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-3">
    <div class="card bg-success text-white">
      <div class="card-body">
        <h6>Besucher heute</h6>
        <h4 class="text-right">
          <i class="bi bi-calendar float-start"></i>
          <span class="ms-3"><?= $visitors_today ?></span>
        </h4>
        <p class="mb-0">Neu heute<span class="float-end"><?php echo $visitors_today; ?></span></p>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-3">
    <div class="card bg-warning text-white">
      <div class="card-body">
        <h6><?php echo $languageService->get('visitors_yesterday'); ?></h6>
        <h4 class="text-right">
          <i class="bi bi-calendar2 float-start"></i>
          <span class="ms-3"><?php echo $visitors_yesterday; ?></span>
        </h4>
        <p class="mb-0">Gestern<span class="float-end"><?php echo $visitors_yesterday; ?></span></p>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-3">
    <div class="card bg-info text-white">
      <div class="card-body">
        <h6><?php echo $languageService->get('visitors_week'); ?></h6>
        <h4 class="text-right">
          <i class="bi bi-calendar3-week float-start"></i>
          <span class="ms-3"><?php echo $visitors_week; ?></span>
        </h4>
        <p class="mb-0">Diese Woche<span class="float-end"><?php echo $visitors_week; ?></span></p>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-3">
    <div class="card bg-danger text-white">
      <div class="card-body">
        <h6>Gesamtbesuche</h6>
        <h4 class="text-right">
          <i class="bi bi-people float-start"></i>
          <span class="ms-3"><?php echo $visitors_total; ?></span>
        </h4>
        <p class="mb-0">Eindeutige Besucher<span class="float-end"><?php echo $unique_visitors; ?></span></p>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-3">
    <div class="card bg-secondary text-white">
      <div class="card-body">
        <h6>Ø Besuche pro Tag</h6>
        <h4 class="text-right">
          <i class="bi bi-bar-chart-line float-start"></i>
          <span class="ms-3"><?php echo $avg_per_day; ?></span>
        </h4>
        <p class="mb-0">Im Durchschnitt<span class="float-end"><?= round($avg_per_day, 2) ?></span></p>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-3">
    <div class="card bg-dark text-white">
      <div class="card-body">
        <h6>Besucher online</h6>
        <h4 class="text-right">
          <i class="bi bi-clock float-start"></i>
          <span class="ms-3"><?php echo $online_visitors; ?></span>
        </h4>
        <p class="mb-0">Letzte 10 Min.<span class="float-end"><?php echo $online_visitors; ?></span></p>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-xl-3">
    <div class="card bg-light">
      <div class="card-body">
        <h6>Webseite online seit</h6>
        <h4 class="text-right">
          <i class="bi bi-clock float-start"></i>
          <span class="ms-3"><?= $first_visit_date ?></span>
        </h4>
        <p class="mb-0">Erste Statistik<span class="float-end"><?= $first_visit_date ?></span></p>
      </div>
    </div>
  </div>

</div>

<div class="card mb-4">
    <div class="card-header">Besucherstatistik</div>
    <div class="card-body">

  <form method="get" action="admincenter.php" class="mb-4 d-flex justify-content-between align-items-center" style="max-width: 400px;">
    <input type="hidden" name="site" value="visitor_statistic">
        <select name="range" class="form-select" onchange="this.form.submit()">
        <option value="week" <?= ($range === 'week' ? 'selected' : '') ?>>Letzte 7 Tage</option>
        <option value="month" <?= ($range === 'month' ? 'selected' : '') ?>>Letzte 30 Tage</option>
        <option value="6months" <?= ($range === '6months' ? 'selected' : '') ?>>Letzte 6 Monate</option>
        <option value="12months" <?= ($range === '12months' ? 'selected' : '') ?>>Letzte 12 Monate</option>
    </select>
    <a href="?site=visitor_statistic&range=<?= htmlspecialchars($range) ?>&export=csv"
       class="btn btn-outline-secondary ms-3"
       title="CSV Export"
       style="min-width: 180px;">
      <i class="bi bi-file-earmark-arrow-down"></i> CSV-Export
    </a>
  </form>

  <h3>Besucher pro Tag</h3>
  <canvas id="visitorsChart" height="100"></canvas>

  </div></div>

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

















    <div class="row">

                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Gerätetypen</h5>
                            <canvas id="deviceChart" height="150"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Betriebssysteme</h5>
                            <canvas id="osChart" height="150"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Browser</h5>
                            <canvas id="browserChart" height="150"></canvas>
                        </div>
                    </div>
                </div>
    </div>            

    <div class="row mt-4">
        <div class="col-md-4">
            <h4>Geräte</h4>
            <ul class="list-group">
                <?php foreach ($device_data as $device => $count): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($device) ?>
                        <span class="badge bg-info rounded-pill"><?php echo $count; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col-md-4">
            <h4>Betriebssysteme</h4>
            <ul class="list-group">
                <?php foreach ($os_data as $os => $count): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($os) ?>
                        <span class="badge bg-info rounded-pill"><?php echo $count; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col-md-4">
            <h4>Browser</h4>
            <ul class="list-group">
                <?php foreach ($browser_data as $browser => $count): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($browser) ?>
                        <span class="badge bg-info rounded-pill"><?php echo $count; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <h3 class="mt-4">Top 10 Seiten nach Klicks</h3>
    <ul class="list-group mb-4">
        <?php foreach ($top_pages as $page): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($page['page']) ?>
                <span class="badge bg-secondary rounded-pill"><?php echo $page['visits']; ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <h4 class="mt-4">Top 5 Referer</h4>
    <ul class="list-group mb-4">
        <?php foreach ($top_referers as $referer): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <?= htmlspecialchars($referer['referer']) ?>
                <span class="badge bg-warning rounded-pill"><?php echo $referer['hits']; ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>




</div></div>








<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart Options Funktion
const chartOptions = (yLabel = '') => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            enabled: true,
            callbacks: {
                label: function(context) {
                    return context.parsed.y + ' ' + yLabel;
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: { stepSize: 1 }
        }
    }
});

// Besucher (Line Chart)
const visitorsCtx = document.getElementById('visitorsChart').getContext('2d');
new Chart(visitorsCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: <?= json_encode($visitsLabel) ?>,
            data: <?= json_encode($visits) ?>,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            tension: 0.3,
            fill: true,
            pointRadius: 3
        }]
    },
    options: chartOptions(<?= json_encode($visitsLabel) ?>)
});

// Top Pages (Bar Chart)
const ctxPages = document.getElementById('topPagesChart').getContext('2d');
new Chart(ctxPages, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($top_pages, 'page')) ?>,
        datasets: [{
            label: <?= json_encode($visitsLabel) ?>,
            data: <?= json_encode(array_column($top_pages, 'visits')) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)'
        }]
    },
    options: chartOptions(<?= json_encode($visitsLabel) ?>)
});

// Top Countries (Bar Chart)
const ctxCountries = document.getElementById('topCountriesChart').getContext('2d');
new Chart(ctxCountries, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($top_countries, 'country_code')) ?>,
        datasets: [{
            label: <?= json_encode($visitorsLabel) ?>,
            data: <?= json_encode(array_column($top_countries, 'visitors')) ?>,
            backgroundColor: 'rgba(255, 206, 86, 0.7)'
        }]
    },
    options: chartOptions(<?= json_encode($visitorsLabel) ?>)
});


// Gerätetypen Diagramm
const deviceChart = new Chart(document.getElementById('deviceChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($device_data)) ?>,
        datasets: [{
            label: 'Gerätetypen',
            data: <?= json_encode(array_values($device_data)) ?>,
            backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0']
        }]
    }
});

// Betriebssysteme Diagramm
const osChart = new Chart(document.getElementById('osChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($os_data)) ?>,
        datasets: [{
            label: 'Betriebssysteme',
            data: <?= json_encode(array_values($os_data)) ?>,
            backgroundColor: ['#FF6384', '#36A2EB', '#9966FF', '#FFCE56']
        }]
    }
});

// Browser Diagramm
const browserChart = new Chart(document.getElementById('browserChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($browser_data)) ?>,
        datasets: [{
            label: 'Browser',
            data: <?= json_encode(array_values($browser_data)) ?>,
            backgroundColor: ['#4BC0C0', '#FF6384', '#36A2EB', '#FFCE56']
        }]
    }
});

</script>

<style>
canvas {
    width: 100% !important;
    max-height: 400px;
}
</style>
