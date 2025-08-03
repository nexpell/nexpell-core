<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Output-Buffering starten, damit keine Ausgabe vor XML kommt
ob_start();

require_once __DIR__ . '/system/config.inc.php';

// Datenbankverbindung
$_database = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($_database->connect_error) {
    http_response_code(500);
    exit;
}

// Header für XML setzen, davor alles löschen
header('Content-Type: application/xml; charset=utf-8');
if (ob_get_level() > 0) {
    ob_clean();
}

// Hilfsfunktion zum Escapen
function xmlEscape(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

// Aktive Sprachen laden
$languages = [];
$langResult = $_database->query("SELECT iso_639_1 FROM settings_languages WHERE active = 1");
if ($langResult) {
    while ($row = $langResult->fetch_assoc()) {
        $languages[] = $row['iso_639_1'];
    }
    $langResult->free();
}

// SEO-Link-Einstellung auslesen
$useSeoUrls = false;
$sysResult = $_database->query("SELECT use_seo_urls FROM settings LIMIT 1");
if ($sysResult) {
    $row = $sysResult->fetch_assoc();
    $useSeoUrls = ($row['use_seo_urls'] ?? '') === '1';
    $sysResult->free();
}

// Navigationseinträge laden
$urls = [];
$result = $_database->query("SELECT url, IFNULL(last_modified, NOW()) as last_modified FROM navigation_website_sub WHERE indropdown = 1");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $site = '';
        if (preg_match('/site=([a-zA-Z0-9_-]+)/', $row['url'], $matches)) {
            $site = $matches[1];
        } else {
            $site = trim($row['url'], '/');
        }

        if ($site === '') continue;

        foreach ($languages as $lang) {
            if ($useSeoUrls) {
                $loc = "https://www.nexpell.de/$lang/$site";
            } else {
                $loc = "https://www.nexpell.de/index.php?site=$site&lang=$lang";
            }
            $urls[$loc] = date('Y-m-d', strtotime($row['last_modified']));
        }
    }
    $result->free();
}

// Zusätzliche Seiten wie "seo"
$extraPages = [
    'seo' => ['priority' => '0.6', 'changefreq' => 'monthly'],
];

foreach ($extraPages as $site => $meta) {
    foreach ($languages as $lang) {
        if ($useSeoUrls) {
            $loc = "https://www.nexpell.de/$lang/$site";
        } else {
            $loc = "https://www.nexpell.de/index.php?site=$site&lang=$lang";
        }
        if (!isset($urls[$loc])) {
            $urls[$loc] = date('Y-m-d');
        }
    }
}

// ✅ XML-Ausgabe
/*$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $loc => $lastmod) {
    $basename = basename(parse_url($loc, PHP_URL_PATH));
    $priority = $extraPages[$basename]['priority'] ?? '0.8';
    $changefreq = $extraPages[$basename]['changefreq'] ?? 'monthly';

    $xml .= "  <url>\n";
    $xml .= "    <loc>" . xmlEscape($loc) . "</loc>\n";
    $xml .= "    <lastmod>$lastmod</lastmod>\n";
    $xml .= "    <changefreq>$changefreq</changefreq>\n";
    $xml .= "    <priority>$priority</priority>\n";
    $xml .= "  </url>\n";
}

$xml .= '</urlset>';

// Datei speichern, z.B. sitemap.xml im aktuellen Verzeichnis
file_put_contents(__DIR__ . '/sitemap.xml', $xml);

// Falls du die Sitemap auch direkt ausgeben willst, kannst du das tun:
header('Content-Type: application/xml; charset=utf-8');
echo $xml;
*/
// Sitemap generieren
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($urls as $loc => $lastmod) {
    $basename = basename(parse_url($loc, PHP_URL_PATH));
    $priority = $extraPages[$basename]['priority'] ?? '0.8';
    $changefreq = $extraPages[$basename]['changefreq'] ?? 'monthly';

    $xml .= "  <url>\n";
    $xml .= "    <loc>" . xmlEscape($loc) . "</loc>\n";
    $xml .= "    <lastmod>$lastmod</lastmod>\n";
    $xml .= "    <changefreq>$changefreq</changefreq>\n";
    $xml .= "    <priority>$priority</priority>\n";
    $xml .= "  </url>\n";
}

$xml .= '</urlset>';

// Datei speichern, z.B. sitemap.xml im aktuellen Verzeichnis
file_put_contents(__DIR__ . '/sitemap.xml', $xml);

// Falls du die Sitemap auch direkt ausgeben willst, kannst du das tun:
header('Content-Type: application/xml; charset=utf-8');
echo $xml;