<?php

// Überprüfen, ob die Session bereits gestartet wurde
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;

// Initialisieren
global $_database,$languageService;
$lang = $languageService->detectLanguage();
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('database', true);

use nexpell\AccessControl;
// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_database');

if (isset($_POST['upload'])) {


    $upload = $_FILES['sql'];
    $CAPCLASS = new \nexpell\Captcha;
    if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {
        if ($upload['name'] != "") {
            $get = safe_query("SELECT DATABASE()");
            $ret = mysqli_fetch_array($get);
            $db = $ret[0];
            //drop all tables from nexpell DB
            $result = mysqli_query($_database, "SHOW TABLES FROM " . $db);
            while ($table = mysqli_fetch_array($result)) {
                safe_query("DROP TABLE `" . $table[0] . "`");
            }

            $tmpFile = tempnam('../tmp/', '.database');
            move_uploaded_file($upload['tmp_name'], $tmpFile);
            $new_query = file($tmpFile);
            foreach ($new_query as $query) {
                @mysqli_query($_database, $query);
            }
            @unlink($tmpFile);
        }
    } else {
        echo $languageService->get('transaction_invalid');
    }
}

if (isset($_GET['action'])) {
    $action = $_GET['action'];
} else {
    $action = '';
}

if (isset($_GET['back'])) {
    $returnto = $_GET['back'];
} else {
    $returnto = "database";
}

if (isset($_GET['delete'])) {

    $filepath = "myphp-backup-files/";
    $CAPCLASS = new \nexpell\Captcha;
    if ($CAPCLASS->checkCaptcha(0, $_GET['captcha_hash'])) {

        $id = $_GET['id'];
        $dg = mysqli_fetch_array(safe_query("SELECT * FROM backups WHERE id='" . $_GET['id'] . "'"));

        safe_query("DELETE FROM backups WHERE id='" . $_GET['id'] . "'");

        $file = $dg['filename'];

        if (file_exists($filepath . $file)) {
            @unlink($filepath . $file);
        }
    } else {
        echo $languageService->get('transaction_invalid');
    }
}

if ($action == "optimize") {
    $_language->readModule('database', false, true);

    echo '<h1>&curren; ' . $languageService->get('database') . '</h1>';

    if (!ispageadmin($userID) || mb_substr(basename($_SERVER['REQUEST_URI']), 0, 15) != "admincenter.php") {
        die($languageService->get('access_denied'));
    }

    $get = safe_query("SELECT DATABASE()");
    $ret = mysqli_fetch_array($get);
    $db = $ret[0];

    $result = mysqli_query($_database, "SHOW TABLES FROM " . $db);
    while ($table = mysqli_fetch_array($result)) {
        safe_query("OPTIMIZE TABLE `" . $table[0] . "`");
    }
    redirect('admincenter.php?site=' . $returnto, '', 0);
} elseif ($action == "write") {


// Definiere Backup-Verzeichnis relativ zum Skriptverzeichnis
define('BACKUP_DIR', __DIR__ . '/myphp-backup-files');

define('IGNORE_TABLES', ''); // Beispiel: 'table1,table2'
define('BACKUP_DATABASENAME', 'myphp');
define('BACKUP_USER', 'root');
define('BACKUP_PASSWORD', '');
define('BACKUP_HOST', 'localhost');
define('BACKUP_CHARSET', 'utf8mb4');
define('BATCH_SIZE', 1000);
define('USE_GZIP', true);

class Backup_Database
{
    private $conn;
    private $backupDir;
    private $backupFile;
    private $db;
    private $ignoreTables = [];
    private $batchSize;
    private $useGzip;

    public function __construct($dbHost, $dbUser, $dbPass, $dbName, $backupDir = BACKUP_DIR, $ignoreTables = '', $batchSize = BATCH_SIZE, $useGzip = USE_GZIP)
    {
        $this->db = $dbName;
        $this->backupDir = $backupDir;
        $this->ignoreTables = array_filter(array_map('trim', explode(',', $ignoreTables)));
        $this->batchSize = $batchSize;
        $this->useGzip = $useGzip;

        // Verbindung zur DB aufbauen
        $this->conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if ($this->conn->connect_error) {
            die('MySQL Verbindung fehlgeschlagen: ' . $this->conn->connect_error);
        }
        // Zeichensatz setzen
        $this->conn->set_charset(BACKUP_CHARSET);

        // Backup-Verzeichnis anlegen, falls nicht vorhanden
        if (!is_dir($this->backupDir)) {
            if (!mkdir($this->backupDir, 0777, true) && !is_dir($this->backupDir)) {
                die('Backup-Verzeichnis konnte nicht erstellt werden: ' . $this->backupDir);
            }
        }

        $this->backupFile = $this->backupDir . '/' . $this->db . '-' . date('Y-m-d_H-i-s') . '.sql';
    }

    public function backupTables($tables = '*')
    {
        // Tabellenliste ermitteln
        if ($tables === '*') {
            $result = $this->conn->query("SHOW TABLES");
            if (!$result) {
                die('Fehler bei SHOW TABLES: ' . $this->conn->error);
            }
            $tables = [];
            while ($row = $result->fetch_array()) {
                $tblName = $row[0];
                if (!in_array($tblName, $this->ignoreTables)) {
                    $tables[] = $tblName;
                }
            }
        } else {
            if (is_string($tables)) {
                $tables = array_map('trim', explode(',', $tables));
            }
            $tables = array_diff($tables, $this->ignoreTables);
        }

        $sqlDump = "-- MySQL Backup von Datenbank `{$this->db}`\n-- Erzeugt: " . date('Y-m-d H:i:s') . "\n\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            // Tabellenstruktur sichern
            $result = $this->conn->query("SHOW CREATE TABLE `$table`");
            if (!$result) {
                die('Fehler bei SHOW CREATE TABLE für ' . $table . ': ' . $this->conn->error);
            }
            $row = $result->fetch_assoc();
            $sqlDump .= "--\n-- Tabellenstruktur für Tabelle `$table`\n--\n\n";
            $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sqlDump .= $row['Create Table'] . ";\n\n";

            // Optional Trigger sichern (auskommentiert, bei Bedarf aktivieren)
            /*
            $resultTriggers = $this->conn->query("SHOW TRIGGERS LIKE '$table'");
            if ($resultTriggers && $resultTriggers->num_rows > 0) {
                $sqlDump .= "--\n-- Trigger für Tabelle `$table`\n--\n\n";
                while ($trigger = $resultTriggers->fetch_assoc()) {
                    $sqlDump .= "DROP TRIGGER IF EXISTS `{$trigger['Trigger']}`;\n";
                    $sqlDump .= $trigger['SQL Original Statement'] . ";\n\n";
                }
            }
            */

            // Tabellendaten sichern (Batch-weise)
            $result = $this->conn->query("SELECT COUNT(*) AS numRows FROM `$table`");
            if (!$result) {
                die('Fehler bei COUNT(*) für ' . $table . ': ' . $this->conn->error);
            }
            $numRows = (int)$result->fetch_assoc()['numRows'];
            if ($numRows === 0) {
                continue; // Keine Daten
            }

            $numBatches = (int)ceil($numRows / $this->batchSize);
            $sqlDump .= "--\n-- Daten der Tabelle `$table` (insgesamt $numRows Datensätze)\n--\n\n";

            for ($batch = 0; $batch < $numBatches; $batch++) {
                $offset = $batch * $this->batchSize;
                $resultData = $this->conn->query("SELECT * FROM `$table` LIMIT $offset, $this->batchSize");
                if (!$resultData) {
                    die('Fehler beim Daten-SELECT für ' . $table . ': ' . $this->conn->error);
                }
                $rows = $resultData->fetch_all(MYSQLI_NUM);
                if (count($rows) > 0) {
                    $sqlDump .= "INSERT INTO `$table` VALUES\n";
                    $rowCount = count($rows);
                    foreach ($rows as $i => $row) {
                        $sqlDump .= "(";
                        foreach ($row as $j => $value) {
                            if (is_null($value)) {
                                $sqlDump .= "NULL";
                            } elseif (is_numeric($value) && !$this->isBooleanString($value)) {
                                // Zahlen als solche schreiben, außer 'true'/'false'
                                $sqlDump .= $value;
                            } elseif ($this->isBooleanString($value)) {
                                // Boolean Strings ohne Anführungszeichen
                                $sqlDump .= strtoupper($value);
                            } else {
                                $sqlDump .= '"' . $this->conn->real_escape_string($value) . '"';
                            }
                            if ($j < count($row) - 1) {
                                $sqlDump .= ",";
                            }
                        }
                        $sqlDump .= ")";
                        $sqlDump .= ($i < $rowCount - 1) ? ",\n" : ";\n\n";
                    }
                }
            }
        }

        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        // Backup-Datei speichern
        if ($this->useGzip) {
            $this->saveBackupGzip($sqlDump);
        } else {
            file_put_contents($this->backupFile, $sqlDump);
        }

        return $this->backupFile . ($this->useGzip ? '.gz' : '');
    }

    private function saveBackupGzip($data)
    {
        $gzipFile = $this->backupFile . '.gz';
        $gz = gzopen($gzipFile, 'wb9');
        if (!$gz) {
            die('Konnte gzip Backup-Datei nicht erstellen.');
        }
        gzwrite($gz, $data);
        gzclose($gz);
    }

    private function isBooleanString($value)
    {
        $lower = strtolower($value);
        return $lower === 'true' || $lower === 'false';
    }

    public function close()
    {
        $this->conn->close();
    }
}

// Beispiel zur Nutzung

$backup = new Backup_Database(
    BACKUP_HOST,
    BACKUP_USER,
    BACKUP_PASSWORD,
    BACKUP_DATABASENAME,
    BACKUP_DIR,
    IGNORE_TABLES,
    BATCH_SIZE,
    USE_GZIP
);

$filename = $backup->backupTables(); // Alle Tabellen sichern

echo "Backup erstellt: $filename\n";

$backup->close();

} elseif ($action == "back") {

    /**
     * This file contains the Restore_Database class wich performs
     * a partial or complete restoration of any given MySQL database
     * @author Daniel López Azaña <daniloaz@gmail.com>
     * @version 1.0
     */

    /**
     * Define database parameters here
     */

    $ds = mysqli_fetch_array(safe_query(
        "SELECT * FROM backups WHERE id='" . $_GET["id"] . "'AND filename=filename"
    ));
    include("../system/sql.php");
    global $filename;

    define("DB_USER", $user);
    define("DB_PASS", $pwd);
    define("DB_NAME", $db);
    define("DB_HOST", $host);
    define("BACKUP_DIR", 'myphp-backup-files'); // Comment this line to use same script's directory ('.')
    define("BACKUP_FILE", $ds['filename']); // Script will autodetect if backup file is gzipped based on .gz extension
    define("CHARSET", 'utf8');
    define("DISABLE_FOREIGN_KEY_CHECKS", true); // Set to true if you are having foreign key constraint fails
    print_r($filename);
    /**
     * The Restore_Database class
     */
    class Restore_Database
    {
        private string $backupDir;
        private string $backupFile;

        public function setBackupDir($dir)
        {
            $this->backupDir = $dir;
        }

        public function setBackupFile($file)
        {
            $this->backupFile = $file;
        }

        public function getBackupDir()
        {
            return $this->backupDir;
        }

        public function getBackupFile()
        {
            return $this->backupFile;
        }
        /**
         * Host where the database is located
         */
        var $host;

        /**
         * Username used to connect to database
         */
        var $user;

        /**
         * Password used to connect to database
         */
        var $pwd;

        /**
         * Database to backup
         */
        var $db;

        /**
         * Database charset
         */
        var $charset;

        /**
         * Database connection
         */
        var $conn;

        /**
         * Disable foreign key checks
         */
        var $disableForeignKeyChecks;

        /**
         * Constructor initializes database
         */
        public function __construct($host, $user, $pwd, $db, $charset = 'utf8')
        {
            $this->host                    = $host;
            $this->user                    = $user;
            $this->pwd                     = $pwd;
            $this->db                      = $db;
            $this->charset                 = $charset;
            $this->disableForeignKeyChecks = defined('DISABLE_FOREIGN_KEY_CHECKS') ? DISABLE_FOREIGN_KEY_CHECKS : true;
            $this->conn                    = $this->initializeDatabase();
            $this->backupDir               = defined('BACKUP_DIR') ? BACKUP_DIR : '.';
            $this->backupFile              = defined('BACKUP_FILE') ? BACKUP_FILE : null;
        }

        /**
         * Destructor re-enables foreign key checks
         */
        function __destructor()
        {
            /**
             * Re-enable foreign key checks 
             */
            if ($this->disableForeignKeyChecks === true) {
                mysqli_query($this->conn, 'SET foreign_key_checks = 1');
            }
        }

        protected function initializeDatabase()
        {
            try {
                $conn = mysqli_connect($this->host, $this->user, $this->pwd, $this->db);
                if (mysqli_connect_errno()) {
                    throw new Exception('ERROR connecting database: ' . mysqli_connect_error());
                    die();
                }
                if (!mysqli_set_charset($conn, $this->charset)) {
                    mysqli_query($conn, 'SET NAMES ' . $this->charset);
                }

                /**
                 * Disable foreign key checks 
                 */
                if ($this->disableForeignKeyChecks === true) {
                    mysqli_query($conn, 'SET foreign_key_checks = 0');
                }
            } catch (Exception $e) {
                print_r($e->getMessage());
                die();
            }

            return $conn;
        }

        /**
         * Backup the whole database or just some tables
         * Use '*' for whole database or 'table1 table2 table3...'
         * @param string $tables
         */
        public function restoreDb()
        {
            try {
                $sql = '';
                $multiLineComment = false;

                $backupDir = $this->backupDir;
                $backupFile = $this->backupFile;

                /**
                 * Gunzip file if gzipped
                 */
                $backupFileIsGzipped = substr($backupFile, -3, 3) == '.gz' ? true : false;
                if ($backupFileIsGzipped) {
                    if (!$backupFile = $this->gunzipBackupFile()) {
                        throw new Exception("ERROR: couldn't gunzip backup file " . $backupDir . '/' . $backupFile);
                    }
                }

                /**
                 * Read backup file line by line
                 */
                $handle = fopen($backupDir . '/' . $backupFile, "r");
                if ($handle) {
                    while (($line = fgets($handle)) !== false) {
                        $line = ltrim(rtrim($line));
                        if (strlen($line) > 1) { // avoid blank lines
                            $lineIsComment = false;
                            if (preg_match('/^\/\*/', $line)) {
                                $multiLineComment = true;
                                $lineIsComment = true;
                            }
                            if ($multiLineComment or preg_match('/^\/\//', $line)) {
                                $lineIsComment = true;
                            }
                            if (!$lineIsComment) {
                                $sql .= $line;
                                if (preg_match('/;$/', $line)) {
                                    // execute query
                                    if (mysqli_query($this->conn, $sql)) {
                                        if (preg_match('/^CREATE TABLE `([^`]+)`/i', $sql, $tableName)) {
                                            $this->obfPrint("Table succesfully created: `" . $tableName[1] . "`");
                                        }
                                        $sql = '';
                                    } else {
                                        throw new Exception("ERROR: SQL execution error: " . mysqli_error($this->conn));
                                    }
                                }
                            } else if (preg_match('/\*\/$/', $line)) {
                                $multiLineComment = false;
                            }
                        }
                    }
                    fclose($handle);
                } else {
                    throw new Exception("ERROR: couldn't open backup file " . $backupDir . '/' . $backupFile);
                }
            } catch (Exception $e) {
                print_r($e->getMessage());
                return false;
            }

            if ($backupFileIsGzipped) {
                unlink($backupDir . '/' . $backupFile);
            }

            return true;
        }

        /*
     * Gunzip backup file
     *
     * @return string New filename (without .gz appended and without backup directory) if success, or false if operation fails
     */
        protected function gunzipBackupFile()
        {
            // Raising this value may increase performance
            $bufferSize = 4096; // read 4kb at a time
            $error = false;

            $source = $this->backupDir . '/' . $this->backupFile;
            $dest = $this->backupDir . '/' . date("Ymd_His", time()) . '_' . substr($this->backupFile, 0, -3);

            $this->obfPrint('Gunzipping backup file ' . $source . '... ', 1, 1);

            // Remove $dest file if exists
            if (file_exists($dest)) {
                if (!unlink($dest)) {
                    return false;
                }
            }

            // Open gzipped and destination files in binary mode
            if (!$srcFile = gzopen($this->backupDir . '/' . $this->backupFile, 'rb')) {
                return false;
            }
            if (!$dstFile = fopen($dest, 'wb')) {
                return false;
            }

            while (!gzeof($srcFile)) {
                // Read buffer-size bytes
                // Both fwrite and gzread are binary-safe
                if (!fwrite($dstFile, gzread($srcFile, $bufferSize))) {
                    return false;
                }
            }

            fclose($dstFile);
            gzclose($srcFile);

            // Return backup filename excluding backup directory
            return str_replace($this->backupDir . '/', '', $dest);
        }

        /**
         * Prints message forcing output buffer flush
         *
         */
        public function obfPrint($msg = '', $lineBreaksBefore = 0, $lineBreaksAfter = 1)
        {
            if (!$msg) {
                return false;
            }

            $msg = date("Y-m-d H:i:s") . ' - ' . $msg;
            $output = '';

            if (php_sapi_name() != "cli") {
                $lineBreak = "<br />";
            } else {
                $lineBreak = "\n";
            }

            if ($lineBreaksBefore > 0) {
                for ($i = 1; $i <= $lineBreaksBefore; $i++) {
                    $output .= $lineBreak;
                }
            }

            $output .= $msg;

            if ($lineBreaksAfter > 0) {
                for ($i = 1; $i <= $lineBreaksAfter; $i++) {
                    $output .= $lineBreak;
                }
            }

            if (php_sapi_name() == "cli") {
                $output .= "\n";
            }

            echo $output;

            if (php_sapi_name() != "cli") {
                ob_flush();
            }

            flush();
        }
    }

    /**
     * Instantiate Restore_Database and perform backup
     */
    // Report all errors
    error_reporting(E_ALL);
    // Set script max execution time
    set_time_limit(900); // 15 minutes

    if (php_sapi_name() != "cli") {
        echo '<div class="card">
  <div class="card-header">
            Backup
        </div>
  <div class="card-body"><div class="alert alert-info" role="alert">';
    }

    $restoreDatabase = new Restore_Database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $result = $restoreDatabase->restoreDb(BACKUP_DIR, BACKUP_FILE) ? 'OK' : 'KO';
    $restoreDatabase->obfPrint("Restoration result: " . $result, 1);

    if (php_sapi_name() != "cli") {
        echo '</div><a class="btn btn-secondary" href="admincenter.php?site=database" role="button">Go Back</a></div></div>';
    }
} else {
    #$_language->readModule('database', false, true);

    //if (!ispageadmin($userID) || mb_substr(basename($_SERVER['REQUEST_URI']), 0, 15) != "admincenter.php") {
    //    die($languageService->get('access_denied']);
    //}

    $CAPCLASS = new \nexpell\Captcha;
    $CAPCLASS->createTransaction();
    $hash = $CAPCLASS->getHash();

    echo '<div class="card">
            <div class="card-header"><i class="bi bi-database"></i> 
                ' . $languageService->get('database') . '
            </div>
                <div class="card-body">

<div class="row">

<div class="col-md-6">

    <div class="mb-3 row bt">
        <label class="col-sm-4 control-label">' . $languageService->get('select_option') . '</label>
        <div class="col-sm-7"><span class="text-muted small"><em>
            <a class="btn btn-primary" href="admincenter.php?site=database&amp;action=write&amp;captcha_hash=' . $hash . '"><i class="bi bi-database-down"></i> ' . $languageService->get('export') . '</a><br><br>
            ' . $languageService->get('import_info1') . '</em></span>
        </div>
    </div>

        <div class="mb-4 row bt">
        <label class="col-sm-4 control-label">' . $languageService->get('optimize') . '</label>
        <div class="col-sm-7"><span class="text-muted small"><em>
            <a class="btn btn-primary" href="admincenter.php?site=database&amp;action=optimize"><i class="bi bi-database-gear"></i> ' . $languageService->get('optimize') . '</a></em></span>
        </div>
    </div>

</div>


<div class="col-md-6">
    <form class="bt" method="post" action="admincenter.php?site=database" enctype="multipart/form-data">

    <div class="mb-3 row bt">
        <label class="col-sm-4 control-label">' . $languageService->get('backup_file') . '</label>
        <div class="col-sm-7"><span class="text-muted small"><em>
            <input name="sql" type="file" size="40" / ><br><br>
    
        <input type="hidden" name="captcha_hash" value="' . $hash . '" />
        <button class="btn btn-primary" type="submit" name="upload"  /><i class="bi bi-filetype-sql"></i> ' . $languageService->get('upload') . '</button></em></span>
        </div>
    </div>
</form>

</div>



</div>
    
</div>

</div>';



    echo '<div class="card">
            <div class="card-header">
                <i class="bi bi-braces"></i> ' . $languageService->get('sql_query') . '
            </div>
            <div class="card-body">';

    echo '<form method="post" action="admincenter.php?site=database">
  <table class="table">
  <thead>
    <tr>
    <td colspan="8" bgcolor="#ffe6e6" style="font-weight: bold; color: #333; line-height: 20px;padding: 10px;">
    ' . $languageService->get('import_info2') . '
    </td></tr>
    <tr>
      <th>ID</th>
      <th>' . $languageService->get('file') . '</th>
      <th>' . $languageService->get('date') . '</th>
      <th>' . $languageService->get('created_by') . '</th>
      <th>' . $languageService->get('actions') . '</th>
    </tr>
    </thead>
  <tbody>';

    $CAPCLASS = new \nexpell\Captcha;
    $CAPCLASS->createTransaction();
    $hash = $CAPCLASS->getHash();

    $select_query = "SELECT * FROM `backups`";
    $result = safe_query($select_query);
    $backups = array();
    while ($ds = mysqli_fetch_array($result)) {


        $id             = $ds['id'];
        $filename       = $ds['filename'];
        $description    = $ds['description'];
        $createdby      = getusername($ds['createdby']);
        $createdate     = date("d/m/Y h:i:sa", strtotime($ds['createdate']));
        $file_exists    = "<img src='../images/icons/offline.gif' alt='' />";
        $download_url   = "/admin/myphp-backup-files/";

        echo '<tr>
    <td>' . $ds['id'] . '</td>
    <td>' . $ds['description'] . '</td>
    <td>' . $ds['createdate'] . '</td>
    <td>' . $createdby . '</td>
    
    <td>
        <a class="btn btn-primary" href="' . $download_url . '' . $description . '" role="button"><i class="bi bi-download"></i> Download</a>

        <a type="button" class="btn btn-warning" href="admincenter.php?site=database&amp;action=back&amp;id=' . $ds['id'] . '" ><i class="bi bi-database-up"></i> ' . $languageService->get('upload') . '</a>

<!-- Button trigger modal -->
    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete" data-href="admincenter.php?site=database&amp;delete=true&amp;id=' . $ds['id'] . '&amp;captcha_hash=' . $hash . '"><i class="bi bi-trash3"></i> 
    ' . $languageService->get('delete') . '
    </button>
    <!-- Button trigger modal END-->

     <!-- Modal -->
<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel"><i class="bi bi-braces"></i> ' . $languageService->get('sql_query') . '</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' . $languageService->get('close') . '"></button>
      </div>
      <div class="modal-body"><p>' . $languageService->get('really_delete') . '</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . $languageService->get('close') . '</button>
        <a class="btn btn-danger btn-ok"><i class="bi bi-trash3"></i> ' . $languageService->get('delete') . '</a>
      </div>
    </div>
  </div>
</div>
<!-- Modal END -->

  </td>
  </tr>';
        $first_line = "";
    }

    echo '</tbody></table>


  </form>
  </div></div>';
}
