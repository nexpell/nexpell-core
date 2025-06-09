<?php

// Überprüfen, ob die Session bereits gestartet wurde
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


use webspell\CMSUpdater;
use webspell\AccessControl;
// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_update_core');

require_once __DIR__ . '/../system/classes/CMSUpdater.php';

$updater = new CMSUpdater();

global $_language, $_database;

$tpl = new Template();
$data_array = [];

define("CURRENT_VERSION", trim(@file_get_contents(__DIR__ . '/version.txt')) ?: "2.1.6"); // Lokale CMS-Version

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

// Button sichtbar, wenn Updates vorhanden sind
$data_array['show_button'] = !empty($updates);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $steps_log = [];
    $tmp_dir = __DIR__ . '/tmp';
    $extract_path = __DIR__ . '/..';
    $new_version = CURRENT_VERSION;

    // Schritt 1: tmp-Verzeichnis
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
            $zip->extractTo($extract_path);
            $zip->close();
            $steps_log[] = "→ Update $version erfolgreich entpackt.";
        } else {
            $steps_log[] = "<strong>ZIP-Archiv von Version $version konnte nicht geöffnet werden.</strong>";
            break;
        }

        // SQL-Migration ausführen
        $sql_url = "https://update.webspell-rm.de/updates/migrations/{$version}.sql";  // URL zur SQL-Datei
        $sql_file = __DIR__ . "/tmp/{$version}.sql";  // Temporärer Speicherort für die SQL-Datei
        
        // SQL-Datei herunterladen
        $sql_content = @file_get_contents($sql_url);
        if (!$sql_content) {
            $steps_log[] = "<strong>Fehler beim Herunterladen der SQL-Datei für Version $version.</strong>";
            continue;
        }

        // Temporäre SQL-Datei speichern
        file_put_contents($sql_file, $sql_content);

        // SQL-Statements ausführen
        $steps_log[] = "→ Führe SQL-Migration für Version $version aus...";
        $sql_statements = file_get_contents($sql_file);
        if ($_database->multi_query($sql_statements)) {
            do {
                while ($_database->more_results() && $_database->next_result()) {;}
            } while ($_database->more_results());
            $steps_log[] = "→ SQL-Migration für Version $version abgeschlossen.";
        } else {
            $steps_log[] = "<strong>Fehler bei der SQL-Migration für Version $version:</strong> " . $_database->error;
        }

        // Temporäre Datei löschen
        unlink($sql_file);
        $steps_log[] = "→ Temporäre Datei für Version $version gelöscht.";

        $new_version = $version; // merke letzte installierte Version
    }

    // Neue Version speichern
    file_put_contents(__DIR__ . '/version.txt', $new_version);
    $steps_log[] = "<strong>Systemversion wurde aktualisiert auf $new_version</strong>";

    $data_array['update_status'] = implode("<br>", $steps_log) . "<br><strong>Alle verfügbaren Updates wurden angewendet.</strong>";
} else {
    // Anzeige nur (GET)
    $data_array['show_button'] = !empty($updates); // Button sichtbar, wenn Updates vorhanden
}

echo $tpl->loadTemplate("update_core", "content", $data_array, "admin");
