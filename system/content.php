<?php

/**
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 *                  Webspell-RM      /                        /   /                                          *
 *                  -----------__---/__---__------__----__---/---/-----__---- _  _ -                         *
 *                   | /| /  /___) /   ) (_ `   /   ) /___) /   / __  /     /  /  /                          *
 *                  _|/_|/__(___ _(___/_(__)___/___/_(___ _/___/_____/_____/__/__/_                          *
 *                               Free Content / Management System                                            *
 *                                           /                                                               *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @version         webspell-rm                                                                              *
 *                                                                                                           *
 * @copyright       2018-2024 by webspell-rm.de                                                              *
 * @support         For Support, Plugins, Templates and the Full Script visit webspell-rm.de                 *
 * @website         <https://www.webspell-rm.de>                                                             *
 * @forum           <https://www.webspell-rm.de/forum.html>                                                  *
 * @wiki            <https://www.webspell-rm.de/wiki.html>                                                   *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @license         Script runs under the GNU GENERAL PUBLIC LICENCE                                         *
 *                  It's NOT allowed to remove this copyright-tag                                            *
 *                  <http://www.fsf.org/licensing/licenses/gpl.html>                                         *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @author          Code based on WebSPELL Clanpackage (Michael Gruber - webspell.at)                        *
 * @copyright       2005-2011 by webspell.org / webspell.info                                                *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 */

// Funktion zur Ausgabe des Seitentitels
function get_sitetitle(): string
{
    $pluginManager = new plugin_manager();

    $site = $_GET['site'] ?? null;
    $updatedTitle = $site ? $pluginManager->plugin_updatetitle($site) : null;

    return $updatedTitle ?: PAGETITLE;
}

// Ausgabe des Hauptinhalts
/*function get_mainContent()
{
    #global $cookievalue, $userID, $date, $loggedin, $_language, $tpl, $myclanname, $hp_url, $imprint_type, $admin_email, $admin_name;
    global $cookievalue, $userID, $date, $loggedin, $_language, $tpl, $hp_url, $imprint_type, $admin_email, $admin_name;
    global $maxtopics, $plugin_path, $maxposts, $page, $action, $preview, $message, $topicID, $_database, $maxmessages, $new_chmod;
    global $hp_title, $default_format_date, $default_format_time, $register_per_ip, $rewriteBase, $activate;

    $settings = safe_query("SELECT * FROM `settings`");
    if (!$settings) {
        system_error("Fehler beim Abrufen der Einstellungen.");
    }
    $ds = mysqli_fetch_array($settings);

    $site = isset($_GET['site']) ? htmlspecialchars($_GET['site'], ENT_QUOTES, 'UTF-8') : $ds['startpage'];

    $invalide = array('\\', '/', '/\/', ':', '.');
    $site = str_replace($invalide, ' ', $site);

    $_plugin = new plugin_manager();
    $_plugin->set_debug(DEBUG);

    if (!empty($site) && $_plugin->is_plugin($site) > 0) {
        $data = $_plugin->plugin_data($site);
        $plugin_path = !empty($data['path']) ? $data['path'] : '';
        $check = $_plugin->plugin_check($data, $site);

        if ($check['status'] == 1) {
            $inc = $check['data'];
            if ($inc == "exit") {
                $site = "404";
            }
            include($check['data']);
        } else {
            echo $check['data'];
        }
    } else {
        if (!file_exists("includes/modules/" . $site . ".php")) {
            $site = "404";
        }
        include("includes/modules/" . $site . ".php");
    }
}*/
/*
function get_mainContent() {
    global $tpl;
    

    $settings = safe_query("SELECT * FROM `settings`");
        if (!$settings) {
            system_error("Fehler beim Abrufen der Einstellungen.");
        }
        $ds = mysqli_fetch_array($settings);

        $site = isset($_GET['site']) ? htmlspecialchars($_GET['site'], ENT_QUOTES, 'UTF-8') : $ds['startpage'];


    // ungültige Zeichen entfernen (optional)
    $invalide = array('\\', '/', '/\/', ':', '.');
    $site = str_replace($invalide, ' ', $site);

    $module_dir = realpath(__DIR__ . '/../includes/modules');

    $module_path = $module_dir . "/$site.php";

    if (file_exists($module_path)) {
        ob_start();
        include $module_path;
        return ob_get_clean();
    } else {
        // 404 laden
        $error_page = $module_dir . "/404.php";
        if (file_exists($error_page)) {
            ob_start();
            include $error_page;
            return ob_get_clean();
        } else {
            return "<h1>404 - Seite nicht gefunden</h1>";
        }
    }

}
*/


function get_mainContent() {
    global $tpl;

    $settings = safe_query("SELECT * FROM `settings`");
    if (!$settings) {
        system_error("Fehler beim Abrufen der Einstellungen.");
    }
    $ds = mysqli_fetch_array($settings);

    $site = isset($_GET['site']) ? htmlspecialchars($_GET['site'], ENT_QUOTES, 'UTF-8') : $ds['startpage'];

    // ungültige Zeichen entfernen
    $invalide = array('\\', '/', '/\/', ':', '.');
    $site = str_replace($invalide, ' ', $site);

    // Modul- und Plugin-Verzeichnisse
    $module_dir = realpath(__DIR__ . '/../includes/modules');
    $plugin_dir = realpath(__DIR__ . '/../includes/plugins');

    // Dateipfade
    $module_path = $module_dir . "/$site.php";
    $plugin_path = $plugin_dir . "/$site/$site.php";

    if (file_exists($module_path)) {
        ob_start();
        include $module_path;
        return ob_get_clean();
    } elseif (file_exists($plugin_path)) {
        ob_start();
        include $plugin_path;
        return ob_get_clean();
    } else {
        // 404 laden
        $error_page = $module_dir . "/404.php";
        if (file_exists($error_page)) {
            ob_start();
            include $error_page;
            return ob_get_clean();
        } else {
            return "<h1>404 - Seite nicht gefunden</h1>";
        }
    }
}


// Widget-Registrierung
function register_widget_module($widget_name)
{
    $widget_menu = new widgets();
    $widget_menu->registerWidget($widget_name);
}

// Header Modul
function get_header_modul() {
    register_widget_module("header_widget");
}

// Navigations Modul
function get_navigation_modul() {
    register_widget_module("navigation_widget");
}

// Content Head Modul
function get_content_head_modul() {
    register_widget_module("content_head_widget");
}

// Content Above Center Modul
function get_content_up_modul() {
    register_widget_module("content_up_widget");
}

// Ausgabe Left Side
function get_left_side_modul()
{
    $qs_arr = array();
    parse_str($_SERVER['QUERY_STRING'], $qs_arr);
    $getsite = isset($qs_arr['site']) ? $qs_arr['site'] : 'startpage';

    $noWidgetPages = [
        'contact', 'imprint', 'privacy_policy', 'profile', 'edit_profile', 'error_404',
        'report', 'static', 'loginoverview', 'register', 'lostpassword', 'login',
        'logout', 'footer', 'navigation', 'topbar', 'articles_comments', 'blog_comments',
        'gallery_comments', 'news_comments', 'news_recomments', 'polls_comments',
        'videos_comments', 'activate'
    ];

    if (in_array($getsite, $noWidgetPages)) {
        return;
    }

    $tableName = ($getsite === 'forum_topic')
        ? "plugins_forum_settings_widgets"
        : "plugins_" . $getsite . "_settings_widgets";

    $result = safe_query("SELECT * FROM `$tableName` WHERE `position`='left_side_widget' OR `position`='full_activated'");
    $dx = mysqli_fetch_array($result);

    if (isset($dx['position']) && ($dx['position'] == 'left_side_widget' || $dx['position'] == 'full_activated')) {
        echo '<div id="leftcol" class="col-md-3">';
        $widget_menu = new widgets();
        $widget_menu->registerWidget("left_side_widget");
        $widget_menu->registerWidget("full_activated");
        echo '</div>';
    }
}

// Ausgabe Right Side
function get_right_side_modul()
{
    $qs_arr = array();
    parse_str($_SERVER['QUERY_STRING'], $qs_arr);
    $getsite = isset($qs_arr['site']) ? $qs_arr['site'] : 'startpage';

    $noWidgetPages = [
        'contact', 'imprint', 'privacy_policy', 'profile', 'edit_profile', 'error_404',
        'report', 'static', 'loginoverview', 'register', 'lostpassword', 'login',
        'logout', 'footer', 'navigation', 'topbar', 'articles_comments', 'blog_comments',
        'gallery_comments', 'news_comments', 'news_recomments', 'polls_comments',
        'videos_comments', 'activate'
    ];

    if (in_array($getsite, $noWidgetPages)) {
        return;
    }

    $tableName = ($getsite === 'forum_topic')
        ? "plugins_forum_settings_widgets"
        : "plugins_" . $getsite . "_settings_widgets";

    $result = safe_query("SELECT * FROM `$tableName` WHERE `position`='right_side_widget' OR `position`='full_activated'");
    $dx = mysqli_fetch_array($result);

    if (isset($dx['position']) && ($dx['position'] == 'right_side_widget' || $dx['position'] == 'full_activated')) {
        echo '<div id="rightcol" class="col-md-3">';
        $widget_menu = new widgets();
        $widget_menu->registerWidget("right_side_widget");
        $widget_menu->registerWidget("full_activated");
        echo '</div>';
    }
}

// Content Below Center Modul
function get_content_down_modul() {
    register_widget_module("content_down_widget");
}

// Content Foot Modul
function get_content_foot_modul() {
    register_widget_module("content_foot_widget");
}

// Footer Modul
function get_footer_modul() {
    register_widget_module("footer_widget");
}

// Wartungsmodus Hinweis
function get_lock_modul()
{
    global $closed;

    $query = safe_query("SELECT `closed` FROM `settings` WHERE `closed`='1'");
    if ($query && mysqli_num_rows($query) > 0) {
        if (!isset($closed) || $closed != '1') {
            return;
        }

        echo '<div class="alert alert-danger" role="alert" style="margin-bottom: -5px;">
            <center>Die Seite befindet sich im Wartungsmodus | The site is in maintenance mode | Il sito è in modalità manutenzione</center>
        </div>';
    }
}

// CKEditor Konfiguration (je nach Superadmin)
function get_editor()
{
    global $userID;

    if (!function_exists('issuperadmin')) {
        echo '<p>Error: Superadmin function is not defined.</p>';
        return;
    }

    echo '<script src="./components/ckeditor/ckeditor.js"></script>';

    if (issuperadmin($userID)) {
        echo '<script src="./components/ckeditor/config.js"></script>';
    } else {
        echo '<script src="./components/ckeditor/user_config.js"></script>';
    }
}
