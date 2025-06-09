<?php

use webspell\LanguageService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $languageService, $_database, $tpl;

$lang = $languageService->detectLanguage();
#$languageService->readModule('navigation');
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
    #$dx = mysqli_fetch_array(safe_query("SELECT * FROM settings_plugins WHERE modulname='forum' AND activate=1"));
    $icon = '';

    /*if ($dx['modulname'] == 'forum') {
        $board_topics = [];
        $q = safe_query("SELECT * FROM plugins_forum_topics");
        while ($lp = mysqli_fetch_assoc($q)) {
            $board_topics[] = $lp['topicID'];
        }

        $ergebnisz = safe_query("SELECT topics FROM user WHERE userID='$userID'");
        $gv = mysqli_fetch_array($ergebnisz);

        $icon = '<a data-toggle="tooltip" data-placement="bottom" title="' . $languageService->module['no_forum_post'] . '" href="index.php?site=forum">
                    <span class="icon badge bg-light text-dark mt-0 position-relative">
                        <i class="bi bi-chat"></i>
                    </span>
                 </a>';

        if (!empty($gv['topics'])) {
            $topic = explode("|", $gv['topics']);
            if (is_array($topic)) {
                $n = 1;
                foreach ($topic as $topics) {
                    if ($topics != "") {
                        $badgeNumber = min($n, 10);
                        $badgeLabel = ($badgeNumber == 10) ? "10+" : $badgeNumber;

                        $icon = '<a data-toggle="tooltip" data-placement="bottom" title="' . $languageService->module['more_new_forum_post'] . '" href="index.php?site=forum">
                                    <span class="badge bg-warning text-dark mt-0 position-relative">
                                        <i class="bi bi-chat-dots"></i>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            ' . $badgeLabel . '
                                            <span class="visually-hidden">unread messages</span>
                                        </span>
                                    </span>
                                 </a>';
                        $n++;
                    }
                }
            }
        }
    }*/

    $l_avatar = getavatar($userID) ?: "noavatar.png";

    /*$dx = mysqli_fetch_array(safe_query("SELECT * FROM settings_plugins WHERE modulname='messenger' AND activate=1"));
    if ($dx['modulname'] == 'messenger') {
        $newmessagesCount = getnewmessages($userID);
        $badgeNumber = min($newmessagesCount, 10);
        $badgeLabel = ($badgeNumber == 10) ? "10+" : $badgeNumber;

        if ($newmessagesCount > 0) {
            $newmessages = '<a data-toggle="tooltip" data-placement="bottom" title="' . ($newmessagesCount == 1 ? $languageService->module['one_new_message'] : $languageService->module['more_new_message']) . '" href="index.php?site=messenger">
                                <span class="icon badge text-bg-success position-relative">
                                    <i class="bi bi-envelope-check"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        ' . $badgeLabel . '
                                        <span class="visually-hidden">unread messages</span>
                                    </span>
                                </span>
                            </a>';
        } else {
            $newmessages = '<a data-toggle="tooltip" data-placement="bottom" title="' . $languageService->module['no_new_messages'] . '" href="index.php?site=messenger">
                                <span class="icon badge text-bg-light position-relative">
                                    <i class="bi bi-envelope"></i>
                                </span>
                            </a>';
        }
    } else {
        $newmessages = '';
    }*/

    $dashboard = (checkUserRoleAssignment($userID, 1))
        ? '<li><a class="dropdown-item" href="admin/admincenter.php" target="_blank">&nbsp;' . $languageService->module['admincenter'] . '</a></li>'
        : '';

    $data_array = [
        'modulepath' => substr(MODULE, 0, -1),
        #'icon' => $icon,
        #'newmessages' => $newmessages,
        'userID' => $userID,
        'l_avatar' => $l_avatar,
        'nickname' => getusername($userID),
        'dashboard' => $dashboard,
        'lang_log_off' => $languageService->module['log_off'],
        'lang_overview' => $languageService->module['overview'],
        'to_profil' => $languageService->module['to_profil'],
        'lang_edit_profile' => $languageService->module['edit_profile'],
        'my_account' => $languageService->module['my_account']
    ];

    echo $tpl->loadTemplate("navigation", "login_loggedin", $data_array, 'theme');
} else {
    $data_array = [
        'modulepath' => substr(MODULE, 0, -1),
        'lang_login' => $languageService->get('login')
    ];

    echo $tpl->loadTemplate("navigation", "login_login", $data_array, 'theme');
}
