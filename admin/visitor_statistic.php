<?php

use nexpell\LanguageService;
use nexpell\AccessControl;

// Session absichern
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



require_once '../system/visitor_log_statistic.php';

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
if (!in_array($range, ['week', 'month', '6months', '12months'])) {
    $range = 'week';
}

$labels = [];
$visitors = [];
$maxonline_values = [];

switch ($range) {
    case 'week':
        // letzte 7 Tage inkl. heute
        $since_date = date('Y-m-d', strtotime("-6 days"));
        $end_date   = date('Y-m-d');

        $day_pointer = strtotime($since_date);
        $days_array = [];
        while ($day_pointer <= strtotime($end_date)) {
            $key = date('Y-m-d', $day_pointer);
            $days_array[$key] = ['hits' => 0, 'maxonline' => 0];
            $day_pointer += 86400;
        }

        $result = $_database->query("
            SELECT DATE(date) as day, SUM(hits) as count, MAX(maxonline) as maxpeak
            FROM visitor_daily_counter
            WHERE date BETWEEN '$since_date' AND '$end_date'
            GROUP BY day
            ORDER BY day ASC
        ");
        while ($row = $result->fetch_assoc()) {
            $days_array[$row['day']] = ['hits' => (int)$row['count'], 'maxonline' => (int)$row['maxpeak']];
        }

        foreach ($days_array as $day => $values) {
            $labels[] = date('D', strtotime($day));
            $visitors[] = $values['hits'];
            $maxonline_values[] = $values['maxonline'];
        }
        break;

    case 'month':
        // letzte 30 Tage inkl. heute
        $since_date = date('Y-m-d', strtotime("-29 days"));
        $end_date   = date('Y-m-d');

        $day_pointer = strtotime($since_date);
        $days_array = [];
        while ($day_pointer <= strtotime($end_date)) {
            $key = date('Y-m-d', $day_pointer);
            $days_array[$key] = ['hits' => 0, 'maxonline' => 0];
            $day_pointer += 86400;
        }

        $result = $_database->query("
            SELECT DATE(date) as day, SUM(hits) as count, MAX(maxonline) as maxpeak
            FROM visitor_daily_counter
            WHERE date BETWEEN '$since_date' AND '$end_date'
            GROUP BY day
            ORDER BY day ASC
        ");
        while ($row = $result->fetch_assoc()) {
            $days_array[$row['day']] = ['hits' => (int)$row['count'], 'maxonline' => (int)$row['maxpeak']];
        }

        foreach ($days_array as $day => $values) {
            $labels[] = date('d.m.', strtotime($day));
            $visitors[] = $values['hits'];
            $maxonline_values[] = $values['maxonline'];
        }
        break;

    case '6months':
        // letzte 6 Monate inkl. aktueller
        $since_date = date('Y-m-01', strtotime("-5 months"));
        $end_date   = date('Y-m-01', strtotime("+1 month"));

        $months_array = [];
        $month_pointer = strtotime($since_date);
        while ($month_pointer < strtotime($end_date)) {
            $key = date('Y-m-01', $month_pointer);
            $months_array[$key] = ['hits' => 0, 'maxonline' => 0];
            $month_pointer = strtotime('+1 month', $month_pointer);
        }

        $result = $_database->query("
            SELECT DATE_FORMAT(date, '%Y-%m-01') as month_start, SUM(hits) as count, MAX(maxonline) as maxpeak
            FROM visitor_daily_counter
            WHERE date >= '$since_date'
            GROUP BY month_start
            ORDER BY month_start ASC
        ");
        while ($row = $result->fetch_assoc()) {
            $months_array[$row['month_start']] = ['hits' => (int)$row['count'], 'maxonline' => (int)$row['maxpeak']];
        }

        foreach ($months_array as $month => $values) {
            $labels[] = date('M Y', strtotime($month));
            $visitors[] = $values['hits'];
            $maxonline_values[] = $values['maxonline'];
        }
        break;

    case '12months':
        // letzte 12 Monate inkl. aktueller
        $since_date = date('Y-m-01', strtotime("-11 months"));
        $end_date   = date('Y-m-01', strtotime("+1 month"));

        $months_array = [];
        $month_pointer = strtotime($since_date);
        while ($month_pointer < strtotime($end_date)) {
            $key = date('Y-m-01', $month_pointer);
            $months_array[$key] = ['hits' => 0, 'maxonline' => 0];
            $month_pointer = strtotime('+1 month', $month_pointer);
        }

        $result = $_database->query("
            SELECT DATE_FORMAT(date, '%Y-%m-01') as month_start, SUM(hits) as count, MAX(maxonline) as maxpeak
            FROM visitor_daily_counter
            WHERE date >= '$since_date'
            GROUP BY month_start
            ORDER BY month_start ASC
        ");
        while ($row = $result->fetch_assoc()) {
            $months_array[$row['month_start']] = ['hits' => (int)$row['count'], 'maxonline' => (int)$row['maxpeak']];
        }

        foreach ($months_array as $month => $values) {
            $labels[] = date('M Y', strtotime($month));
            $visitors[] = $values['hits'];
            $maxonline_values[] = $values['maxonline'];
        }
        break;
}





###############################

$time_limit = time() - 300; // 5 Minuten
$result = $_database->query("
    SELECT COUNT(DISTINCT ip_address) AS online_users
    FROM visitor_statistics
    WHERE UNIX_TIMESTAMP(created_at) > $time_limit
");
$online_users = (int) $result->fetch_assoc()['online_users'];

// --- Besucherstatistiken berechnen ---










function getVisitorCounter(mysqli $_database): array {
    $bot_condition    = getBotCondition(); // deine bestehende Funktion
    $today_date       = date('Y-m-d');
    $yesterday        = date('Y-m-d', strtotime('-1 day'));
    $month_start      = date('Y-m-01');
    $five_minutes_ago = time() - 300;

    // Heute (Hits aus daily_counter)
    $today_hits = (int)$_database->query("
        SELECT SUM(hits) AS hits
        FROM visitor_daily_counter
        WHERE DATE(date) = '$today_date'
    ")->fetch_assoc()['hits'];

    // Gestern
    $yesterday_hits = (int)$_database->query("
        SELECT SUM(hits) AS hits
        FROM visitor_daily_counter
        WHERE DATE(date) = '$yesterday'
    ")->fetch_assoc()['hits'];

    // Monat
    $month_hits = (int)$_database->query("
        SELECT SUM(hits) AS hits
        FROM visitor_daily_counter
        WHERE date >= '$month_start'
    ")->fetch_assoc()['hits'];

    // Gesamt
    $total_hits = (int)$_database->query("
        SELECT SUM(hits) AS hits
        FROM visitor_daily_counter
    ")->fetch_assoc()['hits'];

    // Online (letzte 5 Minuten, Bots raus)
    $online_visitors = (int)$_database->query("
        SELECT COUNT(DISTINCT ip_hash) AS cnt
        FROM visitor_statistics
        WHERE last_seen >= FROM_UNIXTIME($five_minutes_ago) $bot_condition
    ")->fetch_assoc()['cnt'];

    // MaxOnline (aus daily_counter)
    $max_online = (int)$_database->query("
        SELECT MAX(maxonline) AS maxcnt
        FROM visitor_daily_counter
    ")->fetch_assoc()['maxcnt'];

    // Seit wann die Webseite läuft (erstes Statistikdatum)
    // Erstes und letztes Statistikdatum holen
    $result_range = $_database->query("
        SELECT MIN(date) AS first_visit, MAX(date) AS last_visit
        FROM visitor_daily_counter
    ");
    $range = $result_range->fetch_assoc();

    $first_visit = $range['first_visit'];
    $last_visit  = $range['last_visit'];

    $first_visit_date = $first_visit ? date('d.m.Y', strtotime($first_visit)) : 'unbekannt';

    // Anzahl Tage seit Start berechnen (inkl. Starttag)
    $days = ($first_visit && $last_visit)
        ? (floor((strtotime($last_visit) - strtotime($first_visit)) / 86400) + 1)
        : 0;

    // Durchschnitt pro Tag berechnen
    $avg_per_day = $days > 0 ? round($total_hits / $days, 1) : 0;

    // Durchschnitt pro Tag
    $result_days = $_database->query("
        SELECT DATEDIFF(MAX(date), MIN(date)) + 1 AS days
        FROM visitor_daily_counter
    ");
    $days = (int)$result_days->fetch_assoc()['days'];
    $avg_per_day = round($total_hits / max($days, 1), 1);

    return [
        'today'           => $today_hits,
        'yesterday'       => $yesterday_hits,
        'month'           => $month_hits,
        'total'           => $total_hits,
        'online'          => $online_visitors,
        'maxonline'       => $max_online,
        'first_visit'     => $first_visit_date,
        'average_per_day' => $avg_per_day,
        'days'           => $days
    ];
}




$counter = getVisitorCounter($_database);





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

// Top 10 Seiten nach Klicks
$res_clicks = safe_query(
    "SELECT page, COUNT(*) AS total FROM visitor_statistics GROUP BY page ORDER BY total DESC LIMIT 10"
);
$top_pages = [];
while ($row = mysqli_fetch_assoc($res_clicks)) {
    $top_pages[] = $row;
}

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




// Sprachlabels
$visitsLabel   = $languageService->get('visits');
$visitorsLabel = $languageService->get('visitors');
?>

<div class="card">
    <div class="card-header">
        <?= $languageService->get('visitor_statistics'); ?>
    </div>
    <div class="card-body">
        <div class="container py-4">

            <h5 class="mb-4 text-center"><?= $languageService->get('visitor_statistics'); ?></h5>
            <div class="row g-3">
                <!-- Online Users -->
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6><?php echo $languageService->get('online_users'); ?></h6>
                            <h4 class="text-right">
                                <i class="bi bi-person-check float-start"></i>
                                <span class="ms-3"><?= $counter['online'] ?></span>
                            </h4>
                            <p class="mb-0">Gerade eingeloggt<span class="float-end"><?= $counter['online'] ?></span></p>
                        </div>
                    </div>
                </div>

                <!-- Besucher heute -->
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6>Besucher heute</h6>
                            <h4 class="text-right">
                                <i class="bi bi-calendar float-start"></i>
                                <span class="ms-3"><?= $counter['today'] ?></span>
                            </h4>
                            <p class="mb-0">Neu heute<span class="float-end"><?= $counter['today'] ?></span></p>
                        </div>
                    </div>
                </div>

                <!-- Besucher gestern -->
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6><?php echo $languageService->get('visitors_yesterday'); ?></h6>
                            <h4 class="text-right">
                                <i class="bi bi-calendar2 float-start"></i>
                                <span class="ms-3"><?= $counter['yesterday'] ?></span>
                            </h4>
                            <p class="mb-0">Gestern<span class="float-end"><?= $counter['yesterday'] ?></span></p>
                        </div>
                    </div>
                </div>

                <!-- Besucher diese Woche -->
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6><?php echo $languageService->get('visitors_week'); ?> Monat</h6>
                            <h4 class="text-right">
                                <i class="bi bi-calendar3-week float-start"></i>
                                <span class="ms-3"><?= $counter['month'] ?></span>
                            </h4>
                            <p class="mb-0">Diesen Monat<span class="float-end"><?= $counter['month'] ?></span></p>
                        </div>
                    </div>
                </div>

                <!-- Gesamtbesuche -->
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6>Gesamtbesuche</h6>
                            <h4 class="text-right">
                                <i class="bi bi-people float-start"></i>
                                <span class="ms-3"><?= $counter['total'] ?></span>
                            </h4>
                            <p class="mb-0">Eindeutige Besucher<span class="float-end"><?= $counter['total'] ?></span></p>
                        </div>
                    </div>
                </div>

                <!-- Durchschnitt pro Tag -->
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <h6>Ø Besuche pro Tag</h6>
                            <h4 class="text-right">
                                <i class="bi bi-bar-chart-line float-start"></i>
                                <span class="ms-3"><?= $counter['average_per_day'] ?></span>
                            </h4>
                            <p class="mb-0">Im Durchschnitt<span class="float-end"><?= $counter['average_per_day'] ?></span></p>
                        </div>
                    </div>
                </div>

                <!-- Online letzte 10 Minuten -->
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-dark text-white">
                        <div class="card-body">
                            <h6>Besucher online</h6>
                            <h4 class="text-right">
                                <i class="bi bi-clock float-start"></i>
                                <span class="ms-3"><?= $counter['online'] ?></span>
                            </h4>
                            <p class="mb-0">Letzte 10 Min.<span class="float-end"><?= $counter['online'] ?></span></p>
                        </div>
                    </div>
                </div>

                <!-- Erste Statistik -->
                <div class="col-md-6 col-xl-3">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6>Webseite online seit</h6>
                            <h4 class="text-right">
                                <i class="bi bi-clock float-start"></i>
                                <span class="ms-3"><?= $counter['first_visit'] ?></span>
                            </h4>
                            <p class="mb-0">Erste Statistik<span class="float-end"><?= $counter['first_visit'] ?> / Tage online: <?= $counter['days'] ?></span></p>
                        </div>
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

  <h3>Aufrufe pro Tag</h3>
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
// Besucher + MaxOnline (Line Chart)
const visitorsCtx = document.getElementById('visitorsChart').getContext('2d');
new Chart(visitorsCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            {
                label: <?= json_encode($visitsLabel) ?>,
                data: <?= json_encode($visitors) ?>,
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.3,
                fill: true,
                pointRadius: 3,
                yAxisID: 'yHits'
            },
            {
                label: 'MaxOnline',
                data: <?= json_encode($maxonline_values) ?>,
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.3,
                fill: false,
                pointRadius: 3,
                yAxisID: 'yMax'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                enabled: true,
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y;
                    }
                }
            }
        },
        scales: {
            yHits: {
                type: 'linear',
                position: 'left',
                beginAtZero: true,
                title: { display: true, text: 'Hits' }
            },
            yMax: {
                type: 'linear',
                position: 'right',
                beginAtZero: true,
                title: { display: true, text: 'MaxOnline' },
                grid: { drawOnChartArea: false } // linke Y-Achse nicht überschreiben
            }
        }
    }
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
