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
require_once './system/widget.php'; // enthält renderWidget()

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

require_once __DIR__ . '/../../../system/seo_meta_helper.php';
$site = $_GET['site'] ?? 'home';
$meta = getSeoMeta($site);
// Ausgabe $_GET zum Debug
/*var_dump($_GET);
echo '<pre>';
print_r($_GET);
echo '</pre>';*/

// Header-Kompatibilität
header('X-UA-Compatible: IE=edge');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8') ?>">

<head>

    <!-- Head & Title -->
    <title><?= htmlspecialchars($meta['title']) ?></title>

    <!-- Meta Basics -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Meta SEO -->
    <meta name="description" content="<?= htmlspecialchars($meta['description']); ?>">
    <meta name="keywords" content="<?= htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index, follow">
    <meta name="language" content="<?= htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="abstract" content="Anpasser an nexpell">

    <!-- Meta Copyright -->
    <meta name="author" content="nexpell.de">
    <meta name="copyright" content="Copyright © 2018-2025 by nexpell.de">
    <meta name="publisher" content="nexpell.de">
    <meta name="distribution" content="global">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($meta['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta['description']); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?= htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="https://<?= htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8'); ?>/includes/themes/<?= htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8'); ?>/images/og-image.jpg">

    <!-- iOS Fix -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">

    <link rel="SHORTCUT ICON" href="./includes/themes/<?= htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8'); ?>/images/favicon.ico">
    <base href="/">

    <!-- Stylesheets -->
    <link rel="stylesheet" href="./includes/themes/<?= htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8'); ?>/css/dist/<?= htmlspecialchars($currentTheme, ENT_QUOTES, 'UTF-8'); ?>/bootstrap.min.css"/>
    <link rel="stylesheet" href="tmp/rss.xml" title="<?= htmlspecialchars($myclanname ?? 'My Clan', ENT_QUOTES, 'UTF-8'); ?> - RSS Feed">
    <link rel="stylesheet" href="./components/cookies/css/cookieconsent.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="./components/cookies/css/iframemanager.css" media="print" onload="this.media='all'">
    <link rel="stylesheet" href='/includes/plugins/navigation/css/navigation.css'>

    <?= $components_css ?? '' ?>
    <?= $theme_css ?? '' ?>
    <?= '<!--Plugin & Widget css-->' . PHP_EOL ?>
    <?= $plugin_loadheadfile_widget_css ?? '' ?>
    <link rel="stylesheet" href='/includes/plugins/footer_easy/css/footer_easy.css'>
    <link rel="stylesheet" href="./includes/themes/<?= htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8'); ?>/css/stylesheet.css" />
</head>

<body>
<div class="d-flex flex-column sticky-footer-wrapper">

    <!-- Widgets: Top -->
    <?php if (!empty($positions['top'])): ?>
        <?php foreach ($positions['top'] as $widget_key) echo renderWidget($widget_key); ?>
    <?php endif; ?>

    <?= get_navigation_modul(); ?>
    <?= get_lock_modul(); ?>

    <!-- Widgets: underTop -->
    <?php if (!empty($positions['undertop'])): ?>
        <?php foreach ($positions['undertop'] as $widget_key) echo renderWidget($widget_key); ?>
    <?php endif; ?>

    <main class="flex-fill">
        <div class="container">
            <div class="row">
                <?php if (!empty($positions['left'])): ?>
                    <div class="col-md-3">
                        <?php foreach ($positions['left'] as $widget_key) echo renderWidget($widget_key); ?>
                    </div>
                <?php endif; ?>

                <div class="col">
                    <?php if (!empty($positions['maintop'])): ?>
                        <div class="col">
                            <?php foreach ($positions['maintop'] as $widget_key) echo renderWidget($widget_key); ?>
                        </div>
                    <?php endif; ?>

                    <?= get_mainContent(); ?>

                    <?php if (!empty($positions['mainbottom'])): ?>
                        <div class="col">
                            <?php foreach ($positions['mainbottom'] as $widget_key) echo renderWidget($widget_key); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($positions['right'])): ?>
                    <div class="col-md-3">
                        <?php foreach ($positions['right'] as $widget_key) echo renderWidget($widget_key); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Widgets: Bottom -->
    <?php if (!empty($positions['bottom'])): ?>
        <?php foreach ($positions['bottom'] as $widget_key) echo renderWidget($widget_key); ?>
    <?php endif; ?>

    <?= get_footer_modul(); ?>
</div>
<!-- Scroll Top Button -->
<div class="scroll-top-wrapper">
    <span class="scroll-top-inner">
        <i class="bi bi-arrow-up-circle" style="font-size: 2rem;" aria-label="Nach oben scrollen"></i>
    </span>
</div>

<!-- Cookie Settings Button -->
<div class="cookies-wrapper">
    <span class="cookies-top-inner">
        <i class="bi bi-gear" style="font-size: 2rem;" id="cookie-settings-icon" data-toggle="tooltip" data-bs-title="Cookie-Einstellungen"></i>
    </span>
</div>

<!-- Scripts -->
<script defer src="https://www.google.com/recaptcha/api.js"></script>
<?= $components_js ?? '' ?>
<?= $theme_js ?? '' ?>
<?= '<!--Plugin & Widget js-->' . PHP_EOL ?>
<?= $plugin_loadheadfile_widget_js ?? '' ?>
<?= '<!--Plugin & Widget js END-->' . PHP_EOL ?>

<!-- Cookie Consent -->
<div id="cookie-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); z-index: 9998; display: none;"></div>
<?php include 'cookie-consent.php'; ?>

<script src="./components/ckeditor/ckeditor.js"></script>
<script src="./components/ckeditor/config.js"></script>

<!-- DataTables Sprache -->
<script>
    const LangDataTables = '<?= htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8'); ?>';
</script>

<!-- Bootstrap Form Validation -->
<script>
    (function () {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

</body>
</html>
