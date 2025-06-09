<?php
namespace webspell;

class CMSUpdater
{
    private string $log = '';

    public function runUpdates(): string
    {
        $this->log("CMS-Update gestartet...");

        $this->updateDatabase();
        $this->updateFiles();

        $this->log("Update abgeschlossen.");
        return $this->log;
    }

    private function updateDatabase(): void
    {
        global $_database;

        $this->log("Prüfe und aktualisiere Datenbank...");

        // Beispiel: neue Spalte einfügen, wenn sie nicht existiert
        $result = $_database->query("SHOW COLUMNS FROM `users` LIKE 'last_update'");
        if ($result->num_rows === 0) {
            $_database->query("ALTER TABLE `users` ADD `last_update` DATETIME DEFAULT NULL");
            $this->log("Spalte 'last_update' hinzugefügt.");
        } else {
            $this->log("Spalte 'last_update' bereits vorhanden.");
        }
    }

    private function updateFiles(): void
    {
        // Beispiel: neue Dateien aus einem Update-Ordner kopieren
        $update_dir = __DIR__ . '/../../updates/core/';
        $target_dir = __DIR__ . '/../../';

        if (is_dir($update_dir)) {
            $files = scandir($update_dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    copy($update_dir . $file, $target_dir . $file);
                    $this->log("Datei '$file' aktualisiert.");
                }
            }
        } else {
            $this->log("Kein Update-Verzeichnis gefunden.");
        }
    }

    private function log(string $message): void
    {
        $this->log .= date('[Y-m-d H:i:s] ') . $message . "\n";
    }
}
