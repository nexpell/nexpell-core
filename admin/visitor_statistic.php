<?php

use nexpell\LanguageService;
use nexpell\AccessControl;

// Session absichern
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Standard-Sprache setzen, wenn nicht vorhanden
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

// Initialisieren
global $languageService, $_database;
$languageService = new LanguageService($_database);

// Admin-Modul laden (stellt sicher, dass die Sprachdateien für dieses Modul geladen werden)
$languageService->readModule('visitor_statistic', true);

// Den Admin-Zugriff für das Modul prüfen
AccessControl::checkAdminAccess('ac_visitor_statistic');

// Daten aus der Datenbank abrufen
$time_limit = time() - 300; // 5 Minuten
$online_users_result = $_database->query("
    SELECT COUNT(DISTINCT ip_address) AS online_users
    FROM visitor_statistics
    WHERE UNIX_TIMESTAMP(created_at) > $time_limit
");
$online_users = (int) $online_users_result->fetch_assoc()['online_users'];

$visitors_today_result = $_database->query("
    SELECT COUNT(DISTINCT ip_address) AS visitors_today
    FROM visitor_statistics
    WHERE DATE(created_at) = CURDATE()
");
$visitors_today = (int) $visitors_today_result->fetch_assoc()['visitors_today'];

$visitors_yesterday_result = $_database->query("
    SELECT COUNT(DISTINCT ip_address) AS visitors_yesterday
    FROM visitor_statistics
    WHERE DATE(created_at) = CURDATE() - INTERVAL 1 DAY
");
$visitors_yesterday = (int) $visitors_yesterday_result->fetch_assoc()['visitors_yesterday'];

$visitors_week_result = $_database->query("
    SELECT COUNT(DISTINCT ip_address) AS visitors_week
    FROM visitor_statistics
    WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
");
$visitors_week = (int) $visitors_week_result->fetch_assoc()['visitors_week'];

// Top 10 Seiten
$top_pages = [];
$result_pages = $_database->query("
    SELECT page, COUNT(*) AS visits
    FROM visitor_statistics
    GROUP BY page
    ORDER BY visits DESC
    LIMIT 10
");
while ($row = $result_pages->fetch_assoc()) {
    $top_pages[] = $row;
}

// Top 10 Länder
$top_countries = [];
$result_countries = $_database->query("
    SELECT country_code, COUNT(DISTINCT ip_address) AS visitors
    FROM visitor_statistics
    GROUP BY country_code
    ORDER BY visitors DESC
    LIMIT 10
");
while ($row = $result_countries->fetch_assoc()) {
    $top_countries[] = $row;
}

// Top Browser
$top_browsers = [];
$result_browsers = $_database->query("
    SELECT browser, COUNT(*) AS count
    FROM visitor_statistics
    GROUP BY browser
    ORDER BY count DESC
    LIMIT 5
");
while ($row = $result_browsers->fetch_assoc()) {
    $top_browsers[] = $row;
}

// Top Geräte (Device)
$top_devices = [];
$result_devices = $_database->query("
    SELECT device, COUNT(*) AS count
    FROM visitor_statistics
    GROUP BY device
    ORDER BY count DESC
    LIMIT 5
");
while ($row = $result_devices->fetch_assoc()) {
    $top_devices[] = $row;
}

// Besucher pro Tag (letzte 30 Tage)
$daily_visitors = [];
$result_daily = $_database->query("
    SELECT DATE(created_at) AS date, COUNT(DISTINCT ip_address) AS visitors
    FROM visitor_statistics
    WHERE created_at >= CURDATE() - INTERVAL 30 DAY
    GROUP BY date
    ORDER BY date ASC
");
while ($row = $result_daily->fetch_assoc()) {
    $daily_visitors[] = $row;
}
?>

<div class="container-fluid py-4">

    <h1 class="mb-4 text-center"><?= $languageService->get('visitor_statistics'); ?></h1>

    <div class="row g-4 mb-5 text-white text-center">
        <div class="col-md-3">
            <div class="card bg-primary shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-center align-items-center">
                        <i class="bi bi-person-fill" style="font-size: 2rem;"></i>
                        <div class="ms-3">
                            <h5 class="card-title mb-0"><?= $languageService->get('online_users'); ?></h5>
                            <h1 class="display-4"><?= $online_users; ?></h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-center align-items-center">
                        <i class="bi bi-calendar-day-fill" style="font-size: 2rem;"></i>
                        <div class="ms-3">
                            <h5 class="card-title mb-0"><?= $languageService->get('visitors_today'); ?></h5>
                            <h1 class="display-4"><?= $visitors_today; ?></h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-center align-items-center">
                        <i class="bi bi-calendar-week-fill" style="font-size: 2rem;"></i>
                        <div class="ms-3">
                            <h5 class="card-title mb-0"><?= $languageService->get('visitors_week'); ?></h5>
                            <h1 class="display-4"><?= $visitors_week; ?></h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-center align-items-center">
                        <i class="bi bi-calendar-fill" style="font-size: 2rem;"></i>
                        <div class="ms-3">
                            <h5 class="card-title mb-0"><?= $languageService->get('visitors_yesterday'); ?></h5>
                            <h1 class="display-4"><?= $visitors_yesterday; ?></h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-12">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">📈 <?= $languageService->get('daily_visitors'); ?></h5>
                </div>
                <div class="card-body">
                    <canvas id="dailyVisitorsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">🌎 <?= $languageService->get('top_countries'); ?></h5>
                </div>
                <div class="card-body">
                    <canvas id="topCountriesChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">📄 <?= $languageService->get('top_pages'); ?></h5>
                </div>
                <div class="card-body">
                    <canvas id="topPagesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">🌐 <?= $languageService->get('top_browsers'); ?></h5>
                </div>
                <div class="card-body">
                    <canvas id="topBrowsersChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">💻 <?= $languageService->get('top_devices'); ?></h5>
                </div>
                <div class="card-body">
                    <canvas id="topDevicesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const backgroundColors = [
        'rgba(54, 162, 235, 0.7)', 'rgba(255, 99, 132, 0.7)', 'rgba(255, 206, 86, 0.7)',
        'rgba(75, 192, 192, 0.7)', 'rgba(153, 102, 255, 0.7)', 'rgba(255, 159, 64, 0.7)'
    ];

    const borderColors = [
        'rgba(54, 162, 235, 1)', 'rgba(255, 99, 132, 1)', 'rgba(255, 206, 86, 1)',
        'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)', 'rgba(255, 159, 64, 1)'
    ];

    // Besucher pro Tag Chart (Liniendiagramm)
    var ctxDaily = document.getElementById('dailyVisitorsChart').getContext('2d');
    new Chart(ctxDaily, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($daily_visitors, 'date')); ?>,
            datasets: [{
                label: '<?= $languageService->get('visitors_per_day'); ?>',
                data: <?= json_encode(array_column($daily_visitors, 'visitors')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Top Countries Chart (Kuchendiagramm)
    var ctxCountries = document.getElementById('topCountriesChart').getContext('2d');
    new Chart(ctxCountries, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($top_countries, 'country_code')); ?>,
            datasets: [{
                label: '<?= $languageService->get('visitors'); ?>',
                data: <?= json_encode(array_column($top_countries, 'visitors')); ?>,
                backgroundColor: backgroundColors,
                borderColor: '#fff',
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            return tooltipItem.label + ': ' + tooltipItem.raw + ' ' + '<?= $languageService->get('visitors'); ?>';
                        }
                    }
                }
            }
        }
    });

    // Top Pages Chart (Balkendiagramm)
    var ctxPages = document.getElementById('topPagesChart').getContext('2d');
    new Chart(ctxPages, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($top_pages, 'page')); ?>,
            datasets: [{
                label: '<?= $languageService->get('visits'); ?>',
                data: <?= json_encode(array_column($top_pages, 'visits')); ?>,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Top Browsers Chart (Balkendiagramm)
    var ctxBrowsers = document.getElementById('topBrowsersChart').getContext('2d');
    new Chart(ctxBrowsers, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($top_browsers, 'browser')); ?>,
            datasets: [{
                label: '<?= $languageService->get('visits'); ?>',
                data: <?= json_encode(array_column($top_browsers, 'count')); ?>,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Top Devices Chart (Balkendiagramm)
    var ctxDevices = document.getElementById('topDevicesChart').getContext('2d');
    new Chart(ctxDevices, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($top_devices, 'device')); ?>,
            datasets: [{
                label: '<?= $languageService->get('visits'); ?>',
                data: <?= json_encode(array_column($top_devices, 'count')); ?>,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>