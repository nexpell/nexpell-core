<?php
// Sicherstellen, dass Session läuft
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use nexpell\CMSUpdater;
use nexpell\AccessControl;

// Admin-Zugriff prüfen
AccessControl::checkAdminAccess('ac_update_core');

require_once __DIR__ . '/../system/classes/CMSUpdater.php';

$updater = new CMSUpdater();

global $_language, $_database;

$tpl = new Template();
$data_array = [];

// Pfad zur version.php im system-Ordner
$version_file = __DIR__ . '/../system/version.php';

// Core-Version laden, sonst Fallback
$core_version = file_exists($version_file) ? include $version_file : '1.0.0';

// Konstante definieren, wie es in update_core.php erwartet wird
define('CURRENT_VERSION', $core_version);

$update_info_url = "https://update.nexpell.de/updates/update_info.json";
$update_info_json = @file_get_contents($update_info_url);
$update_info = $update_info_json ? json_decode($update_info_json, true) : null;

if (!$update_info || !isset($update_info['updates']) || !is_array($update_info['updates'])) {
    $data_array['update_status'] = "Update-Informationen konnten nicht geladen werden.";
    echo $tpl->loadTemplate("update_core", "content", $data_array, "admin");
    return;
}

$data_array['current_version'] = CURRENT_VERSION;

$updates = array_filter($update_info['updates'], function ($entry) {
    return version_compare($entry['version'], CURRENT_VERSION, '>');
});

$latest_update = null;
if (!empty($updates)) {
    $latest_update = end($updates);
    $data_array['new_version'] = $latest_update['version'];
    $data_array['changelog'] = $latest_update['changelog'];
} else {
    $data_array['new_version'] = CURRENT_VERSION;
    $data_array['changelog'] = "Keine weiteren Änderungen verfügbar.";
}

if (!empty($updates)) {
    $update_text = "<strong>Folgende Updates sind verfügbar:</strong><ul>";
    foreach ($updates as $update) {
        $update_text .= "<li><strong>Version:</strong> {$update['version']}<br>{$update['changelog']}</li>";
    }
    $update_text .= "</ul>";

    $data_array['update_status'] = $update_text;
    $data_array['show_button'] = true;
    $data_array['has_update'] = true;
} else {
    $data_array['update_status'] = "<div class=\"alert alert-success\">
        <i class=\"bi bi-check-circle me-2\"></i>
        Du verwendest bereits die neueste Version.
    </div>";
    $data_array['show_button'] = false;
    $data_array['has_update'] = false;
}

// Hier wird der Button für die Zurück-Navigation immer angezeigt, unabhängig vom Update-Status
$data_array['show_back_button'] = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $steps_log = [];
    $tmp_dir = __DIR__ . '/tmp';
    $extract_path = __DIR__ . '/..';
    $new_version = CURRENT_VERSION;
    $all_updates_succeeded = true;
    $downloaded_zips = [];
    $downloaded_migrations = [];

    // Schritt 1: tmp-Verzeichnis prüfen/erstellen
    $steps_log[] = "1. Prüfe tmp-Verzeichnis...";
    if (!is_dir($tmp_dir) && !mkdir($tmp_dir, 0755, true)) {
        $data_array['update_status'] = implode("<br>", $steps_log) . "<br><strong>Fehler: tmp-Verzeichnis konnte nicht erstellt werden.</strong>";
        echo $tpl->loadTemplate("update_core", "content", $data_array, "admin");
        return;
    }

    // Schritt 2: Updates herunterladen und Migrationsskripte in temporäres Verzeichnis entpacken
    $steps_log[] = "2. Lade Updates herunter und entpacke Migrationsdateien...";
    foreach ($updates as $update) {
        $version = $update['version'];
        $zip_url = $update['zip_url'];
        $zip_file = $tmp_dir . "/update_$version.zip";
        $sql_file = $tmp_dir . "/migrations/{$version}.php"; // Temporärer Pfad für Migrationen

        if (!is_dir($tmp_dir . '/migrations')) {
            mkdir($tmp_dir . '/migrations', 0755, true);
        }

        $zip_content = @file_get_contents($zip_url);
        if (!$zip_content || !file_put_contents($zip_file, $zip_content)) {
            $steps_log[] = "<div class=\"alert alert-danger\" role=\"alert\">
                <i class=\"bi bi-exclamation-triangle-fill me-2\"></i>
                <strong>Fehler:</strong> Update-Datei für Version $version konnte nicht heruntergeladen oder gespeichert werden.
            </div>";
            $all_updates_succeeded = false;
            break;
        }
        $downloaded_zips[] = $zip_file;

        $zip = new ZipArchive;
        if ($zip->open($zip_file) === TRUE) {
            $zip->extractTo($tmp_dir, "admin/update_core/migrations/{$version}.php");
            $zip->close();
            // Verschiebe die extrahierte Migrationsdatei an den richtigen temporären Ort
            if (file_exists($tmp_dir . "/admin/update_core/migrations/{$version}.php")) {
                rename($tmp_dir . "/admin/update_core/migrations/{$version}.php", $sql_file);
                $downloaded_migrations[] = $sql_file;
            }
        }
    }

    // Schritt 3: Migrationsskripte ausführen, nur wenn alle Downloads erfolgreich waren
    if ($all_updates_succeeded) {
        $steps_log[] = "3. Führe Datenbank-Migrationen aus...";
        foreach ($updates as $update) {
            $version = $update['version'];
            $sql_file = $tmp_dir . "/migrations/{$version}.php";

            if (!file_exists($sql_file)) {
                $steps_log[] = "<div class=\"alert alert-danger\" role=\"alert\">
                    <i class=\"bi bi-file-earmark-x-fill me-2\"></i>
                    <strong>Fehler:</strong> Migrationsdatei für Version $version nicht gefunden.
                </div>";
                $all_updates_succeeded = false;
                break;
            }

            try {
                global $migrator;
                $migrator = new \nexpell\DatabaseMigrationHelper($_database);
                include $sql_file;
                $steps_log[] = "→ PHP-Migration für Version $version abgeschlossen.";
                $new_version = $version;
            } catch (Throwable $e) {
                $steps_log[] = "<div class=\"alert alert-danger\" role=\"alert\">
                    <i class=\"bi bi-bug-fill me-2\"></i>
                    <strong>Fehler bei der Migration für Version $version:</strong><br>" . htmlspecialchars($e->getMessage()) . "
                </div>";
                $all_updates_succeeded = false;
                break;
            }
        }
    }

    // Schritt 4: Dateien entpacken und Versionsnummer aktualisieren, nur wenn alle Schritte erfolgreich waren
    if ($all_updates_succeeded) {
        $steps_log[] = "4. Entpacke Dateien und schließe das Update ab...";
        foreach ($updates as $update) {
            $version = $update['version'];
            $zip_file = $tmp_dir . "/update_$version.zip";
            $zip = new ZipArchive;
            if ($zip->open($zip_file) === TRUE) {
                $zip->extractTo($extract_path);
                $zip->close();
            }
            if (isset($update['delete_files']) && is_array($update['delete_files'])) {
                foreach ($update['delete_files'] as $relativePath) {
                    $fullPath = $extract_path . '/' . $relativePath;
                    if (file_exists($fullPath)) {
                        @unlink($fullPath);
                    }
                }
            }
        }

        // Neue Version schreiben
        $version_php_content = "<?php\n\nreturn '{$new_version}';\n";
        file_put_contents($version_file, $version_php_content);

        $steps_log[] = "<div class=\"alert alert-success mt-4\" role=\"alert\">
            <h5><i class=\"bi bi-check-circle-fill me-2\"></i>System wurde aktualisiert auf Version: $new_version <i class=\"bi bi-check2 me-2\"></i></h5></div>";

        $steps_log[] = "<div class=\"alert alert-info mt-4\" role=\"alert\">
            <i class=\"bi bi-check-circle-fill me-2\"></i>
            <strong>Alle verfügbaren Updates wurden erfolgreich angewendet.</strong>
        </div>";

    } else {
        $steps_log[] = "<div class=\"alert alert-danger mt-3\" role=\"alert\">
            <i class=\"bi bi-x-circle me-2\"></i>
            <strong>Update-Vorgang wurde abgebrochen.</strong> Die Änderungen wurden nicht angewendet, um Datenkorruption zu verhindern.
        </div>";
    }

    // Schritt 5: Aufräumen der temporären Dateien
    $steps_log[] = "5. Bereinige temporäre Dateien...";
    foreach ($downloaded_zips as $zip_file) {
        if (file_exists($zip_file)) {
            @unlink($zip_file);
        }
    }
    foreach ($downloaded_migrations as $sql_file) {
        if (file_exists($sql_file)) {
            @unlink($sql_file);
        }
    }
    // Löschen des temporären Migrationsordners
    if (is_dir($tmp_dir . '/migrations')) {
        rmdir($tmp_dir . '/migrations');
    }

    $steps_log[] = "<a href=\"admincenter.php?site=update_core\" class=\"btn btn-primary mt-2\">
        <i class=\"bi bi-arrow-left-circle\"></i> Zurück zur Update-Übersicht
    </a>";

    $data_array['update_status'] = implode("<br>", $steps_log);
}

echo $tpl->loadTemplate("update_core", "content", $data_array, "admin");