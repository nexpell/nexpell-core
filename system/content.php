<?php

// Funktion zur Ausgabe des Seitentitels
function get_sitetitle(): string
{
    $pluginManager = new plugin_manager();
    $site = $_GET['site'] ?? 'index';

    // Titel gezielt für Startseite setzen
    if ($site === 'index') {
        return 'nexpell – Dein CMS für moderne Webprojekte';
    }

    $updatedTitle = $pluginManager->plugin_updatetitle($site);

    // Fallback: Standardtitel (z. B. "nexpell - [news]")
    $title = $updatedTitle ?: PAGETITLE;

    // Optional: Platzhalter ersetzen
    $replacements = [
        '[news]' => 'Nachrichten',
        '[home]' => 'Startseite',
    ];

    return strtr($title, $replacements);
}

function get_mainContent() {
    global $tpl;

    $settings = safe_query("SELECT * FROM `settings`");
    if (!$settings) {
        system_error("Fehler beim Abrufen der Einstellungen.");
    }
    $ds = mysqli_fetch_array($settings);

    $site = isset($_GET['site']) ? htmlspecialchars($_GET['site'], ENT_QUOTES, 'UTF-8') : $ds['startpage'];
    $site = preg_replace('/[^a-zA-Z0-9_-]/', '', $site);

    $module_dir = realpath(__DIR__ . '/../includes/modules');
    $plugin_dir = realpath(__DIR__ . '/../includes/plugins');

    // 1. Prüfe Modul direkt
    $module_path = $module_dir . "/$site.php";
    if (file_exists($module_path)) {
        ob_start();
        include $module_path;
        return ob_get_clean();
    }

    // 2. Plugin prüfen per settings_plugins (index_link + path)
    $plugin_query = safe_query("SELECT * FROM settings_plugins WHERE activate='1'");
    while ($row = mysqli_fetch_array($plugin_query)) {
        $links = explode(",", $row['index_link']);
        if (in_array($site, $links)) {
            $plugin_file = rtrim($row['path'], '/') . '/' . $site . '.php';
            if (file_exists($plugin_file)) {
                ob_start();
                include $plugin_file;
                return ob_get_clean();
            }
        }
    }

    // 3. Fallback 404
    $error_page = $module_dir . "/404.php";
    if (file_exists($error_page)) {
        ob_start();
        include $error_page;
        return ob_get_clean();
    }

    return "<h1>404 - Seite nicht gefunden</h1>";
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

/*Plugins manuell einbinden 
get_widget('modulname','widgetdatei'); 
*/
function get_widget($modulname, $widgetdatei)
{

    $query = safe_query("SELECT * FROM  settings_plugins WHERE modulname = '" . $modulname . "'");
    $ds = mysqli_fetch_array($query);

    if (@file_exists($ds['path'] . $widgetdatei . ".php" ?? '')) {
        $plugin_path = $ds['path'];
        require($ds['path'] . $widgetdatei . ".php");
        return false;
    } else {
        echo '';
    }
}
