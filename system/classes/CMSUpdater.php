<?php
namespace nexpell;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class CMSUpdater
{
    private array $log = [];

    public function runUpdates(): string
    {
        $this->logMsg("🚀 CMS-Update gestartet...");

        // --- Bootstrap-Systemdateien zuerst aktualisieren ---
        $this->updateCoreFiles();

        // --- Migrationen ausführen ---
        $this->runMigrations();

        // --- Temporäres Updateverzeichnis bereinigen ---
        $this->cleanupTmp();

        $this->logMsg("✅ Update abgeschlossen.");
        return $this->renderLog();
    }

    /**
     * Kopiert CMSUpdater.php & DatabaseMigrationHelper.php
     * aus dem temporären Update-Paket nach /system/classes/
     */
    private function updateCoreFiles(): void
    {
        $this->logMsg("🧩 Systemdateien aktualisieren...");

        $source = __DIR__ . '/../../admin/tmp/system/classes/';
        $target = __DIR__ . '/';

        $files = [
            'CMSUpdater.php',
            'DatabaseMigrationHelper.php'
        ];

        // 👉 Dateien, die bei bestimmten Übergangs-Versionen fehlen dürfen
        $skipMissing = [];
        if (defined('CURRENT_VERSION') && CURRENT_VERSION === '1.0.1') {
            // Bei Update 1.0.2 bewusst keine Warnung
            $skipMissing = $files;
        }

        foreach ($files as $file) {
            $src = $source . $file;
            $dst = $target . $file;

            if (file_exists($src)) {
                if (@copy($src, $dst)) {
                    $this->logMsg("✅ $file → erfolgreich nach /system/classes/ kopiert.");
                } else {
                    $this->logMsg("❌ Fehler: $file konnte nicht kopiert werden!");
                }
            } else {
                // ❗️nur loggen, wenn Datei NICHT in Skip-Liste
                if (!in_array($file, $skipMissing, true)) {
                    $this->logMsg("⚠️ Datei $file fehlt im Update-Paket.");
                } else {
                    $this->logMsg("ℹ️ $file wird bei Version 1.0.2 nicht aktualisiert (Übergangsupdate).");
                }
            }
        }
    }


    /**
     * Führt Migrationen (z. B. /admin/tmp/migrations/*.php) aus
     */
    private function runMigrations(): void
    {
        $this->logMsg("🔄 Migrationen ausführen...");

        $migrationDir = __DIR__ . '/../../admin/tmp/migrations/';
        if (!is_dir($migrationDir)) {
            $this->logMsg("⚠️ Kein Migrationsordner gefunden ($migrationDir).");
            return;
        }

        $migrations = glob($migrationDir . '*.php');
        if (!$migrations) {
            $this->logMsg("ℹ️ Keine Migrationsdateien gefunden.");
            return;
        }

        // Nach Version sortieren (z. B. 1.0.0, 1.0.1, 1.0.2)
        sort($migrations, SORT_NATURAL);

        // Nur die höchste Version behalten
        $latestFile = end($migrations);
        $latestVersion = basename($latestFile, '.php');

        $this->logMsg("📦 Es wird nur die neueste Migration ausgeführt: Version $latestVersion");

        foreach ($migrations as $migrationFile) {
            $version = basename($migrationFile, '.php');

            // Nur die höchste Version wirklich ausführen
            if ($version !== $latestVersion) {
                $this->logMsg("⏩ Überspringe ältere Migration $version (bereits veraltet).");
                continue;
            }

            try {
                $this->logMsg("▶️ Starte Migration für Version $version...");
                include $migrationFile;
                $this->logMsg("✅ Migration $version erfolgreich abgeschlossen.");
            } catch (\Throwable $e) {
                $this->logMsg("❌ Fehler in Migration $version: " . $e->getMessage());
            }
        }
    }


    /**
     * Löscht /admin/tmp/ nach erfolgreichem Update
     */
    private function cleanupTmp(): void
    {
        $tmpDir = __DIR__ . '/../../admin/tmp/';
        if (!is_dir($tmpDir)) {
            $this->logMsg("ℹ️ Kein temporäres Verzeichnis vorhanden.");
            return;
        }

        $this->logMsg("🧹 Bereinige temporäres Verzeichnis...");

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
            @$todo($fileinfo->getRealPath());
        }

        @rmdir($tmpDir);
        $this->logMsg("✅ Temporäres Verzeichnis gelöscht (/admin/tmp/).");
    }

    // --- Logging-Helfer ---
    private function logMsg(string $message): void
    {
        $this->log[] = date('[Y-m-d H:i:s] ') . $message;
    }

    private function renderLog(): string
    {
        $html = "<div class='p-3 bg-light border rounded'>";
        foreach ($this->log as $entry) {
            if (str_contains($entry, '❌')) {
                $html .= "<div class='alert alert-danger py-1 my-1'><i class='bi bi-x-circle-fill me-2'></i>" . htmlspecialchars($entry) . "</div>";
            } elseif (str_contains($entry, '⚠️')) {
                $html .= "<div class='alert alert-warning py-1 my-1'><i class='bi bi-exclamation-triangle-fill me-2'></i>" . htmlspecialchars($entry) . "</div>";
            } elseif (str_contains($entry, '✅')) {
                $html .= "<div class='alert alert-success py-1 my-1 small'><i class='bi bi-check-circle-fill me-2'></i>" . htmlspecialchars($entry) . "</div>";
            } else {
                $html .= "<div class='text-muted small'>" . htmlspecialchars($entry) . "</div>";
            }
        }
        $html .= "</div>";
        return $html;
    }
}
