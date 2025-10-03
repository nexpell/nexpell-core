<?php
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
    <!-- iOS -->
    <meta name="apple-mobile-web-app-capable" content="yes">

    <!-- Android / moderne Browser -->
    <meta name="mobile-web-app-capable" content="yes">
    
    <meta name="format-detection" content="telephone=no">

    <!-- Standard Favicon -->
    <link rel="icon" href="/includes/themes/default/images/favicon.ico" type="image/x-icon">

    <!-- PNG-Favicons für verschiedene Auflösungen -->
    <link rel="icon" type="image/png" sizes="32x32" href="/includes/themes/default/images/favicon-32.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/includes/themes/default/images/favicon-192.png">

    <!-- Apple Touch Icon (iOS) -->
    <link rel="apple-touch-icon" sizes="180x180" href="/includes/themes/default/images/favicon-180.png">


    <base href="/">

    <!-- 1. Bootstrap CSS des aktuellen Themes -->
    <link rel="stylesheet" href="/includes/themes/<?= htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8'); ?>/css/dist/<?= htmlspecialchars($currentTheme, ENT_QUOTES, 'UTF-8'); ?>/bootstrap.min.css"/>

    <?php
        // 2. Core-Komponenten CSS
        // Wird dynamisch über $components_css geladen aus der /system/settings.php
        echo $components_css ?? '';

        // 3. Theme-spezifische CSS-Dateien
        // Hier können Themes eigene Styles einbinden
        echo $theme_css ?? '';

        // 4. Struktur-Kommentar im HTML: Beginn der Plugin/Widget-CSS
        echo '<!--Plugin & Widget css-->' . PHP_EOL;

        // 5. Plugin- & Widget-CSS dynamisch laden
        // Jedes Plugin/Widget kann eigene Styles registrieren
        echo $plugin_loadheadfile_widget_css ?? '';

        echo '<link type="text/css" rel="stylesheet" href="./components/scrolltotop/css/scrolltotop.css" />';

        // 6. Hauptstylesheet des Themes
        // Überschreibt ggf. vorherige Styles und enthält globale Anpassungen
        echo '<link rel="stylesheet" href="/includes/themes/' . htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8') . '/css/stylesheet.css" />';
    ?>

</head>

<body>
<div class="d-flex flex-column sticky-footer-wrapper">
    <!-- Widgets: Top -->
    <?php if (!empty($widgetsByPosition['top'])): ?>
    <?php foreach ($widgetsByPosition['top'] as $widget): ?>                
        <?= $widget ?>
    <?php endforeach; ?>
<?php endif; ?>

    <?= get_navigation_modul(); ?>
    <?= get_lock_modul(); ?>
