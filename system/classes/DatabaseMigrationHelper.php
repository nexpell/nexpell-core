<?php
namespace nexpell;

class DatabaseMigrationHelper
{
    private array $log = [];

    public function log(string $message): void
    {
        $this->log[] = date('[Y-m-d H:i:s] ') . $message;
    }

    public function getLog(): string
    {
        return implode("\n", $this->log);
    }

    public function columnExists(string $table, string $column): bool
    {
        global $_database;

        // Achtung: Tabellenname darf nicht als Parameter übergeben werden, daher manuell escapen
        $table = $_database->real_escape_string($table);
        $column = $_database->real_escape_string($column);

        $result = $_database->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $result && $result->num_rows > 0;
    }

    public function addColumnIfNotExists(string $table, string $columnDefinition): void
    {
        global $_database;

        preg_match('/^`?(\w+)`?\s/i', $columnDefinition, $matches);
        $column = $matches[1] ?? null;

        if ($column && !$this->columnExists($table, $column)) {
            $_database->query("ALTER TABLE `$table` ADD COLUMN $columnDefinition");
            $this->log("Spalte '$column' zu '$table' hinzugefügt.");
        } else {
            $this->log("Spalte '$column' in '$table' bereits vorhanden.");
        }
    }

    public function dropColumnIfExists(string $table, string $column): void
    {
        global $_database;

        if ($this->columnExists($table, $column)) {
            $_database->query("ALTER TABLE `$table` DROP COLUMN `$column`");
            $this->log("Spalte '$column' von '$table' entfernt.");
        } else {
            $this->log("Spalte '$column' in '$table' nicht vorhanden – wird übersprungen.");
        }
    }

    public function tableExists(string $table): bool
    {
        global $_database;

        $table = $_database->real_escape_string($table);
        $result = $_database->query("SHOW TABLES LIKE '$table'");
        return $result && $result->num_rows > 0;
    }

    public function createTableIfNotExists(string $tableSQL): void
    {
        global $_database;

        $_database->query($tableSQL);

        preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/i', $tableSQL, $matches);
        $table = $matches[1] ?? 'unbekannt';

        $this->log("Tabelle '$table' überprüft bzw. erstellt.");
    }
}
