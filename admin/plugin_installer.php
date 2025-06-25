<?php

use webspell\LanguageService;

// Session absichern
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Standard setzen, wenn nicht vorhanden
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

// Initialisieren
global $_database,$languageService;
$lang = $languageService->detectLanguage();
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('plugin_installer', true);

use webspell\AccessControl;
use webspell\PluginUninstaller;
use webspell\Plugininstaller;

// Admin-Rechte prüfen
AccessControl::checkAdminAccess('ac_plugin_installer');

// Konfiguration
$plugin_dir = '../includes/plugins/';
$plugin_path = 'https://www.update.webspell-rm.de/plugins';
$plugin_json_url = $plugin_path . '/plugins.json';

// Plugin installieren, updaten oder deinstallieren
if (isset($_GET['install']) || isset($_GET['update']) || isset($_GET['uninstall'])) {
    $plugin_action = isset($_GET['install']) ? 'install' : (isset($_GET['update']) ? 'update' : 'uninstall');
    $plugin_folder = basename($_GET[$plugin_action]);

    $plugin_info_array = @json_decode(@file_get_contents($plugin_json_url), true);
    $plugin_info = null;

    if (is_array($plugin_info_array)) {
        foreach ($plugin_info_array as $plugin) {
            if (isset($plugin['modulname']) && $plugin['modulname'] === $plugin_folder) {
                $plugin_info = $plugin;
                break;
            }
        }
    }

    // Plugin deinstallieren
    if ($plugin_action === 'uninstall') {
        if (empty($plugin_folder) || !preg_match('/^[a-z0-9_\-]+$/i', $plugin_folder)) {
            echo '<div class="alert alert-danger">Ungültiger Plugin-Ordner.</div>';
            exit;
        }

        $uninstaller = new PluginUninstaller();
        $uninstaller->uninstall($plugin_folder);

        $log = $uninstaller->getLog();
        $html_log = '';
        $valid_types = ['success', 'danger', 'warning', 'info'];

        foreach ($log as $entry) {
            $type = in_array($entry['type'], $valid_types) ? $entry['type'] : 'info';
            $html_log .= '<div class="alert alert-' . htmlspecialchars($type) . '">' . htmlspecialchars($entry['message']) . '</div>';
        }

        echo $html_log;
        exit;
    }

    // Plugin installieren oder updaten
    if (!$plugin_info) {
        echo '<div class="alert alert-danger">Plugin nicht gefunden: ' . htmlspecialchars($plugin_folder) . '</div>';
        exit;
    }

    $local_plugin_folder = $plugin_dir . $plugin_folder;

    if (!download_plugin_files($plugin_folder, $local_plugin_folder, $plugin_path)) {
        exit;
    }

    $script_file = $plugin_action === 'install' ? 'install.php' : 'update.php';
    $script_path = $local_plugin_folder . '/' . $script_file;

    if (file_exists($script_path)) {
        include $script_path;
    }

    // Plugin-Daten speichern
    $name = htmlspecialchars($plugin_info['name']);
    $modulname = htmlspecialchars($plugin_info['modulname']);
    $description = htmlspecialchars($plugin_info['description']);
    $version = htmlspecialchars($plugin_info['version']);
    $author = htmlspecialchars($plugin_info['author']);
    $url = htmlspecialchars($plugin_info['url']);
    $folder = htmlspecialchars($plugin_folder);

    if ($plugin_action === 'install') {
        safe_query("
            INSERT INTO plugins_installed (name, modulname, description, version, author, url, folder, installed_date)
            VALUES ('$name','$modulname','$description','$version','$author','$url','$folder',NOW())
        ");
        echo '<div class="alert alert-success">Plugin <strong>' . $name . '</strong> wurde installiert.</div>';
        echo '<script type="text/javascript">
                setTimeout(function() {
                    window.location.href = "admincenter.php?site=plugin_installer";
                }, 3000); // 3 Sekunden warten
            </script>';
    } else {
        safe_query("
            UPDATE plugins_installed 
            SET version = '$version', installed_date = NOW()
            WHERE modulname = '$modulname'
        ");
        echo '<div class="alert alert-success">Plugin <strong>' . $name . '</strong> wurde aktualisiert.</div>';
        echo '<script type="text/javascript">
                setTimeout(function() {
                    window.location.href = "admincenter.php?site=plugin_installer";
                }, 3000); // 3 Sekunden warten
            </script>';
    }
}

// Lokale Plugins erfassen
$local_plugins = [];
foreach (scandir($plugin_dir) as $folder) {
    if ($folder == '.' || $folder == '..') continue;
    $path = $plugin_dir . $folder;
    if (is_dir($path) && file_exists("$path/plugin.json")) {
        $json = json_decode(file_get_contents("$path/plugin.json"), true);
        if ($json) {
            $json['dir'] = $folder;
            $local_plugins[$json['name']] = $json;
        }
    }
}

// Externe Plugins abrufen
$external_plugins = [];
if (filter_var($plugin_json_url, FILTER_VALIDATE_URL)) {
    $plugin_data = @file_get_contents($plugin_json_url);
    if ($plugin_data) {
        $decoded = json_decode($plugin_data, true);
        if (is_array($decoded)) {
            foreach ($decoded as $plugin) {
                if (isset($plugin['name'])) {
                    $external_plugins[$plugin['name']] = $plugin;
                }
            }
        }
    }
}

// Installierte Plugins laden
$installed_plugins = [];
$res = safe_query("SELECT * FROM plugins_installed");
while ($row = mysqli_fetch_assoc($res)) {
    $installed_plugins[$row['name']] = $row;
}

// Alle Plugins zusammenführen
$all_plugin_names = array_unique(array_merge(
    array_keys($local_plugins),
    array_keys($external_plugins),
    array_keys($installed_plugins)
));

$plugins_for_template = [];

foreach ($all_plugin_names as $name) {
    $local = $local_plugins[$name] ?? null;
    $external = $external_plugins[$name] ?? null;
    $installed_entry = $installed_plugins[$name] ?? null;

    $plugin = $local ?? $external;
    if (!$plugin) continue;

    $plugin_folder = $plugin['dir'] ?? $plugin['name'];
    $installed = $installed_entry !== null;
    $installed_version = $installed_entry['version'] ?? '—';

    $update = false;
    if ($installed && isset($plugin['version'])) {
        $update = version_compare($installed_version, $plugin['version'], '<');
    }

    $plugins_for_template[] = [
        'name' => $name,
        'modulname' => $plugin['modulname'] ?? '',
        'description' => $plugin['description'] ?? '',
        'version' => $plugin['version'] ?? '',
        'author' => $plugin['author'] ?? '',
        'url' => $plugin['url'] ?? '',
        'download' => $plugin['download'] ?? '',
        'folder' => $plugin_folder,
        'installed_version' => $installed_version,
        'installed' => $installed,
        'update' => $update,
        'lang' => $plugin['lang'] ?? 'de'
    ];
}

// HTML-Ausgabe
echo '
<div class="card">
    <div class="card-header">'.$languageService->get('plugin_installer').'</div>
    <div class="card-body">
        <div class="container py-5">
        <h3>'.$languageService->get('plugin_installer_headline').'</h3>

        <table class="table table-bordered table-striped bg-white shadow-sm">
            <thead class="table-light">
                <tr>
                    <th width="14%">'.$languageService->get('plugin_name').'</th>
                    <th>'.$languageService->get('plugin_description').'</th>
                    <th width="6%">'.$languageService->get('language').'</th>
                    <th width="6%">'.$languageService->get('plugin_version').'</th>
                    <th width="14%">'.$languageService->get('plugin_action').'</th>
                </tr>
            </thead>
            <tbody>';
foreach ($plugins_for_template as $plugin) {

    $translate = new multiLanguage($lang);
    $languages = $translate->detectLanguages($plugin['description']);
    $description = $translate->getTextByLanguage($plugin['description']);

    // Flaggen-HTML generieren
    $flags_html = '';
    $lang_codes = explode(',', $plugin['lang'] ?? '');
    foreach ($lang_codes as $lang_code) {
        $lang_code = trim($lang_code);
        if ($lang_code !== '') {
            $flags_html .= '<img src="images/flags/' . $lang_code . '.svg" alt="' . strtoupper($lang_code) . '" title="' . strtoupper($lang_code) . '" class="me-1" style="height:16px;">';
        }
    }

    echo '<tr>
        <td>'.htmlspecialchars($plugin['name']).'</td>
        <td>'.$description.'</td>
        <td>'.$flags_html.'</td>
        <td>'.htmlspecialchars($plugin['version']);

    if ($plugin['installed']) {
        echo '<br><small class="text-muted">('.htmlspecialchars($plugin['installed_version']).')</small>';
    }

    echo '</td><td>';

    if ($plugin['installed']) {
        echo '<button class="btn btn-success btn-sm" disabled>'.$languageService->get('installed').'</button> ';
        if ($plugin['update']) {
            echo '<a href="admincenter.php?site=plugin_installer&update='.urlencode($plugin['modulname']).'" class="btn btn-warning btn-sm">'
                .$languageService->get('update').'</a> ';
        }
        echo '<a href="admincenter.php?site=plugin_installer&uninstall='.urlencode($plugin['modulname']).'" class="btn btn-danger btn-sm" onclick="return confirm(\'Wirklich deinstallieren?\');">'
            .$languageService->get('uninstall').'</a>';
    } else {
        if (!empty($plugin['download']) && $plugin['download'] !== 'DISABLED') {
            echo '<a href="admincenter.php?site=plugin_installer&install=' . urlencode($plugin['modulname']) . '" 
                class="btn btn-primary btn-sm">' . $languageService->get('install') . '</a>';
        } else {
            echo '<span style="color: gray;">Kein Download verfügbar</span>';
        }
    }

    echo '</td></tr>';
}

echo '
            </tbody>
        </table>
        </div>
    </div>
</div>';


/**
 * Plugin-Dateien herunterladen und entpacken
 */
function download_plugin_files(string $plugin_folder, string $local_plugin_folder, string $plugin_path)
{
    $remote_url = $plugin_path . '/' . $plugin_folder . '.zip';
    $local_zip = $local_plugin_folder . '.zip';

    // ZIP-Datei herunterladen
    $content = @file_get_contents($remote_url);
    if ($content === false) {
        echo '<div class="alert alert-danger">Download der Plugin-Datei fehlgeschlagen.</div>';
        return false;
    }

    // Lokal speichern
    if (file_put_contents($local_zip, $content) === false) {
        echo '<div class="alert alert-danger">Speichern der Plugin-Datei fehlgeschlagen.</div>';
        return false;
    }

    // Entpacken
    $zip = new ZipArchive();
    if ($zip->open($local_zip) === true) {
        $zip->extractTo($local_plugin_folder);
        $zip->close();
        unlink($local_zip);
        return true;
    } else {
        echo '<div class="alert alert-danger">Entpacken der Plugin-Datei fehlgeschlagen.</div>';
        return false;
    }
}

/**
 * Ordner rekursiv löschen
 */
function deleteFolder($folderPath) {
    if (!is_dir($folderPath)) return false;

    $files = array_diff(scandir($folderPath), ['.', '..']);
    foreach ($files as $file) {
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
        is_dir($filePath) ? deleteFolder($filePath) : unlink($filePath);
    }

    return rmdir($folderPath);
}
