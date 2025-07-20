<?php
/**
 * ─────────────────────────────────────────────────────────────────────────────
 * nexpell 1.0 - Modern Content & Community Management System
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * @version       1.0
 * @build         Stable Release
 * @release       2025
 * @copyright     © 2025 nexpell | https://www.nexpell.de
 * 
 * @description   nexpell is a modern open source CMS designed for gaming
 *                communities, esports teams, and digital projects of any kind.
 * 
 * @author        The nexpell Team
 * 
 * @license       GNU General Public License (GPL)
 *                This software is distributed under the terms of the GPL.
 *                It is strictly prohibited to remove this copyright notice.
 *                For license details, see: https://www.gnu.org/licenses/gpl.html
 * 
 * @support       Support, updates, and plugins available at:
 *                → Website: https://www.nexpell.de
 *                → Forum:   https://www.nexpell.de/forum.html
 *                → Wiki:    https://www.nexpell.de/wiki.html
 * 
 * ─────────────────────────────────────────────────────────────────────────────
 */

// Session starten, falls noch nicht gestartet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use webspell\LanguageService;

// Initialisiere LanguageService, falls nicht schon vorhanden
if (!isset($languageService)) {
    $languageService = new LanguageService($_database);
}

if (isset($_GET['new_lang'])) {
    $_SESSION['language'] = $_GET['new_lang'];

    // Wenn site in GET vorhanden → redirect nach /site
    if (isset($_GET['site']) && !empty($_GET['site'])) {
        $site = basename($_GET['site']);
        header("Location: /$site");
        exit;
    }

    // Wenn URI wie /contact?new_lang=en → redirect nach /contact ohne Parameter
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (!empty($currentPath) && $currentPath !== '/') {
        header("Location: $currentPath");
        exit;
    }

    // fallback zur Startseite
    header("Location: /");
    exit;
}

// Sprache aus Session laden oder Standard setzen
if (isset($_SESSION['language'])) {
    $currentLang = $_SESSION['language'];
} else {
    $currentLang = 'de';
    $_SESSION['language'] = $currentLang;
}
$languageService->setLanguage($currentLang);

// $_language synchronisieren
$_language = $languageService;

// Theme aus DB laden
$result = safe_query("SELECT * FROM settings_themes WHERE modulname = 'default'");
$row = mysqli_fetch_assoc($result);
$currentTheme = $row['themename'] ?? 'lux';
$theme_name = 'default';

// SEO/Meta-Fallbacks
$description = $description ?? 'Standard Beschreibung für die Webseite';
$keywords = $keywords ?? 'keyword1, keyword2, keyword3';

// Verbindungs-Setup und wichtige Includes
require_once './system/widget.php'; // Enthält renderWidget()

// Seitenname für Widgets-Abfrage
$page = isset($_GET['site']) ? $_GET['site'] : 'index';

// SQL-Escape für $page
$page_escaped = mysqli_real_escape_string($GLOBALS['_database'], $page);

// Widgets Positionen aus DB holen
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

// Header-Kompatibilität
header('X-UA-Compatible: IE=edge');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($languageService->detectLanguage(), ENT_QUOTES, 'UTF-8') ?>">

<head>
    <!-- Meta Basics -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Meta SEO -->
    <meta name="description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($keywords, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index, follow">
    <meta name="language" content="<?php echo htmlspecialchars($_language->detectLanguage(), ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="abstract" content="Anpasser an Webspell-RM">

    <!-- Meta Copyright -->
    <meta name="author" content="webspell-rm.de">
    <meta name="copyright" content="Copyright © 2018-2025 by webspell-rm.de">
    <meta name="publisher" content="webspell-rm.de">
    <meta name="distribution" content="global">

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars(get_sitetitle(), ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8'); ?>/includes/themes/<?php echo htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8'); ?>/images/og-image.jpg">

    <!-- iOS Fix -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">

    <link rel="SHORTCUT ICON" href="./includes/themes/<?php echo htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8'); ?>/images/favicon.ico">

    <!-- Head & Title -->
    <title><?php echo htmlspecialchars(get_sitetitle(), ENT_QUOTES, 'UTF-8'); ?></title>
    <base href="/">

<link id="bootstrap-css" rel="stylesheet" href="./includes/themes/<?php echo htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8'); ?>/css/dist/<?php echo htmlspecialchars($currentTheme, ENT_QUOTES, 'UTF-8'); ?>/bootstrap.min.css"/>

<link type="application/rss+xml" rel="alternate" href="tmp/rss.xml" title="<?php echo htmlspecialchars($myclanname ?? 'My Clan', ENT_QUOTES, 'UTF-8'); ?> - RSS Feed">
<link type="text/css" rel="stylesheet" href="./components/cookies/css/cookieconsent.css" media="print" onload="this.media='all'">
<link type="text/css" rel="stylesheet" href="./components/cookies/css/iframemanager.css" media="print" onload="this.media='all'">
<link type="text/css" rel="stylesheet" href='/includes/plugins/navigation/css/navigation.css'>

<?php
$lang = $_language->detectLanguage();
echo $components_css ?? '';
echo $theme_css ?? '';
echo '<!--Plugin & Widget css-->' . PHP_EOL;
echo $plugin_loadheadfile_widget_css ?? '';
?>
<link type="text/css" rel="stylesheet" href='/includes/plugins/footer_easy/css/footer_easy.css'>
<!--Plugin & Widget css END-->
<link type="text/css" rel="stylesheet" href="./includes/themes/<?php echo htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8'); ?>/css/stylesheet.css" />
</head>
<body>

<div class="d-flex flex-column sticky-footer-wrapper">

    <!-- Top Widgets -->
    <?php if (!empty($positions['top'])): ?>
    
            <?php foreach ($positions['top'] as $widget_key) {
                echo renderWidget($widget_key);
            } ?>
        
    <?php endif; ?>

    <?php echo get_navigation_modul(); ?>
    <?php echo get_lock_modul(); ?>

    <!-- under Top Widgets -->
    <?php if (!empty($positions['undertop'])): ?>
    
            <?php foreach ($positions['undertop'] as $widget_key) {
                echo renderWidget($widget_key);
            } ?>
       
    <?php endif; ?>

    <main class="flex-fill">
        <div class="container">
            <div class="row">
            
                <?php if (!empty($positions['left'])): ?>
                    <div class="col-md-3">
                        <?php foreach ($positions['left'] as $widget_key): ?>
                            <?php echo renderWidget($widget_key); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="col">

                    <?php if (!empty($positions['maintop'])): ?>
                        <div class="col">
                            <?php foreach ($positions['maintop'] as $widget_key): ?>
                                <?php echo renderWidget($widget_key); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php echo get_mainContent(); ?>

                    <?php if (!empty($positions['mainbottom'])): ?>
                        <div class="col">
                            <?php foreach ($positions['mainbottom'] as $widget_key): ?>
                                <?php echo renderWidget($widget_key); ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($positions['right'])): ?>
                    <div class="col-md-3">
                        <?php foreach ($positions['right'] as $widget_key): ?>
                            <?php echo renderWidget($widget_key); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <!-- Bottom Widgets -->
    <?php if (!empty($positions['bottom'])): ?>
    
            <?php foreach ($positions['bottom'] as $widget_key) {
                echo renderWidget($widget_key);
            } ?>
        
    <?php endif; ?>

    <?php echo get_footer_modul(); ?>

</div>

<div class="scroll-top-wrapper">
    <span class="scroll-top-inner">
        <i class="bi bi-arrow-up-circle" style="font-size: 2rem;" aria-label="Nach oben scrollen"></i>
    </span>
</div>

<div class="cookies-wrapper">
    <span class="cookies-top-inner">
        <i class="bi bi-gear" style="font-size: 2rem;" id="cookie-settings-icon" data-toggle="tooltip" data-bs-title="Cookie-Einstellungen"></i>
    </span>
</div>

<script defer src="https://www.google.com/recaptcha/api.js"></script>
<?php
echo $components_js ?? '';
echo $theme_js ?? '';
echo '<!--Plugin & Widget js-->' . PHP_EOL;
echo $plugin_loadheadfile_widget_js ?? '';
echo '<!--Plugin & Widget js END-->' . PHP_EOL;
?>

<!-- Cookie Consent Banner -->
<div id="cookie-overlay" style="
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 9998;
    display: none;
    pointer-events: auto;
"></div>

<?php include 'cookie-consent.php'; ?>
<!-- Cookie Consent Banner END -->

<script src="./components/ckeditor/ckeditor.js"></script>
<script src="./components/ckeditor/config.js"></script>

<!-- Language recognition for DataTables -->
<script>
    const LangDataTables = '<?php echo htmlspecialchars($_language->detectLanguage(), ENT_QUOTES, 'UTF-8'); ?>';
</script>

<!-- Bootstrap Form Validation -->
<script type="text/javascript">
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>
</body>
</html>
