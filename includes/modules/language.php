<?php

/*if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $_database;

$availableLangs = ['de', 'en', 'it'];

// Sprache aus URL oder Session oder DB ermitteln
if (isset($_GET['new_lang']) && in_array($_GET['new_lang'], $availableLangs)) {
    $lang = $_GET['new_lang'];
    $_SESSION['language'] = $lang;

    // Nach Sprachwahl -> Weiterleitung auf gleiche URL ohne `new_lang`
    $redirectUrl = $_SERVER['REQUEST_URI'];

    // `new_lang` aus Query entfernen
    $redirectUrl = preg_replace('/([&?])new_lang=' . $lang . '(&|$)/', '$1', $redirectUrl);
    $redirectUrl = rtrim($redirectUrl, '?&'); // trailing ? oder & entfernen

    header("Location: $redirectUrl");
    exit;
}*/


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $_database;

// Sprache aus DB laden
$availableLangs = [];
$languageData = [];

$result = $_database->query("SELECT * FROM settings_languages WHERE active = 1 ORDER BY name_en ASC");
if (!$result) {
    die("Fehler bei der Sprachabfrage: " . $_database->error);
}

while ($row = $result->fetch_assoc()) {
    $code = $row['iso_639_1'];
    $availableLangs[] = $code;
    $languageData[$code] = $row;
}

// Sprache aus `new_lang`, Session oder Default aus settings
if (isset($_GET['new_lang']) && in_array($_GET['new_lang'], $availableLangs)) {
    $lang = $_GET['new_lang'];
    $_SESSION['language'] = $lang;

    // Redirect ohne `new_lang`, um Redirect-Loop zu verhindern
    $redirectUrl = $_SERVER['REQUEST_URI'];
    $redirectUrl = preg_replace('/([&?])new_lang=' . $lang . '(&|$)/', '$1', $redirectUrl);
    $redirectUrl = rtrim($redirectUrl, '?&'); // ? oder & am Ende entfernen

    header("Location: $redirectUrl");
    exit;
} elseif (isset($_SESSION['language']) && in_array($_SESSION['language'], $availableLangs)) {
    $lang = $_SESSION['language'];
} else {
    // Fallback: Standard aus Settings-Tabelle
    $defaultResult = $_database->query("SELECT default_language FROM settings LIMIT 1");
    $row = $defaultResult ? $defaultResult->fetch_assoc() : null;
    $lang = ($row && in_array($row['default_language'], $availableLangs)) ? $row['default_language'] : 'de';
    $_SESSION['language'] = $lang;
}




// Aktueller Pfad
#$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$currentPath = $_SERVER['REQUEST_URI'];

function replaceLangInUrl(string $url, string $newLang, array $allowedLangs): string {
    $parsed = parse_url($url);

    $path = $parsed['path'] ?? '/';
    parse_str($parsed['query'] ?? '', $params);

    // Immer neue Sprache setzen
    $params['new_lang'] = $newLang;

    // SEO aktiviert und kein direkter index.php-Aufruf
    if (
        defined('USE_SEO_URLS') && USE_SEO_URLS &&
        strpos($path, 'index.php') === false
    ) {
        // Sprachpräfix im Pfad ersetzen
        $segments = explode('/', trim($path, '/'));
        if (in_array($segments[0], $allowedLangs)) {
            $segments[0] = $newLang; // vorhandene Sprache ersetzen
        } else {
            array_unshift($segments, $newLang); // Sprache voranstellen
        }

        $newPath = '/' . implode('/', $segments);
        $query = http_build_query($params);
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $newPath . ($query ? '?' . $query : '') . $fragment;
    }

    // Wenn index.php verwendet wird: klassisch weiterleiten
    return '/index.php?' . http_build_query($params);
}




// Sprachen aus DB laden
$query = "SELECT iso_639_1, name_native, name_en, flag FROM settings_languages WHERE active = 1 ORDER BY name_en ASC";
$result = $_database->query($query);
if (!$result) die("Fehler bei der Abfrage: " . $_database->error);

$lang_ok = '';
$language_links = '';
$flag_ok = '';

while ($row = $result->fetch_assoc()) {
    $short = $row['iso_639_1'];
    $flag = $row['flag'] ?: "/admin/images/flags/{$short}.png";
    $name = $row['name_native'] ?: ($row['name_en'] ?: ucfirst($short));

    
    $url = replaceLangInUrl($currentPath, $short, $availableLangs);

    if ($short === $lang) {
        $lang_ok = '<a class="dropdown-item active" href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($name) . '">'
            . '<span class="flag" style="background-image: url(\'' . htmlspecialchars($flag) . '\');"></span> '
            . htmlspecialchars($name) . ' <i class="bi bi-check2 text-success" style="font-size: 1rem;"></i></a>';
        $flag_ok = '<span class="flag" style="background-image: url(\'' . htmlspecialchars($flag) . '\');"></span>';
    } else {
        $language_links .= '<a class="dropdown-item" href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($name) . '">'
            . '<span class="flag" style="background-image: url(\'' . htmlspecialchars($flag) . '\');"></span> '
            . htmlspecialchars($name) . '</a>';
    }
}

// Daten an Template übergeben
$data_array = [
    'flag_ok' => $flag_ok,
    'languages_ok' => $lang_ok,
    'languages' => $language_links,
];

echo $tpl->loadTemplate("navigation", "languages", $data_array, 'theme');
