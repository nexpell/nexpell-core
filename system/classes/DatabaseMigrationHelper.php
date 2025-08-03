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
            $this->runQuery("ALTER TABLE `$table` ADD COLUMN $columnDefinition");
            $this->log("Spalte '$column' zu '$table' hinzugefügt.");
        } else {
            $this->log("Spalte '$column' in '$table' bereits vorhanden.");
        }
    }

    public function dropColumnIfExists(string $table, string $column): void
    {
        global $_database;

        if ($this->columnExists($table, $column)) {
            $this->runQuery("ALTER TABLE `$table` DROP COLUMN `$column`");
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
        $this->runQuery($tableSQL);

        preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/i', $tableSQL, $matches);
        $table = $matches[1] ?? 'unbekannt';

        $this->log("Tabelle '$table' überprüft bzw. erstellt.");
    }

    public function escapeIdentifier(string $identifier): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }

    public function dropTableIfExists(string $table): void
    {
        $table = $this->escapeIdentifier($table);
        $this->runQuery("DROP TABLE IF EXISTS `$table`;");
        $this->log("🗑️ Tabelle '$table' gelöscht (falls vorhanden).");
    }

    // Neu ergänzte Methode:
    public function runQuery(string $query)
    {
        global $_database;

        $result = $_database->query($query);
        if (!$result) {
            throw new \RuntimeException("SQL-Fehler: " . $_database->error . " bei Query: $query");
        }
        return $result;
    }

    public function escape(string $value): string
    {
        global $_database;
        return $_database->real_escape_string($value);
    }
}
