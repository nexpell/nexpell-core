<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\AccessControl;
use nexpell\LanguageService;

global $languageService;

$lang = $languageService->detectLanguage();
$languageService->readModule('static');

$staticID = isset($_GET['staticID']) ? (int)$_GET['staticID'] : 0;

$ds = mysqli_fetch_array(safe_query("SELECT * FROM `settings_static` WHERE `staticID`='" . $staticID . "'"));

// Titel übersetzen
$title = $ds['title'];
$translate = new multiLanguage($lang);
$translate->detectLanguages($title);
$title = $translate->getTextByLanguage($title);

$config = mysqli_fetch_array(safe_query("SELECT selected_style FROM settings_headstyle_config WHERE id=1"));
$class = htmlspecialchars($config['selected_style']);

// Header-Daten
$data_array = [
    'class'    => $class,
    'title' => $title,
    'subtitle' => $title
];
echo $tpl->loadTemplate("static", "head", $data_array, 'theme');

// Rollen aus der DB (JSON-Feld) lesen
$allowedRoles = [];
if (!empty($ds['access_roles'])) {
    $allowedRoles = json_decode($ds['access_roles'], true);
}

if (empty($allowedRoles)) {
    $accessGranted = true; // Jeder darf sehen
} else {
    $accessGranted = AccessControl::hasAnyRole($allowedRoles);
}

if ($accessGranted) {
    // Inhalt übersetzen
    $content = $ds['content'];
    $translate->detectLanguages($content);
    $content = $translate->getTextByLanguage($content);

    $data_array = [
        'content' => $content
    ];
    echo $tpl->loadTemplate("static", "content", $data_array, 'theme');
} else {
    echo '<div class="alert alert-danger" role="alert">' . $languageService->get('no_access') . '</div>';
}
