<?php

// Funktion zur sicheren Ausgabe von Variablen
function show_var($var) {
    // Überprüfen, ob die Variable ein Skalarwert (z.B. String, Integer) ist
    if (is_scalar($var)) {
        return $var;
    } else {
        // Wenn es kein Skalarwert ist, wird die Variable zurückgegeben
        return $var;
    }
}


// Funktion zum Laden von CSS- und JS-Dateien aus einem Template-Verzeichnis
function headfiles($var, $path) {
    $css = "";
    $js = "\n";
    
    // Überprüfung für den Fall, dass CSS-Dateien geladen werden sollen
    switch ($var) {
        case "css":
            // Überprüfen, ob das Verzeichnis für CSS-Dateien existiert
            if (is_dir($path . "css/")) { 
                $subf = "css/"; 
            } else { 
                $subf = ""; 
            }
            
            // Array für die CSS-Dateien
            $f = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $path . $subf) . '*.css');
            $fc = count($f, COUNT_RECURSIVE);  // Zählen der CSS-Dateien

            // Wenn CSS-Dateien gefunden wurden, diese hinzufügen
            if ($fc > 0) {
                for ($b = 0; $b <= $fc - 2; $b++) {
                    $css .= '<link type="text/css" rel="stylesheet" href="./' . $f[$b] . '" />' . chr(0x0D) . chr(0x0A);
                }
            }
            return $css;
            break;

        // Überprüfung für den Fall, dass JS-Dateien geladen werden sollen
        case "js":
            // Überprüfen, ob das Verzeichnis für JS-Dateien existiert
            if (is_dir($path . "js/")) { 
                $subf2 = "js/"; 
            } else { 
                $subf2 = ""; 
            }
            
            // Array für die JS-Dateien
            $g = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $path . $subf2) . '*.js');
            $fc = count($g, COUNT_RECURSIVE);  // Zählen der JS-Dateien

            // Wenn JS-Dateien gefunden wurden, diese hinzufügen
            if ($fc > 0) {
                for ($d = 0; $d <= $fc - 2; $d++) {
                    $js .= '<script src="./' . $g[$d] . '"></script>' . chr(0x0D) . chr(0x0A);
                }
            }
            return $js;
            break;

        // Standardfall für ungültige Parameter
        default:
            return "<!-- invalid parameter, use 'css', 'js' or 'components' -->";
    }
}

// -- LOGIN SESSION -- //

// Prüfen, ob die Datei 'session.php' existiert und einbinden
if (file_exists('session.php')) {
    systeminc('session');
} else {
    systeminc('../system/session');
}

// Prüfen, ob die Datei 'ip.php' existiert und einbinden
if (file_exists('ip.php')) {
    systeminc('ip');
} else {
    systeminc('../system/ip');
}



// Funktion zur Zählung des Vorkommens eines Substrings in einem mehrdimensionalen Array
function substri_count_array($haystack, $needle)
{
    $return = 0;

    // Durchlaufe jedes Element im Array
    foreach ($haystack as $value) {
        // Falls das Element selbst ein Array ist, rekursiv die Funktion aufrufen
        if (is_array($value)) {
            $return += substri_count_array($value, $needle);
        } else {
            // Andernfalls, den Substring zählen, dabei Groß-/Kleinschreibung ignorieren
            $return += substr_count(strtolower($value), strtolower($needle));
        }
    }

    return $return;
}

// Funktion zur Ersetzung von Zeichen in einem String für den sicheren Gebrauch in JavaScript
function js_replace($string)
{
    // Ersetze Rückwärtsschrägstriche
    $output = preg_replace("/(\\\)/si", '\\\\\1', $string);

    // Ersetze bestimmte Zeichen durch ihre escape-codierten Entsprechungen
    $output = str_replace(
        array("\r\n", "\n", "'", "<script>", "</script>", "<noscript>", "</noscript>"),
        array("\\n", "\\n", "\'", "\\x3Cscript\\x3E", "\\x3C/script\\x3E", "\\x3Cnoscript\\x3E", "\\x3C/noscript\\x3E"),
        $output
    );

    return $output;
}

// Funktion zur Berechnung des Prozentsatzes
function percent($sub, $total, $dec = 2)
{
    // Überprüfe, ob $sub und $total numerisch sind und $total nicht null ist
    if (!is_numeric($sub) || !is_numeric($total) || $total == 0) {
        return 0; // Verhindere Divisionen durch null und ungültige Eingabewerte
    }

    // Berechne den Prozentsatz
    $perc = ($sub / $total) * 100;

    // Runde den Prozentsatz auf die angegebene Dezimalstellenanzahl
    return round($perc, $dec);
}



// Funktion, die eine Seite im Wartungsmodus anzeigt
function showlock(string $reason, int $time)
{
    // Holen des Seitentitels aus der Datenbank
    $gettitle = mysqli_fetch_array(safe_query("SELECT `hptitle` FROM `settings`"));
    $pagetitle = $gettitle['hptitle'];
    
    // Erstellen eines Datenarrays, um den Seitentitel und andere Variablen zu speichern
    $data_array = array();
    $data_array['$pagetitle'] = $pagetitle;

    // Prüfen, ob mod_rewrite aktiviert ist und die RewriteBase holen
    if (isset($GLOBALS['_modRewrite']) && $GLOBALS['_modRewrite']->enabled()) {
        $data_array['$rewriteBase'] = $GLOBALS['_modRewrite']->getRewriteBase();
    } else {
        $data_array['$rewriteBase'] = '';
    }

    // Hinzufügen des Grundes für den Wartungsmodus
    $data_array = [
        'reason' => $reason,
        'time' => $time
    ];

    // Einbinden der Lock-Seite
    include(__DIR__ . '/../includes/modules/lock.php');

    // Das Skript stoppen, damit keine weiteren Ausgaben erfolgen
    die();
}

// Prüft, ob die Webseite geschlossen ist und ob der Benutzer ein Admin ist
$res = safe_query("SELECT closed FROM settings");
$row = mysqli_fetch_assoc($res);
$closed = isset($row['closed']) ? (int)$row['closed'] : 0;

$currentPath = $_SERVER['SCRIPT_NAME'];
$isLoginPage = (strpos($currentPath, '/admin/login.php') !== false);

if (
    $closed === 1 &&
    !$isLoginPage &&
    (!isset($_SESSION['userID']))
) {
    $lockRes = safe_query("SELECT reason, time FROM settings_site_lock LIMIT 1");
    $lockRow = mysqli_fetch_assoc($lockRes);

    $reason = $lockRow['reason'] ?? 'Wartungsmodus aktiviert.';
    $time = isset($lockRow['time']) ? (int)$lockRow['time'] : 0;

    showlock($reason, $time);
}

// Funktion zur Überprüfung von Systemumgebungsvariablen
function checkenv($systemvar, $checkfor)
{
    // Überprüft, ob der Wert der Systemumgebungsvariable $systemvar den String $checkfor enthält
    return stristr(ini_get($systemvar), $checkfor);
}

// Funktion zur Verschlüsselung einer E-Mail-Adresse, um sie vor Spam-Bots zu schützen
function mail_protect($mailaddress)
{
    // Sicherstellen, dass die E-Mail-Adresse nicht leer ist
    if (empty($mailaddress)) {
        return '';
    }

    // Initialisierung der Variablen zur Speicherung der verschlüsselten E-Mail
    $protected_mail = "";

    // Umwandeln der E-Mail-Adresse in ein Array von ASCII-Werten
    $arr = unpack("C*", $mailaddress);

    // Durchlaufen jedes Werts im Array und Umwandlung in hexadezimale Form
    foreach ($arr as $entry) {
        // Hexadezimale Darstellung jedes Zeichens
        $protected_mail .= sprintf("%%%X", $entry);
    }

    // Rückgabe der verschlüsselten E-Mail-Adresse
    return $protected_mail;
}

// zum Prüfen
#echo mail_protect("example@example.com");

// Funktion zur Überprüfung, ob eine URL gültig ist
function validate_url($url)
{
    // Regulärer Ausdruck zur Validierung einer URL
    return preg_match(
        // @codingStandardsIgnoreStart
        "/^(ht|f)tps?:\/\/([^:@]+:[^:@]+@)?(?!\.)(\.?(?!-)[0-9\p{L}-]+(?<!-))+(:[0-9]{2,5})?(\/[^#\?]*(\?[^#\?]*)?(#.*)?)?$/sui",
        // @codingStandardsIgnoreEnd
        $url
    );
}

// Funktion zur Überprüfung, ob eine E-Mail-Adresse gültig ist
function validate_email($email)
{
    // Regulärer Ausdruck zur Validierung einer E-Mail-Adresse
    return preg_match(
        // @codingStandardsIgnoreStart
        "/^(?!\.)(\.?[\p{L}0-9!#\$%&'\*\+\/=\?^_`\{\|}~-]+)+@(?!\.)(\.?(?!-)[0-9\p{L}-]+(?<!-))+\.[\p{L}0-9]{2,}$/sui",
        // @codingStandardsIgnoreEnd
        $email
    );
}


// Funktion zur Kombination von zwei Arrays, wenn `array_combine` nicht existiert
if (!function_exists('array_combine')) {
    
    function array_combine($keyarray, $valuearray)
    {
        // Arrays für die Schlüssel und Werte initialisieren
        $keys = array();
        $values = array();
        $result = array();

        // Schlüssel aus dem ersten Array extrahieren
        foreach ($keyarray as $key) {
            $keys[] = $key;
        }

        // Werte aus dem zweiten Array extrahieren
        foreach ($valuearray as $value) {
            $values[] = $value;
        }

        // Kombination der Schlüssel und Werte in ein assoziatives Array
        foreach ($keys as $access => $resultkey) {
            $result[$resultkey] = $values[$access];
        }

        // Das resultierende assoziative Array zurückgeben
        return $result;
    }
}

// Funktion zur sicheren Vergleich von zwei Hash-Werten, wenn `hash_equals` nicht existiert
if (!function_exists("hash_equals")) {
    
    function hash_equals($known_str, $user_str)
    {
        $result = 0;

        // Sicherstellen, dass beide Parameter Strings sind
        if (!is_string($known_str)) {
            return false;
        }

        if (!is_string($user_str)) {
            return false;
        }

        // Überprüfen, ob die Länge der beiden Strings übereinstimmt
        if (strlen($known_str) != strlen($user_str)) {
            return false;
        }

        // Bitweise XOR-Operation, um die Unterschiede zwischen den Zeichen zu finden
        for ($j = 0; $j < strlen($known_str); $j++) {
            $result |= ord($known_str[$j]) ^ ord($user_str[$j]);
        }

        // Wenn keine Unterschiede vorliegen, sind die Strings gleich
        return $result === 0;
    }
}



function countempty($checkarray)
{
    $ret = 0;

    // Iteration über jedes Element im Array
    foreach ($checkarray as $value) {
        // Wenn das Element ein Array ist, rekursive Zählung aufrufen
        if (is_array($value)) {
            $ret += countempty($value);
        }
        // Wenn das Element leer ist (nach Trim) erhöhen wir den Zähler
        elseif (trim($value) == "") {
            $ret++;
        }
    }

    return $ret;
}


function checkforempty($valuearray)
{
    $check = array();

    // Extrahiert die Werte der angegebenen Request-Variablen
    foreach ($valuearray as $value) {
        // Füge den Wert der jeweiligen Request-Variable in das Array hinzu
        $check[] = $_REQUEST[$value];
    }

    // Überprüft, ob es leere Variablen gibt
    if (countempty($check) > 0) {
        return false;
    }

    return true;
}


// -- CAPTCHA -- //
if(file_exists('classes/Captcha.php')) { 
    systeminc('classes/Captcha'); 
} else { 
    systeminc('../system/classes/Captcha'); 
}

// -- USER INFORMATION -- //
// Einbinden der Benutzerinformations-Funktionen
if (file_exists('func/user.php')) {
    systeminc('func/user');
} else {
    systeminc('../system/func/user');
}

// -- ACCESS INFORMATION -- //
// Einbinden der Zugriffssteuerungs-Funktionen
if (file_exists('classes/AccessControl.php')) {
    systeminc('classes/AccessControl');
} else {
    systeminc('../system/classes/AccessControl');
}

if (file_exists('func/check_access.php')) {
    systeminc('func/check_access');
} else {
    systeminc('../system/func/check_access');
}

// -- Page INFORMATION -- //
// Einbinden der Seiten-Funktionen
if (file_exists('func/page.php')) {
    systeminc('func/page');
} else {
    systeminc('../system/func/page');
}

// -- Tags -- //
// Einbinden der Tags-Funktionen
if (file_exists('func/tags.php')) {
    systeminc('func/tags');
} else {
    systeminc('../system/func/tags');
}

if (file_exists('classes/NavigationUpdater.php')) {
    systeminc('classes/NavigationUpdater');
} else {
    systeminc('../system/classes/NavigationUpdater');
}

// -- INDEX CONTENT -- //
// Einbinden des Inhalts für die Startseite
if (file_exists('content.php')) {
    systeminc('content');
} else {
    systeminc('../system/content');
}

// Für Login unf Rollen
if (file_exists('classes/LoginSecurity.php')) {
    systeminc('classes/LoginSecurity');
} else {
    systeminc('../system/classes/LoginSecurity');
}

if (file_exists('classes/RoleManager.php')) {
    systeminc('classes/RoleManager');
} else {
    systeminc('../system/classes/RoleManager');
}

if (file_exists('classes/PluginSettings.php')) {
    systeminc('classes/PluginSettings');
} else {
    systeminc('../system/classes/PluginSettings');
}

if (file_exists('classes/AdminLogger.php')) {
    systeminc('classes/AdminLogger');
} else {
    systeminc('../system/classes/AdminLogger');
}

if (file_exists('classes/PluginUninstaller.php')) {
    systeminc('classes/PluginUninstaller');
} else {
    systeminc('../system/classes/PluginUninstaller');
}

if (file_exists('classes/ThemeUninstaller.php')) {
    systeminc('classes/ThemeUninstaller');
} else {
    systeminc('../system/classes/ThemeUninstaller');
}

if (file_exists('classes/LanguageService.php')) {
    systeminc('classes/LanguageService');
} else {
    systeminc('../system/classes/LanguageService');
}

if (file_exists('classes/LanguageManager.php')) {
    systeminc('classes/LanguageManager');
} else {
    systeminc('../system/classes/LanguageManager');
}

if (file_exists('classes/DatabaseMigrationHelper.php')) {
    systeminc('classes/DatabaseMigrationHelper');
} else {
    systeminc('../system/classes/DatabaseMigrationHelper');
}



function getCurrentLanguage(): string
{
    global $languageService;
    static $lang = null;

    if ($lang === null) {
        $lang = $languageService->detectLanguage();
    }

    return $lang;
}

// Funktion zur Bereinigung des Textes vor dem Speichern in der Datenbank
function cleanTextForStorage($text) {
    // Entferne alle Carriage-Return-Zeichen (\r)
    $text = str_replace("\r", "", $text);

    // Optional: Alle Zeilenumbrüche durch <br> ersetzen, wenn du HTML-Ausgabe möchtest
    $text = str_replace("\n", "<br>", $text);

    return $text;
}

// Funktion zur Bereinigung des Textes für die Anzeige
function cleanTextForDisplay($text) {
    // Falls du HTML-Zeilenumbrüche im Text hast, wandle sie in echte Zeilenumbrüche um
    $text = str_replace("<br>", "\n", $text);

    // Umwandlung von \n in HTML <br> für korrekte Darstellung im Browser
    $text = nl2br($text);

    return $text;
}

function getinput($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

// -- SITE VARIABLE -- //
// Setzt die Site-Variable aus der URL-Abfrage
if (isset($_GET['site'])) {
    $site = $_GET['site'];
} else {
    $site = '';
}



// Setzt Standardwerte für HTTP_REFERER und REQUEST_URI
if (!isset($_SERVER['HTTP_REFERER'])) {
    $_SERVER['HTTP_REFERER'] = "";
}

if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
    if (isset($_SERVER['QUERY_STRING'])) {
        $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
    }
}


// -- BANNED IPs -- //
// Löscht abgelaufene Einträge in der Tabelle für gesperrte IPs
safe_query("DELETE FROM banned_ips WHERE deltime < '" . time() . "'");


// =======================
// SEO / PAGE TITLE
// =======================
if (stristr($_SERVER['PHP_SELF'], "/admin/") === false) {
    if (file_exists('seo.php')) {
        systeminc('seo');
    } else {
        systeminc('../system/seo');
    }
    define('PAGETITLE', getPageTitle());
} else {
    define('PAGETITLE', $GLOBALS['hp_title']);
}

// =======================
// RSS FEEDS
// =======================
/*if (file_exists('func/feeds.php')) {
    systeminc('func/feeds');
} else {
    systeminc('../system/func/feeds');
}*/

// =======================
// EMAIL
// =======================
if (file_exists('func/email.php')) {
    systeminc('src/func/email');
} else {
    systeminc('../system/func/email');
}

// =======================
// DIRECTORY CLEANUP
// =======================
function recursiveRemoveDirectory($directory)
{
    foreach (glob("{$directory}/*") as $file) {
        is_dir($file) ? recursiveRemoveDirectory($file) : unlink($file);
    }
    @rmdir($directory);
}

// =======================
// URL / PROTOKOLL HELPER
// =======================
function getCurrentUrl() {
    return ((empty($_SERVER['HTTPS'])) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function httpprotokollsetzen($string) {
    if (stristr($string, 'https://') === false) {
        return "http://$string";
    } else {
        return "https://$string";
    }
}

function httpprotokoll($string) {
    if (strpos($string, 'https://') === 0) {
        return 'https://';
    } elseif (strpos($string, 'http://') === 0) {
        return 'http://';
    } else {
        return 'https://'; // Fallback
    }
}



// =======================
// TABLE EXISTENCE CHECK
// =======================
function tableExists($table) {
    $result = safe_query("SHOW TABLES LIKE '" . $table . "'");
    return $result && mysqli_num_rows($result) > 0;
}

function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function get_all_settings() {
    global $_database;

    $result = safe_query("SELECT * FROM settings LIMIT 1");
    if ($result && mysqli_num_rows($result)) {
        return mysqli_fetch_assoc($result);
    }
    return [];
}


// Konstante zur Steuerung von SEO-URLs
//define('USE_SEO_URLS', true);
// SEO-Einstellung laden
$result = $_database->query("SELECT use_seo_urls FROM settings LIMIT 1");
$seoEnabled = 0;
if ($result) {
    $row = $result->fetch_assoc();
    $seoEnabled = (int)$row['use_seo_urls'];
}

// Konstante setzen
define('USE_SEO_URLS', $seoEnabled === 1);
/**
 * Konvertiert interne Links zu SEO-URLs (z. B. /de/forum/thread/3/page/2#post17).
 * Unterstützt Eingabe als URL-String oder Array mit Parametern.
 *
 * @param string|array $input URL (z. B. "index.php?site=...") oder Array (z. B. ['site'=>'forum', 'id'=>3])
 * @return string SEO-URL oder Original-URL, wenn deaktiviert
 */

function convertToSeoUrl($input): string {
    if (!USE_SEO_URLS) {
        return is_array($input) ? 'index.php?' . http_build_query($input) : $input;
    }

    $lang = $_SESSION['language'] ?? 'de';

    // Wenn ein Array übergeben wurde
    if (is_array($input)) {
        $site    = $input['site']    ?? 'start';
        $action  = $input['action']  ?? null;
        $id      = $input['id']      ?? null;
        $page    = $input['page']    ?? null;
        $userID  = $input['userID']  ?? null;
        $anchor  = $input['anchor']  ?? null;

        $url = "/$lang/" . urlencode($site);
        if ($action) $url .= '/' . urlencode($action);
        if ($id)     $url .= '/' . urlencode($id);
        if ($userID) $url .= '/user/' . urlencode($userID);
        if ($page)   $url .= '/page/' . urlencode($page);
        if ($anchor) $url .= '#' . urlencode($anchor);

        return $url;
    }

    // Wenn ein String übergeben wurde
    if (is_string($input) && stripos($input, 'index.php') !== false) {
        $parts = parse_url($input);
        parse_str($parts['query'] ?? '', $params);

        $site    = $params['site']    ?? 'start';
        $action  = $params['action']  ?? null;
        $id      = $params['id']      ?? null;
        $userID  = $params['userID']  ?? null;
        $page    = $params['page']    ?? null;

        $url = "/$lang/" . urlencode($site);
        if ($action) $url .= '/' . urlencode($action);
        if ($id)     $url .= '/' . urlencode($id);
        if ($userID) $url .= '/user/' . urlencode($userID);
        if ($page)   $url .= '/page/' . urlencode($page);

        if (!empty($parts['fragment'])) {
            $url .= '#' . urlencode($parts['fragment']);
        }

        return $url;
    }

    return $input;
}

