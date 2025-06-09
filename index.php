<?php
/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Webspell-RM 3.0 - Modern Content & Community Management System
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * @version       3.0
 * @build         Stable Release
 * @release       2025
 * @copyright     © 2018–2025 Webspell-RM | https://www.webspell-rm.de
 * 
 * @description   Webspell-RM is a modern open source CMS designed for gaming
 *                communities, esports teams, and digital projects of any kind.
 * 
 * @author        Based on the original WebSPELL Clanpackage by Michael Gruber
 *                (webspell.at), further developed by the Webspell-RM Team.
 * 
 * @license       GNU General Public License (GPL)
 *                This software is distributed under the terms of the GPL.
 *                It is strictly prohibited to remove this copyright notice.
 *                For license details, see: https://www.gnu.org/licenses/gpl.html
 * 
 * @support       Support, updates, and plugins available at:
 *                → Website: https://www.webspell-rm.de
 *                → Forum:   https://www.webspell-rm.de/forum.html
 *                → Wiki:    https://www.webspell-rm.de/wiki.html
 * 
 * ─────────────────────────────────────────────────────────────────────────────
 */


// === Fehleranzeige aktivieren ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// === Session starten ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === Sprachsystem vorbereiten ===
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

// === System-Dateien einbinden ===
include_once("system/config.inc.php");
include_once("system/settings.php");
include_once("system/functions.php");
include_once("system/themes.php");
include_once("system/init.php");
include_once("system/plugin.php");
include_once("system/widget.php");
include_once("system/multi_language.php");
include_once("system/classes/track_visitor.php");
include_once("system/init_language.php"); // setzt $languageService
include_once("system/classes/Router.php");
include_once("system/classes/Template.php");

// === Globale Variablen ===
global $tpl;
global $_database;
global $languageService;

// === Template initialisieren ===
$tpl = new Template();
Template::setInstance($tpl);

// Jetzt kannst du getInstance() ohne Fehler aufrufen
$instance = Template::getInstance();

$theme = new Theme();

$tpl->themes_path = rtrim($theme->get_active_theme(), '/\\') . DIRECTORY_SEPARATOR;
$tpl->template_path = "templates" . DIRECTORY_SEPARATOR;

// === Plugins initialisieren ===
$_pluginmanager = new plugin_manager();

// === CSS / JS Komponenten vorbereiten ===
$components_css = "";
if (!empty($components['css'])) {
    foreach ($components['css'] as $component) {
        $components_css .= '<link type="text/css" rel="stylesheet" href="' . htmlspecialchars($component) . '" />' . "\n";
    }
}

define("MODULE", "./includes/modules/");
define("PLUGIN", "./includes/plugins/");

$components_js = "";
if (!empty($components['js'])) {
    foreach ($components['js'] as $component) {
        $components_js .= '<script src="' . htmlspecialchars($component) . '"></script>' . "\n";
    }
}

$theme_css = headfiles("css", $tpl->themes_path);
$theme_js = headfiles("js", $tpl->themes_path);

// === Zusätzliche Tracker / Plugins ===
if (file_exists("includes/plugins/counter/counter_track.php")) {
    include_once("includes/plugins/counter/counter_track.php");
}
if (file_exists("includes/plugins/whoisonline/whoisonline_tracker.php")) {
    include_once("includes/plugins/whoisonline/whoisonline_tracker.php");
}

// === Routing starten ===
#include_once("system/routes/web.php");

// === Router aufrufen ===
#$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
include($tpl->themes_path . "index.php");

/*if (isset($_GET['site'])) {
    $expected_uri = '/' . trim($_GET['site'], '/');
    $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Normalisiere beide Pfade ohne abschließenden Slash
    $normalized_expected = rtrim($expected_uri, '/');
    $normalized_request = rtrim($request_path, '/');

    if ($normalized_request !== $normalized_expected) {
        header("Location: $expected_uri", true, 301);
        exit;
    }
}*/