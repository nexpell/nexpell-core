<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $_database;

$availableLangs = ['de', 'en', 'it'];

// Sprache aus URL oder Session oder DB ermitteln
if (isset($_GET['new_lang']) && in_array($_GET['new_lang'], $availableLangs)) {
    $lang = $_GET['new_lang'];
    $_SESSION['language'] = $lang;
} elseif (isset($_SESSION['language']) && in_array($_SESSION['language'], $availableLangs)) {
    $lang = $_SESSION['language'];
} else {
    $result = $_database->query("SELECT default_language FROM settings LIMIT 1");
    if ($result && $row = $result->fetch_assoc() && !empty($row['default_language'])) {
        $lang = $row['default_language'];
    } else {
        $lang = 'de';
    }
    $_SESSION['language'] = $lang;
}

// Aktueller Pfad
#$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$currentPath = $_SERVER['REQUEST_URI'];

// Funktion zum Ersetzen der Sprache im Pfad
/*function replaceLangInUrl(string $url, string $newLang, array $allowedLangs): string {
    $parsed = parse_url($url);
    $path = $parsed['path'] ?? '/';

    parse_str($parsed['query'] ?? '', $parsed);

    $parsed['new_lang'] = $newLang;

    $segments = explode('/', trim($path, '/'));

    if (isset($segments[0]) && in_array($segments[0], $allowedLangs)) {
        $segments[0] = $newLang;
    } else {
        array_unshift($segments, $newLang);
    }

    $newPath = '/' . implode('/', $segments);

    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

    return $newPath . $query;
}*/

function replaceLangInUrl(string $url, string $newLang, array $allowedLangs): string {
    $parsed = parse_url($url);

    $path = $parsed['path'] ?? '/';
    parse_str($parsed['query'] ?? '', $params);

    $params['new_lang'] = $newLang;

    // Wenn SEO aktiv
    if (defined('USE_SEO_URLS') && USE_SEO_URLS) {
        $segments = explode('/', trim($path, '/'));

        if (isset($segments[0]) && in_array($segments[0], $allowedLangs)) {
            $segments[0] = $newLang;
        } else {
            array_unshift($segments, $newLang);
        }

        $newPath = '/' . implode('/', $segments);
        $query = http_build_query(array_diff_key($params, ['site' => '', 'action' => '', 'id' => '', 'page' => '', 'anchor' => '', 'fragment' => '']));

        return $newPath . ($query ? '?' . $query : '');
    }

    // SEO aus: normaler Query-Link
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
