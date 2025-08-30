<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;
use nexpell\SeoUrlHandler;

global $languageService, $_database, $tpl;

$lang = $languageService->detectLanguage();

// Verfügbare Sprachen aus DB laden
$availableLangs = [];
$result = safe_query("SELECT iso_639_1 FROM settings_languages WHERE active = 1");
while ($row = mysqli_fetch_assoc($result)) {
    $availableLangs[] = $row['iso_639_1'];
}

$languageService->readModule('index');

$loggedin = isset($_SESSION['userID']) && $_SESSION['userID'] > 0;
$userID = $_SESSION['userID'] ?? 0;

// Funktion zur Navigation ohne Dropdown
function navigation_nodropdown($default_url) {
    global $_database, $languageService;

    $newurl = $default_url;
    $mr_res = mysqli_fetch_array(safe_query("SELECT * FROM `settings` WHERE 1 LIMIT 1"));
    if ($mr_res && isset($mr_res['modRewrite']) && $mr_res['modRewrite'] == 1) {
        $urlParts = explode("/", trim($_SERVER["REQUEST_URI"], "/"));
        if (!empty($urlParts[0])) {
            if (strpos($urlParts[0], '.') !== false) {
                $newurl = "index.php?site=" . htmlspecialchars(explode(".", $urlParts[0])[0]);
            } else {
                $newurl = "index.php?site=" . htmlspecialchars($urlParts[0]);
            }
        }
    }

    try {
        $escapedUrl = mysqli_real_escape_string($_database ?? null, $newurl);
        $rex = safe_query("SELECT * FROM `navigation_website_sub` WHERE `url`='" . $escapedUrl . "' LIMIT 1");
        if (mysqli_num_rows($rex)) {
            $output = "";
            $rox = mysqli_fetch_array($rex);
            $res = safe_query("SELECT * FROM `navigation_website_sub` WHERE `mnavID`='" . intval($rox['mnavID']) . "' AND `indropdown`='0' ORDER BY `sort`");
            while ($row = mysqli_fetch_array($res)) {
                $nameKey = strtolower($row['name']);
                $name = $languageService->module[$nameKey] ?? $row['name'];
                $url = htmlspecialchars($row['url']);
                $target = '';
                if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
                    $target = ' target="_blank" rel="noopener noreferrer"';
                }
                $output .= '<li class="nav-item"><a class="dropdown-item" href="' . $url . '"' . $target . '>' . htmlspecialchars($name) . '</a></li>';
            }
            return $output;
        }
        return '';
    } catch (Exception $e) {
        if (defined('DEBUG') && DEBUG === "ON") {
            return 'Fehler: ' . $e->getMessage();
        }
        return '';
    }
}

// Navigation Hauptstruktur
try {
    $res = safe_query("SELECT * FROM `navigation_website_main` ORDER BY `sort`");
    $lo = 0;

    while ($row = mysqli_fetch_array($res)) {
        $translate = new multiLanguage($lang);
        $translate->detectLanguages($row['name']);
        $name = $translate->getTextByLanguage($row['name']);

        $head_array = [
            'name' => $name,
            'url' => $row['url'],
            'windows' => $row['windows'] ? '' : '_blank',
        ];

        if ($row['isdropdown'] == 1) {
            if ($lo == 1) {
                $head_array['login_overview'] = $loggedin 
                    ? '<li class="nav-item"><a class="dropdown-item" href="index.php?site=loginoverview">' . ($languageService->module['overview'] ?? 'Overview') . '</a></li>' 
                    : '<li class="nav-item"><a class="dropdown-item" href="index.php?site=login">' . ($languageService->module['login'] ?? 'Login') . '</a></li>';
            } else {
                $head_array['login_overview'] = "";
            }
            $lo++;

            $rex = safe_query("SELECT * FROM `navigation_website_sub` WHERE `mnavID`='" . (int)$row['mnavID'] . "' AND `indropdown`='1' ORDER BY `sort`");
            if (mysqli_num_rows($rex)) {
                echo $tpl->loadTemplate("navigation", "dd_head", $head_array, 'theme');
                echo $tpl->loadTemplate("navigation", "sub_open", [], 'theme');

                while ($rox = mysqli_fetch_array($rex)) {
                    $translate->detectLanguages($rox['name']);
                    $sub_name = $translate->getTextByLanguage($rox['name']);
                    $sub_url = $rox['url'];

                    if (strpos($sub_url, 'index.php?site=') === 0) {
                        // URL ist bereits im Query-Format, einfach in SEO-URL umwandeln
                        $sub_url = SeoUrlHandler::convertToSeoUrl($sub_url);
                    } elseif (substr($sub_url, -4) === '.php' && !str_starts_with($sub_url, 'http')) {
                        // URL endet auf ".php" und ist kein externer Link,
                        // z.B. "forum.php" -> "index.php?site=forum" -> SEO-URL
                        $sub_url = SeoUrlHandler::convertToSeoUrl('index.php?site=' . basename($sub_url, '.php'));
                    }

                    $target = '';
                    if (strpos($sub_url, 'http://') === 0 || strpos($sub_url, 'https://') === 0) {
                        $target = '_blank';
                    }

                    $sub_array = [
                        'url' => $sub_url,
                        'name' => $sub_name,
                        'target' => $target
                    ];

                    echo $tpl->loadTemplate("navigation", "sub_nav", $sub_array, 'theme');
                }


                echo $tpl->loadTemplate("navigation", "sub_close", [], 'theme');
                echo $tpl->loadTemplate("navigation", "dd_foot", [], 'theme');
            }
        } else {
            $target = '';
            if (strpos($row['url'], 'http://') === 0 || strpos($row['url'], 'https://') === 0) {
                $target = '_blank';
            }
            $head_array['target'] = $target;
            echo $tpl->loadTemplate("navigation", "main_head", $head_array, 'theme');
        }
    }
} catch (Exception $e) {
    echo 'Fehler bei der Navigation: ' . $e->getMessage();
    return false;
}

// Login + Forum + Messenger + Avatar
if ($loggedin) {
    $icon = '';
    $l_avatar = getavatar($userID) ?: "noavatar.png";

    $dashboard = (checkUserRoleAssignment($userID, 1))
    ? '<li><a class="dropdown-item" href="' . htmlspecialchars('/admin/admincenter.php') . '" target="_blank">&nbsp;' . ($languageService->module['admincenter'] ?? 'Admin Center') . '</a></li>'
    : '';


    $urlString = 'index.php?site=profile&userID=' . intval($userID);
    $profile = '<li><a class="dropdown-item" href="' . 
    htmlspecialchars(SeoUrlHandler::convertToSeoUrl($urlString)) . 
    '">&nbsp;' . $languageService->module['to_profil'] . '</a></li>';

    $logoutUrl = 'index.php?' . http_build_query(['site' => 'logout']);
    $logout = '<li>
        <a class="dropdown-item" href="' . 
            htmlspecialchars(SeoUrlHandler::convertToSeoUrl($logoutUrl)) . 
            '">&nbsp;' . $languageService->module['log_off'] . '</a>
    </li>';

    $modulname = 'messenger';
    $result = $_database->query("SELECT COUNT(*) AS installed FROM settings_plugins_installed WHERE modulname = '$modulname'");
    $row = $result->fetch_assoc();

    if ($row['installed'] > 0) {
        // Plugin ist installiert – HTML-Link ausgeben
        $messenger = '<a class="nav-link messenger-link" href="index.php?site=messenger">
  <i id="mail-icon" class="bi bi-envelope-dash"></i>
  <span id="total-unread-badge">0</span>
</a>
';
    } else {
        $messenger = ''; // oder gar nichts ausgeben
    }

    $data_array = [
        'modulepath' => substr(MODULE, 0, -1),
        'l_avatar' => $l_avatar,
        'nickname' => getusername($userID),
        'profile' => $profile,
        'dashboard' => $dashboard,
        'logout' => $logout,
        'messenger' => $messenger,
        'lang_overview' => $languageService->module['overview'] ?? 'Übersicht',
        'my_account' => $languageService->module['my_account'] ?? 'Mein Konto',
    ];

    echo $tpl->loadTemplate("navigation", "login_loggedin", $data_array, 'theme');
} else {

    $login = '<li><a class="nav-link" href="' . htmlspecialchars(SeoUrlHandler::convertToSeoUrl('index.php?site=login')) . '">' . $languageService->module['login'] . '</a></li>';

    $data_array = [
        'modulepath' => substr(MODULE, 0, -1),
        'login' => $login
    ];

    echo $tpl->loadTemplate("navigation", "login_login", $data_array, 'theme');
}
