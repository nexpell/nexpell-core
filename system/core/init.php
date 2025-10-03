<?php
/**
 * ─────────────────────────────────────────────────────────────────────────────
 * nexpell 1.0 - Modern Content & Community Management System
 * ─────────────────────────────────────────────────────────────────────────────
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use webspell\LanguageService;
use nexpell\SeoUrlHandler;
// Jetzt kannst du SeoUrlHandler verwenden, z.B.
SeoUrlHandler::route();


// Sprache aus Session laden oder Standard setzen
if (isset($_SESSION['language'])) {
    $currentLang = $_SESSION['language'];
} else {
    $currentLang = 'de';
    $_SESSION['language'] = $currentLang;
}
$languageService->setLanguage($currentLang);

// LanguageService initialisieren
if (!isset($languageService)) {
    $languageService = new LanguageService($_database);
}
$languageService->setLanguage($lang);
$_language = $languageService;

// Aktuelle Seite bestimmen
$page = $_GET['site'] ?? ($segments[1] ?? 'index');

// Theme laden
$result = safe_query("SELECT * FROM settings_themes WHERE modulname = 'default'");
$row = mysqli_fetch_assoc($result);
$currentTheme = $row['themename'] ?? 'lux';
$theme_name = 'default';

// SEO/Meta-Fallbacks
$description = $description ?? 'Standard Beschreibung für die Webseite';
$keywords = $keywords ?? 'keyword1, keyword2, keyword3';

// Wichtige Includes
require_once BASE_PATH . '/system/widget.php'; // enthält renderWidget()

// SQL-escaped Seitenname
$page_escaped = mysqli_real_escape_string($GLOBALS['_database'], $page);

// Widgets laden
$positions = [];
$res = safe_query("SELECT * FROM settings_widgets_positions WHERE page='" . $page_escaped . "' ORDER BY position, sort_order ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $positions[$row['position']][] = $row['widget_key'];
}

if (!empty($positions)) {
    foreach ($positions as $pos => $widgetKeys) {
        foreach ($widgetKeys as $widget_key) {
            loadWidgetHeadAssets($widget_key);
        }
    }
}

loadPluginHeadAssets();

require_once BASE_PATH . '/system/seo_meta_helper.php';
$site = $_GET['site'] ?? 'index';
$meta = getSeoMeta($site);
// Ausgabe $_GET zum Debug
/*var_dump($_GET);
echo '<pre>';
print_r($_GET);
echo '</pre>';*/

// Live-Visitor Tracking
$currentSite = $site ?? 'index';
live_visitor_track($currentSite);


$allPositions = ['top', 'undertop', 'left', 'maintop', 'mainbottom', 'right', 'bottom'];
$widgetsByPosition = [];

foreach ($allPositions as $position) {
    $widgetsByPosition[$position] = [];
    if (!empty($positions[$position])) {
        foreach ($positions[$position] as $widget_key) {
            $output = renderWidget($widget_key);
            if (!empty(trim($output))) {
                $widgetsByPosition[$position][] = $output;
            }
        }
    }
}
?>
