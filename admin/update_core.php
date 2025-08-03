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

define("CURRENT_VERSION", trim(@file_get_contents(__DIR__ . '/version.txt')) ?: "1.0.0");

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

/*$update_text = "<strong>Folgende Updates sind verfügbar:</strong><ul>";
foreach ($updates as $update) {
    $update_text .= "<li><strong>Version:</strong> {$update['version']}<br>{$update['changelog']}</li>";
}
$update_text .= "</ul>";*/

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


#$data_array['update_status'] = $update_text;
#$data_array['show_button'] = !empty($updates);

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
        $sql_file = __DIR__ . "/update_core/migrations/{$version}.php";

        $steps_log[] = "→ Lade Update $version herunter...";
        $steps_log[] = "<div class=\"alert alert-secondary\" role=\"alert\">
            <strong>Starte Update für Version $version...</strong>
        </div>";

        $zip_content = @file_get_contents($zip_url);
        if (!$zip_content || !file_put_contents($zip_file, $zip_content)) {
            $steps_log[] = "<div class=\"alert alert-danger\" role=\"alert\">
                <i class=\"bi bi-exclamation-triangle-fill me-2\"></i>
                <strong>Fehler:</strong> Update-Datei konnte nicht heruntergeladen oder gespeichert werden.
            </div>";
            if (file_exists($zip_file)) {
                unlink($zip_file);
            }
            continue;
        }

        $zip = new ZipArchive;
        if ($zip->open($zip_file) !== TRUE) {
            $steps_log[] = "<div class=\"alert alert-danger\" role=\"alert\">
                <strong>Fehler:</strong> ZIP-Archiv von Version $version konnte nicht geöffnet werden.
            </div>";
            unlink($zip_file);
            continue;
        }

        $replaced_files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $target_file = $extract_path . '/' . $filename;
            if (file_exists($target_file)) {
                $replaced_files[] = $filename;
            }
        }

        $zip->extractTo($extract_path);
        $zip->close();

        $steps_log[] = "→ Update $version erfolgreich entpackt.";

        if (!empty($replaced_files)) {
            $filtered_files = array_filter($replaced_files, function ($file) {
                return !str_starts_with($file, 'admin/update_core/') && !is_dir(__DIR__ . '/../' . $file);
            });

            if (!empty($filtered_files)) {
                $steps_log[] = "→ Überschriebene Dateien:<br><div class=\"alert alert-info\" role=\"alert\"><pre>" .
                    htmlspecialchars(implode("\n", $filtered_files)) .
                    "</pre></div>";
            }
        }

        if (isset($update['delete_files']) && is_array($update['delete_files'])) {
            foreach ($update['delete_files'] as $relativePath) {
                $fullPath = $extract_path . '/' . $relativePath;
                if (file_exists($fullPath)) {
                    if (@unlink($fullPath)) {
                        $steps_log[] = "→ Alte Datei gelöscht: <code>$relativePath</code>";
                    } else {
                        $steps_log[] = "<div class=\"alert alert-warning\">⚠️ Konnte <code>$relativePath</code> nicht löschen.</div>";
                    }
                }
            }
        }

        if (!file_exists($sql_file)) {
            $steps_log[] = "<div class=\"alert alert-danger\" role=\"alert\">
                <i class=\"bi bi-file-earmark-x-fill me-2\"></i>
                <strong>Fehler:</strong> Migrationsdatei <code>$sql_file</code> nicht gefunden.
            </div>
            <div class=\"alert alert-danger mt-3\" role=\"alert\">
                <i class=\"bi bi-x-circle me-2\"></i>
                <strong>Update auf Version $version nicht erfolgreich abgeschlossen.</strong>
            </div>";
        } else {
            try {
                global $migrator;
                $migrator = new \nexpell\DatabaseMigrationHelper($_database);

                include $sql_file;

                $steps_log[] = "→ PHP-Migration für Version $version abgeschlossen.";

                $migration_log = $migrator->getLog();
                if ($migration_log) {
                    $steps_log[] = "<div class=\"alert alert-info\" role=\"alert\"><pre>" . htmlspecialchars($migration_log) . "</pre></div>";
                }

                $steps_log[] = "<div class=\"alert alert-success mt-3\" role=\"alert\">
                    <i class=\"bi bi-check-circle-fill me-2\"></i>
                    <strong>Update auf Version $version erfolgreich abgeschlossen.</strong>
                </div>";

                $new_version = $version;

            } catch (Throwable $e) {
                $steps_log[] = "<div class=\"alert alert-danger\" role=\"alert\">
                    <i class=\"bi bi-bug-fill me-2\"></i>
                    <strong>Fehler bei der Migration für Version $version:</strong><br>" . htmlspecialchars($e->getMessage()) . "
                </div>
                <div class=\"alert alert-danger mt-3\" role=\"alert\">
                    <i class=\"bi bi-x-circle me-2\"></i>
                    <strong>Update auf Version $version nicht erfolgreich abgeschlossen.</strong>
                </div>";
            } finally {
                // ✅ Wird immer ausgeführt, auch bei Fehler
                if (file_exists($sql_file)) {
                    if (@unlink($sql_file)) {
                        $steps_log[] = "→ Temporäre Datei <code>$sql_file</code> gelöscht.";
                    } else {
                        $steps_log[] = "<div class=\"alert alert-warning\">Konnte <code>$sql_file</code> nicht löschen.</div>";
                    }
                }

                if (file_exists($zip_file)) {
                    if (@unlink($zip_file)) {
                        $steps_log[] = "→ ZIP-Datei <code>$zip_file</code> gelöscht.";
                    } else {
                        $steps_log[] = "<div class=\"alert alert-warning\">Konnte <code>$zip_file</code> nicht löschen.</div>";
                    }
                }
            }

        }

        // Immer temporäre Dateien löschen
        if (file_exists($sql_file)) {
            unlink($sql_file);
            $steps_log[] = "→ Temporäre Datei <code>$sql_file</code> gelöscht.";
        }
        if (file_exists($zip_file)) {
            unlink($zip_file);
            $steps_log[] = "→ ZIP-Datei <code>$zip_file</code> gelöscht.";
        }
    }



   

    // Neue Version schreiben (nur wenn erfolgreich)
    if ($new_version !== CURRENT_VERSION) {
        file_put_contents(__DIR__ . '/version.txt', $new_version);
        $steps_log[] = "<div class=\"alert alert-success mt-4\" role=\"alert\">
            <h5><i class=\"bi bi-check-circle-fill me-2\"></i>System wurde aktualisiert auf Version: $new_version <i class=\"bi bi-check2 me-2\"></i></h5></div>";

        // Abschlussmeldung + Button zum Zurückkehren
        $steps_log[] = "<div class=\"alert alert-info mt-4\" role=\"alert\">
            <i class=\"bi bi-check-circle-fill me-2\"></i>
            <strong>Alle verfügbaren Updates wurden erfolgreich angewendet.</strong>
        </div>";

        // Nach dem Update-Vorgang: Alle Migrationsdateien löschen
        $migrations_dir = __DIR__ . "/update_core/migrations";
        if (is_dir($migrations_dir)) {
            $files = glob($migrations_dir . "/*.php");
            $deleted_count = 0;

            foreach ($files as $file) {
                if (is_file($file) && @unlink($file)) {
                    $deleted_count++;
                }
            }

            if ($deleted_count > 0) {
                $data_array['update_status'] .= "<br><div class=\"alert alert-info mt-3\" role=\"alert\">
                    <i class=\"bi bi-folder-x me-2\"></i>
                    <strong>$deleted_count Migrationsdatei(en) wurden gelöscht.</strong>
                </div>";
            } else {
                $data_array['update_status'] .= "<br><div class=\"alert alert-info mt-3\" role=\"alert\">
                    <i class=\"bi bi-folder me-2\"></i>
                    <strong>Keine Migrationsdateien zum Löschen vorhanden.</strong>
                </div>";
            }
        }

        $steps_log[] = "<a href=\"admincenter.php?site=update_core\" class=\"btn btn-primary mt-2\">
            <i class=\"bi bi-arrow-left-circle\"></i> Zurück zur Update-Übersicht
        </a>";
    } else {
        $steps_log[] = "<div class=\"alert alert-warning mt-3\" role=\"alert\">
            <i class=\"bi bi-exclamation-circle-fill me-2\"></i>
            <strong>Update wurde nicht abgeschlossen.</strong> Bitte prüfen Sie die Fehlermeldungen oben.
        </div>";
    }

    $data_array['update_status'] = implode("<br>", $steps_log);
}





echo $tpl->loadTemplate("update_core", "content", $data_array, "admin");
