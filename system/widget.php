<?php

use nexpell\PluginSettings;

/*
renderWidget($widget_key)
Lädt ein Widget anhand seines Schlüssels (widget_key) aus der Datenbank, ermittelt das zugehörige Plugin und bindet die Widget-Datei ($widget_key.php) aus dem Plugin-Verzeichnis ein.
Gibt den HTML-Ausgabe-Buffer des Widgets zurück oder eine Fehlermeldung, wenn das Widget nicht gefunden wurde.

loadWidgetHeadAssets($widget_key)
Lädt CSS- und JS-Dateien des Plugins, zu dem das Widget gehört, nur einmal pro Plugin, um doppelte Einbindungen zu vermeiden. Die Assets liegen in /includes/plugins/[plugin]/css/ bzw. /js/.

loadPluginHeadAssets()
Lädt die CSS- und JS-Dateien eines Plugins, das aktuell per ?site=pluginname aufgerufen wird (z.B. für Plugin-Seiten).

Hilfsfunktion loadHeadAssetIfExists(...)
Prüft, ob CSS- oder JS-Dateien für ein Plugin existieren und fügt diese in die globalen Sammelvariablen ein.

get_navigation_modul()
Bindet das Navigations-Widget statisch ein, indem die Widget-Datei widget_navigation.php aus dem zugehörigen Plugin-Verzeichnis direkt inkludiert wird. Das Widget wird so unabhängig von einer dynamischen Widget-Registrierung angezeigt.

get_footer_modul()
Bindet das Footer-Widget statisch ein, indem die Widget-Datei widget_footer_easy.php aus dem zugehörigen Plugin-Verzeichnis direkt inkludiert wird. Auch hier erfolgt die Anzeige unabhängig von dynamischer Widget-Verwaltung.
*/


/*function renderWidget($widget_key)
{
    global $_database;

    // Ergebnisarrays initialisieren
    global $needed_widget_css, $needed_widget_js;
    $needed_widget_css ??= [];
    $needed_widget_js ??= [];

    // Prepared Statement für mehr Sicherheit
    $stmt = $_database->prepare("SELECT widget_key, plugin FROM settings_widgets WHERE widget_key = ? LIMIT 1");
    if (!$stmt) {
        error_log("DB-Fehler in renderWidget (prepare fehlgeschlagen)");
        return "<!-- Widget konnte nicht geladen werden (DB-Fehler) -->";
    }
    $stmt->bind_param("s", $widget_key);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res || $res->num_rows === 0) {
        $safeKey = htmlspecialchars($widget_key);
        return "<!-- Widget $safeKey nicht gefunden -->";
    }

    $row = $res->fetch_assoc();
    $plugin = $row['plugin'];

    $basePath = "/includes/plugins/$plugin/";
    $widgetFile = $_SERVER['DOCUMENT_ROOT'] . $basePath . "$widget_key.php";

    $output = "";

    // Widget-Datei einbinden
    if (file_exists($widgetFile)) {
        ob_start();
        include $widgetFile;
        $output .= ob_get_clean();
    } else {
        $safeKey = htmlspecialchars($widget_key);
        $safePlugin = htmlspecialchars($plugin);
        $output .= "<!-- Widget $safeKey nicht gefunden im Plugin $safePlugin -->";
        error_log("Widget-Datei nicht gefunden: $widgetFile");
    }

    return $output;
}*/

function renderWidget($widget_key)
{
    global $_database;
    global $needed_widget_css, $needed_widget_js;
    $needed_widget_css ??= [];
    $needed_widget_js ??= [];

    $stmt = $_database->prepare("SELECT widget_key, plugin FROM settings_widgets WHERE widget_key = ? LIMIT 1");
    if (!$stmt) {
        error_log("DB-Fehler in renderWidget (prepare fehlgeschlagen)");
        return "<!-- Widget konnte nicht geladen werden (DB-Fehler) -->";
    }
    $stmt->bind_param("s", $widget_key);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res || $res->num_rows === 0) {
        return "<!-- Widget " . htmlspecialchars($widget_key) . " nicht gefunden -->";
    }

    $row = $res->fetch_assoc();
    $plugin = $row['plugin'];

    $basePath = rtrim($_SERVER['DOCUMENT_ROOT'] . "/includes/plugins/$plugin/", '/');
    $widgetFile = $basePath . "/$widget_key.php";

    if (file_exists($widgetFile)) {
        ob_start();
        include $widgetFile;
        return ob_get_clean();
    } else {
        error_log("Widget-Datei nicht gefunden: $widgetFile");
        return "<!-- Widget " . htmlspecialchars($widget_key) . " nicht gefunden im Plugin " . htmlspecialchars($plugin) . " -->";
    }
}




// Am Anfang (global für das Script)
$plugin_loadheadfile_widget_css = "";
$plugin_loadheadfile_widget_js = "";

// Globales Array für bereits geladene Plugins (CSS/JS)
$loaded_head_assets_plugins = [];

/**
 * Hilfsfunktion
 */
function loadHeadAssetIfExists(string $type, string $base_path, string &$collector, string $plugin): void {
    $filename = $type === 'css' ? "{$plugin}.css" : "{$plugin}.js";
    $abs_path = $_SERVER['DOCUMENT_ROOT'] . "{$base_path}/{$type}/{$filename}";

    if (file_exists($abs_path)) {
        if ($type === 'css') {
            $collector .= "<link type=\"text/css\" rel=\"stylesheet\" href=\"{$base_path}/{$type}/{$filename}\">\n";
        } elseif ($type === 'js') {
            $collector .= "<script src=\"{$base_path}/{$type}/{$filename}\"></script>\n";
        }
    }
}



function getCurrentSite(): string {
    global $availableLangs;

    $requestUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $segments = explode('/', $requestUri);

    if (isset($_GET['site'])) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['site']);
    }

    if (isset($segments[0]) && in_array($segments[0], $availableLangs)) {
        return preg_replace('/[^a-zA-Z0-9_-]/', '', $segments[1] ?? 'start');
    }

    return 'start';
}


/**
 * Widget-Assets laden
 */
function loadWidgetHeadAssets(string $widget_key): void {
    global $plugin_loadheadfile_widget_css, $plugin_loadheadfile_widget_js, $_database, $loaded_head_assets_plugins;

    $stmt = $_database->prepare("SELECT plugin FROM settings_widgets WHERE widget_key = ? LIMIT 1");
    $stmt->bind_param("s", $widget_key);
    $stmt->execute();
    $res = $stmt->get_result();

    $row = $res->fetch_assoc();
    if (!$row) {
        return;
    }

    $plugin = $row['plugin'];

    // Schon geladen?
    if (in_array($plugin, $loaded_head_assets_plugins, true)) {
        return;
    }

    $base_path = "/includes/plugins/{$plugin}";

    loadHeadAssetIfExists('css', $base_path, $plugin_loadheadfile_widget_css, $plugin);
    loadHeadAssetIfExists('js', $base_path, $plugin_loadheadfile_widget_js, $plugin);

    $loaded_head_assets_plugins[] = $plugin;
}



/**
 * Plugin-Assets laden (z.B. über ?site=plugin)
 */
/*function loadPluginHeadAssets(): void {
    global $_database, $plugin_loadheadfile_widget_css, $plugin_loadheadfile_widget_js, $loaded_head_assets_plugins;

    if (!isset($_GET['site'])) {
        return;
    }

    $site = $_GET['site'];

    $stmt = $_database->prepare("SELECT modulname FROM settings_plugins WHERE modulname = ? LIMIT 1");
    $stmt->bind_param("s", $site);
    $stmt->execute();
    $res = $stmt->get_result();

    $row = $res->fetch_assoc();
    if (!$row) {
        return;
    }

    $plugin = $row['modulname'];

    if (in_array($plugin, $loaded_head_assets_plugins, true)) {
        return;
    }

    $base_path = "/includes/plugins/{$plugin}";

    loadHeadAssetIfExists('css', $base_path, $plugin_loadheadfile_widget_css, $plugin);
    loadHeadAssetIfExists('js', $base_path, $plugin_loadheadfile_widget_js, $plugin);

    $loaded_head_assets_plugins[] = $plugin;
}*/

function loadPluginHeadAssets(): void {
    global $_database, $plugin_loadheadfile_widget_css, $plugin_loadheadfile_widget_js, $loaded_head_assets_plugins;

    $site = getCurrentSite(); // <<< statt $_GET['site']

    $stmt = $_database->prepare("SELECT modulname FROM settings_plugins WHERE modulname = ? LIMIT 1");
    $stmt->bind_param("s", $site);
    $stmt->execute();
    $res = $stmt->get_result();

    $row = $res->fetch_assoc();
    if (!$row) {
        return;
    }

    $plugin = $row['modulname'];

    if (in_array($plugin, $loaded_head_assets_plugins, true)) {
        return;
    }

    $base_path = "/includes/plugins/{$plugin}";

    loadHeadAssetIfExists('css', $base_path, $plugin_loadheadfile_widget_css, $plugin);
    loadHeadAssetIfExists('js', $base_path, $plugin_loadheadfile_widget_js, $plugin);

    $loaded_head_assets_plugins[] = $plugin;
}


// Navigations Modul
// Das Widget wird direkt geladen und angezeigt, 
// indem die entsprechende Widget-Datei (z. B. widget_navigation.php) manuell eingebunden wird. 
// So ist die Anzeige derNavigation unabhängig von einer dynamischen Widget-Verwaltung.
function get_navigation_modul() {
    global $_database;

    // Plugin zum Widget-Key 'navigation' ermitteln
    $stmt = $_database->prepare("SELECT modulname FROM settings_plugins WHERE modulname = ? LIMIT 1");
    $key = "navigation";
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if (!$row) {
        echo "Widget 'navigation' nicht gefunden.";
        return;
    }

    $plugin = $row['modulname'];
    $widget_path = $_SERVER['DOCUMENT_ROOT'] . "/includes/plugins//{$plugin}/widget_navigation.php";

    if (file_exists($widget_path)) {
        include $widget_path;
    } else {
        echo "Widget-Datei widget_navigation.php im Plugin {$plugin} nicht gefunden!";
    }
}

// Footer Modul
// Das Widget wird direkt geladen und angezeigt, 
// indem die entsprechende Widget-Datei (z. B. widget_footer.php) manuell eingebunden wird. 
// So ist die Anzeige des Footers unabhängig von einer dynamischen Widget-Verwaltung.
function get_footer_modul() {
    global $_database;

    // Plugin zum Widget-Key 'navigation' ermitteln
    $stmt = $_database->prepare("SELECT modulname FROM settings_plugins WHERE modulname = ? LIMIT 1");
    $key = "footer_easy";
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();

    if (!$row) {
        echo "Widget 'footer_easy' nicht gefunden.";
        return;
    }

    $plugin = $row['modulname'];
    $widget_path = $_SERVER['DOCUMENT_ROOT'] . "/includes/plugins//{$plugin}/widget_footer_easy.php";

    if (file_exists($widget_path)) {
        include $widget_path;
    } else {
        echo "Widget-Datei widget_footer_easy.php im Plugin {$plugin} nicht gefunden!";
    }
}








