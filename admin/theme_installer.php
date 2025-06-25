<?php

use webspell\LanguageService;

// Session absichern
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

// Initialisieren
global $_database,$languageService;
$lang = $languageService->detectLanguage();
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('theme_installer', true);


use webspell\AccessControl;
use webspell\themeUninstaller;
use webspell\Plugininstaller;

// Admin-Rechte prüfen
#AccessControl::checkAdminAccess('ac_plugin_installer');

// Konfiguration
$theme_dir = '../includes/themes/default/css/dist/';
$theme_path = 'https://www.update.webspell-rm.de/themes';
$theme_json_url = $theme_path . '/theme.json';

// Theme-Aktion: Installieren, Updaten, Deinstallieren
if (isset($_GET['install']) || isset($_GET['update']) || isset($_GET['uninstall'])) {
    $theme_action = isset($_GET['install']) ? 'install' : (isset($_GET['update']) ? 'update' : 'uninstall');
    $theme_folder = basename($_GET[$theme_action]);

    $theme_info_array = @json_decode(@file_get_contents($theme_json_url), true);
    $theme_info = null;

    if (is_array($theme_info_array)) {
        foreach ($theme_info_array as $theme) {
            if (isset($theme['modulname']) && $theme['modulname'] === $theme_folder) {
                $theme_info = $theme;
                break;
            }
        }
    }

    if ($theme_action === 'uninstall') {
        if (empty($theme_folder) || !preg_match('/^[a-z0-9_\-]+$/i', $theme_folder)) {
            echo '<div class="alert alert-danger">Ungültiger Theme-Ordner.</div>';
            exit;
        }

        $uninstaller = new ThemeUninstaller();
        $uninstaller->uninstall($theme_folder);
        foreach ($uninstaller->getLog() as $entry) {
            $type = in_array($entry['type'], ['success', 'danger', 'warning', 'info']) ? $entry['type'] : 'info';
            echo '<div class="alert alert-' . $type . '">' . htmlspecialchars($entry['message']) . '</div>';
        }
        echo '<script>setTimeout(function() { window.location.href = "admincenter.php?site=theme_installer"; }, 3000);</script>';
        exit;
    }

    if (!$theme_info) {
        echo '<div class="alert alert-danger">Theme nicht gefunden: ' . htmlspecialchars($theme_folder) . '</div>';
        exit;
    }

    $local_theme_folder = $theme_dir . $theme_folder;

    if (!download_theme_files($theme_folder, $local_theme_folder, $theme_path)) {
        echo '<div class="alert alert-danger">Download fehlgeschlagen.</div>';
        exit;
    }

    $script_file = $theme_action === 'install' ? 'install.php' : 'update.php';
    if (file_exists($local_theme_folder . '/' . $script_file)) {
        include $local_theme_folder . '/' . $script_file;
    }

    $name = htmlspecialchars($theme_info['name']);
    $modulname = htmlspecialchars($theme_info['modulname']);
    $description = htmlspecialchars($theme_info['description']);
    $version = htmlspecialchars($theme_info['version']);
    $author = htmlspecialchars($theme_info['author']);
    $url = htmlspecialchars($theme_info['url']);
    $folder = htmlspecialchars($theme_folder);

    if ($theme_action === 'install') {
        safe_query("INSERT INTO themes_installed (name, modulname, description, version, author, url, folder, installed_date)
                    VALUES ('$name','$modulname','$description','$version','$author','$url','$folder',NOW())");
        echo '<div class="alert alert-success">Theme <strong>' . $name . '</strong> wurde installiert.</div>';
    } else {
        safe_query("UPDATE themes_installed SET version = '$version', installed_date = NOW() WHERE modulname = '$modulname'");
        echo '<div class="alert alert-success">Theme <strong>' . $name . '</strong> wurde aktualisiert.</div>';
    }

    echo '<script>setTimeout(function() { window.location.href = "admincenter.php?site=theme_installer"; }, 3000);</script>';
    exit;
}

// Lokale Themes scannen
$local_themes = [];
foreach (scandir($theme_dir) as $folder) {
    if ($folder === '.' || $folder === '..') continue;
    $path = $theme_dir . $folder;
    if (is_dir($path) && file_exists("$path/theme.json")) {
        $json = json_decode(file_get_contents("$path/theme.json"), true);
        if ($json) {
            $json['dir'] = $folder;
            $local_themes[$json['name']] = $json;
        }
    }
}

// Externe Themes abrufen
$external_themes = [];
$remote_data = @file_get_contents($theme_json_url);
if ($remote_data) {
    $decoded = json_decode($remote_data, true);
    if (is_array($decoded)) {
        foreach ($decoded as $theme) {
            $external_themes[$theme['name']] = $theme;
        }
    }
}

// Installierte Themes
$installed_themes = [];
$res = safe_query("SELECT * FROM themes_installed");
while ($row = mysqli_fetch_assoc($res)) {
    $installed_themes[$row['name']] = $row;
}

// Zusammenführen
$all_theme_names = array_unique(array_merge(array_keys($local_themes), array_keys($external_themes), array_keys($installed_themes)));
$themes_for_template = [];

foreach ($all_theme_names as $name) {
    $local = $local_themes[$name] ?? null;
    $external = $external_themes[$name] ?? null;
    $installed_entry = $installed_themes[$name] ?? null;
    $theme = $local ?? $external;
    if (!$theme) continue;

    $theme_folder = $theme['dir'] ?? $theme['name'];
    $installed = $installed_entry !== null;
    $installed_version = $installed_entry['version'] ?? '—';
    $update = $installed && isset($theme['version']) && version_compare($installed_version, $theme['version'], '<');

    $themes_for_template[] = [
        'name' => $name,
        'modulname' => $theme['modulname'] ?? '',
        'description' => $theme['description'] ?? '',
        'version' => $theme['version'] ?? '',
        'author' => $theme['author'] ?? '',
        'url' => $theme['url'] ?? '',
        'download' => $theme['download'] ?? '',
        'folder' => $theme_folder,
        'installed_version' => $installed_version,
        'installed' => $installed,
        'update' => $update
    ];
}

// HTML-Ausgabe
echo '
<div class="card">
    <div class="card-header">' . $languageService->get('theme_installer') . '</div>
    <div class="card-body">
        <div class="container py-5">
        <h3>' . $languageService->get('theme_installer_headline') . '</h3>
        <table class="table table-bordered table-striped bg-white shadow-sm">
            <thead class="table-light">
                <tr>
                    <th width="14%">' . $languageService->get('theme_name') . '</th>
                    <th>' . $languageService->get('theme_description') . '</th>
                    <th width="6%">' . $languageService->get('theme_version') . '</th>
                    <th width="14%">' . $languageService->get('theme_action') . '</th>
                </tr>
            </thead>
            <tbody>';

foreach ($themes_for_template as $theme) {
    $translate = new multiLanguage($lang);
    $languages = $translate->detectLanguages($theme['description']);
    $description = $translate->getTextByLanguage($theme['description']);
    $flags_html = '';

    echo '<tr>
        <td>' . htmlspecialchars($theme['name']) . '</td>
        <td>' . $description . '</td>
        <td>' . htmlspecialchars($theme['version']);
    if ($theme['installed']) echo '<br><small class="text-muted">(' . htmlspecialchars($theme['installed_version']) . ')</small>';
    echo '</td><td>';

    if ($theme['installed']) {
        echo '<button class="btn btn-success btn-sm" disabled>' . $languageService->get('installed') . '</button> ';
        if ($theme['update']) {
            echo '<a href="admincenter.php?site=theme_installer&update=' . urlencode($theme['modulname']) . '" class="btn btn-warning btn-sm">' . $languageService->get('update') . '</a> ';
        }
        echo '<a href="admincenter.php?site=theme_installer&uninstall=' . urlencode($theme['modulname']) . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Wirklich deinstallieren?\');">' . $languageService->get('uninstall') . '</a>';
    } else {
        if (!empty($theme['download']) && $theme['download'] !== 'DISABLED') {
            echo '<a href="admincenter.php?site=theme_installer&install=' . urlencode($theme['modulname']) . '" class="btn btn-primary btn-sm">' . $languageService->get('install') . '</a>';
        } else {
            echo '<span style="color: gray;">Kein Download verfügbar</span>';
        }
    }

    echo '</td></tr>';
}

echo '</tbody></table></div></div></div>';

/**
 * Lädt und entpackt das Theme-ZIP
 */
function download_theme_files(string $theme_folder, string $local_theme_folder, string $theme_path): bool
{
    $remote_url = $theme_path . '/' . $theme_folder . '.zip';
    $local_zip = tempnam(sys_get_temp_dir(), 'theme_') . '.zip';

    if (!@copy($remote_url, $local_zip)) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($local_zip) === true) {
        $temp_extract_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'theme_extract_' . uniqid();
        @mkdir($temp_extract_dir, 0755, true);
        
        if (!$zip->extractTo($temp_extract_dir)) {
            $zip->close();
            unlink($local_zip);
            return false;
        }

        $zip->close();
        unlink($local_zip);

        // Prüfe, ob innerhalb des Archivs ein Ordner liegt
        $entries = scandir($temp_extract_dir);
        $entries = array_diff($entries, ['.', '..']);

        if (count($entries) === 1 && is_dir($temp_extract_dir . '/' . reset($entries))) {
            // ZIP enthält einen Ordner mit demselben Namen – verschiebe nur diesen
            $source_dir = $temp_extract_dir . '/' . reset($entries);
        } else {
            // ZIP enthält direkt Dateien – nimm gesamten temp-Ordner als Quelle
            $source_dir = $temp_extract_dir;
        }

        // Lösche ggf. bestehendes Theme-Verzeichnis
        if (is_dir($local_theme_folder)) {
            deleteFolder($local_theme_folder);
        }

        // Zielordner anlegen
        @mkdir($local_theme_folder, 0755, true);

        // Inhalte kopieren
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $targetPath = $local_theme_folder . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                @mkdir($targetPath, 0755, true);
            } else {
                copy($item, $targetPath);
            }
        }

        // Temporären Entpack-Ordner löschen
        deleteFolder($temp_extract_dir);

        return true;
    }

    return false;
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

