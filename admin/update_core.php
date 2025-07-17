<?php
// Sicherstellen, dass Session läuft
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use webspell\CMSUpdater;
use webspell\AccessControl;

// Admin-Zugriff prüfen
AccessControl::checkAdminAccess('ac_update_core');

require_once __DIR__ . '/../system/classes/CMSUpdater.php';

$updater = new CMSUpdater();

global $_language, $_database;

$tpl = new Template();
$data_array = [];

define("CURRENT_VERSION", trim(@file_get_contents(__DIR__ . '/version.txt')) ?: "1.0.0");

$update_info_url = "https://update.webspell-rm.de/updates/update_info.json";
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

$update_text = "<strong>Folgende Updates sind verfügbar:</strong><ul>";
foreach ($updates as $update) {
    $update_text .= "<li><strong>Version:</strong> {$update['version']}<br>{$update['changelog']}</li>";
}
$update_text .= "</ul>";

$data_array['update_status'] = $update_text;
$data_array['show_button'] = !empty($updates);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $steps_log = [];
    $tmp_dir = __DIR__ . '/tmp';
    $extract_path = __DIR__ . '/..';
    $new_version = CURRENT_VERSION;

    // Schritt 1: tmp-Verzeichnis prüfen/erstellen
    $steps_log[] = "1. Prüfe tmp-Verzeichnis...";
    if (!is_dir($tmp_dir) && !mkdir($tmp_dir, 0755, true)) {
        $data_array['update_status'] = implode("<br>", $steps_log) . "<br><strong>Fehler: tmp-Verzeichnis konnte nicht erstellt werden.</strong>";
        echo $tpl->loadTemplate("update_core", "content", $data_array, "admin");
        return;
    }

    // Schritt 2: Updates ausführen
    $steps_log[] = "2. Führe Updates aus...";
    foreach ($updates as $update) {
        $version = $update['version'];
        $zip_url = $update['zip_url'];
        $changelog = $update['changelog'];
        $zip_file = $tmp_dir . "/update_$version.zip";

        $steps_log[] = "→ Lade Update $version herunter...";
        $zip_content = @file_get_contents($zip_url);
        if (!$zip_content || !file_put_contents($zip_file, $zip_content)) {
            $steps_log[] = "<strong>Fehler beim Herunterladen von Version $version.</strong>";
            break;
        }

        $zip = new ZipArchive;
        if ($zip->open($zip_file) === TRUE) {

            // Array zum Speichern der ersetzten Dateien
            $replaced_files = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $target_file = $extract_path . '/' . $filename;

                if (file_exists($target_file)) {
                    $replaced_files[] = $filename; // Datei wird überschrieben
                }
            }

            // Entpacken
            $zip->extractTo($extract_path);
            $zip->close();

            $steps_log[] = "→ Update $version erfolgreich entpackt.";

            if (!empty($replaced_files)) {
                $filtered_files = [];

                foreach ($replaced_files as $file) {
                    // Ignoriere bestimmte Verzeichnisse
                    if (
                        str_starts_with($file, 'admin/') ||
                        str_starts_with($file, 'admin/update_core/') ||
                        str_starts_with($file, 'admin/update_core/migrations/')
                    ) {
                        continue;
                    }

                    // Nur Dateien anzeigen, keine Ordner (keine Pfade, die mit '/' enden)
                    if (!is_dir(__DIR__ . '/../' . $file)) {
                        $filtered_files[] = $file;
                    }
                }

                if (!empty($filtered_files)) {
                    $steps_log[] = "→ Folgende Dateien wurden überschrieben:<br><div class=\"alert alert-info\" role=\"alert\"><pre>" .
                        htmlspecialchars(implode("\n", $filtered_files)) .
                        "</pre></div>";
                }
            }

        } else {
            $steps_log[] = "<strong>ZIP-Archiv von Version $version konnte nicht geöffnet werden.</strong>";
            break;
        }


        // SQL-/PHP-Migration ausführen
        $sql_file = __DIR__ . "/update_core/migrations/{$version}.php";

        if (!file_exists($sql_file)) {
            $steps_log[] = "<strong>Fehler: Lokale SQL-Datei für Version $version nicht gefunden: {$sql_file}</strong>";
            continue;
        }

        $steps_log[] = "→ Führe PHP-Migration für Version $version aus...";

        try {
            // Migration Helper global machen, falls gebraucht
            global $migrator;
            $migrator = new \webspell\DatabaseMigrationHelper($_database);

            include $sql_file;

            $steps_log[] = "→ PHP-Migration für Version $version abgeschlossen.";

            // Migration-Log anhängen
            $migration_log = $migrator->getLog();
            if ($migration_log) {
                $steps_log[] = "<div class=\"alert alert-info\" role=\"alert\"><pre>" . htmlspecialchars($migration_log) . "</pre></div>";
            }
        } catch (Throwable $e) {
            $steps_log[] = "<strong>Fehler bei der PHP-Migration für Version $version:</strong> " . $e->getMessage();
        }

        // Temporäre Datei löschen
        if (file_exists($sql_file)) {
            unlink($sql_file);
            $steps_log[] = "→ Temporäre Datei für Version $version gelöscht.";
        }

        // ZIP-Datei löschen
        if (file_exists($zip_file)) {
            unlink($zip_file);
            $steps_log[] = "→ ZIP-Datei für Version $version gelöscht.";
        }


        $new_version = $version;
    }

    // Neue Version speichern
    file_put_contents(__DIR__ . '/version.txt', $new_version);
    $steps_log[] = "<strong>Systemversion wurde aktualisiert auf $new_version</strong>";

    $data_array['update_status'] = implode("<br>", $steps_log) . "<br><strong>Alle verfügbaren Updates wurden angewendet.</strong>";
} else {
    // Nur Anzeige (GET)
    $data_array['show_button'] = !empty($updates);
}

echo $tpl->loadTemplate("update_core", "content", $data_array, "admin");
