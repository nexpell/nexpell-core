<?php

namespace nexpell;

class AccessControl
{

    public static function hasAdminAccess($modulname)
    {
        global $userID;

        if (!$userID) {
            return false;
        }

        $query = "
            SELECT COUNT(*) AS access_count
            FROM `user_role_admin_navi_rights` ar
            JOIN `user_role_assignments` ur ON ar.`roleID` = ur.`roleID`
            WHERE ur.`userID` = '" . (int)$userID . "'
            AND ar.`modulname` = '" . escape($modulname) . "'
        ";

        $result = safe_query($query);
        $row = mysqli_fetch_assoc($result);
        return $row['access_count'] > 0;
    }

    public static function checkAdminAccess($modulname, bool $apiMode = false)
    {
        global $userID, $languageService;

        $logFile = __DIR__ . '/../../admin/logs/access_control.log';

        if (!$userID || !self::hasAdminAccess($modulname)) {
            http_response_code(403);

            if ($apiMode) {
                echo json_encode(['error' => 'Zugriff verweigert']);
                exit;
            }

            $modulnameDisplay = htmlspecialchars($modulname);

            // Rolle holen
            $roleName = '';
            if ($userID) {
                $query = "
                    SELECT r.`role_name`
                    FROM `user_role_admin_navi_rights` ar
                    JOIN `user_role_assignments` ur ON ar.`roleID` = ur.`roleID`
                    JOIN `user_roles` r ON ur.`roleID` = r.`roleID`
                    WHERE ur.`userID` = '" . (int)$userID . "'
                      AND ar.`modulname` = '" . self::escape($modulname) . "'
                    LIMIT 1
                ";
                $result = safe_query($query);
                if ($row = mysqli_fetch_assoc($result)) {
                    $roleName = htmlspecialchars($row['role_name']);
                }
            }

            // Linkname aus DB holen
            $linkName = 'Unbekannter Link';
            $linkQuery = "
                SELECT `name`
                FROM `navigation_dashboard_links`
                WHERE `modulname` = '" . self::escape($modulname) . "'
                LIMIT 1
            ";
            $linkResult = safe_query($linkQuery);
            if ($linkRow = mysqli_fetch_assoc($linkResult)) {
                $linkName = $linkRow['name'];
            }

            // Sprache ermitteln (z.B. 'de', 'en', 'it')
            $lang = $languageService->detectLanguage();

            // Nur den Text für die aktuelle Sprache extrahieren
            $translatedName = self::extractTextByLanguage($linkName, $lang);

            // Fehlermeldung bauen
            $errorMessage = "<b>Zugriff verweigert:</b> Keine Berechtigung für das Modul '<i>$modulnameDisplay</i>'.<br>";
            $errorMessage .= "<b>Linkname:</b> " . htmlspecialchars($translatedName) . "<br>";
            if ($roleName !== '') {
                $errorMessage .= "<b>Ihre Rolle:</b> $roleName<br>";
            }

            $logEntry = sprintf(
                "[%s] Zugriff verweigert: Modul='%s', UserID=%s, Rolle='%s', IP=%s\n",
                date('Y-m-d H:i:s'),
                $modulname,
                $userID ?? 'nicht angemeldet',
                $roleName ?: 'Keine Rolle',
                $_SERVER['REMOTE_ADDR'] ?? 'Unbekannt'
            );

            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
            error_log($logEntry);

            echo "<div class='alert alert-danger' role='alert'>$errorMessage</div>";
            exit;
        }
    }

    // Hilfsfunktion zum Extrahieren des Texts der aktuellen Sprache
    private static function extractTextByLanguage(string $multiLangString, string $lang): string
    {
        preg_match_all('/\[\[lang:(\w{2})\]\](.*?)(?=(\[\[lang:\w{2}\]\])|$)/s', $multiLangString, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if ($match[1] === $lang) {
                return trim($match[2]);
            }
        }

        // Fallback: Wenn Sprache nicht gefunden, gesamten String zurückgeben
        return $multiLangString;
    }

    // Escape-Funktion, falls noch nicht definiert
    private static function escape(string $str): string
    {
        global $dbConnection; // Falls du eine DB-Verbindung hast

        if (function_exists('mysqli_real_escape_string') && isset($dbConnection)) {
            return mysqli_real_escape_string($dbConnection, $str);
        }

        return addslashes($str);
    }


/*    public static function checkAdminAccess($modulname)
    {
        global $userID, $languageService;

        // Wenn kein Login vorhanden → Weiterleitung wie bisher
        if (!$userID) {
            header('Location: login.php');
            exit;
        }

        // Berechtigungsprüfung
        if (!self::hasAdminAccess($modulname)) {
            // HTTP-Status 403 senden
            http_response_code(403);

            $modulnameDisplay = htmlspecialchars($modulname);
            $errorMessage = "<b>Zugriff verweigert:</b> Keine Berechtigung für das Modul '<i>$modulnameDisplay</i>'.<br>";

            // Protokoll ins PHP-Errorlog
            error_log("AccessControl Fehler: Modul '$modulnameDisplay' nicht erlaubt für userID $userID | IP: " . $_SERVER['REMOTE_ADDR']);

            // Versuche, den Linknamen zu holen
            $linkQuery = "
                SELECT `name`
                FROM `navigation_dashboard_links`
                WHERE `modulname` = '" . escape($modulname) . "'
            ";
            $linkResult = safe_query($linkQuery);
            $linkRow = mysqli_fetch_assoc($linkResult);
            $linkName = $linkRow ? htmlspecialchars($linkRow['name']) : 'Unbekannter Link';

            try {
                $languageService->detectLanguages($linkName);
            } catch (\Error $e) {
                error_log("Fehler in detectLanguages(): " . $e->getMessage());
                echo "<div class='alert alert-danger'>Ein interner Fehler ist aufgetreten. Bitte wende dich an den Administrator.</div>";
                exit;
            }

            $translatedName = $languageService->getTextByLanguage($linkName);
            $errorMessage .= "<b>Linkname:</b> $translatedName<br>";

            echo "<div class='alert alert-danger' role='alert'>$errorMessage</div>";
            exit;
        }
    }
*/


/*
    public static function checkAdminAccess($modulname)
    {
        global $userID, $languageService;

        // Überprüfen, ob der Benutzer angemeldet ist
        if (!$userID) {
            header('Location: login.php'); // Umleitung zur Login-Seite
            exit;
        }

        // Prüfen, ob der Benutzer Zugriff auf das Modul hat und den Rollennamen holen
        $query = "
            SELECT r.`role_name`, ar.`modulname`, COUNT(*) AS access_count
            FROM `user_role_admin_navi_rights` ar
            JOIN `user_role_assignments` ur ON ar.`roleID` = ur.`roleID`
            JOIN `user_roles` r ON ur.`roleID` = r.`roleID`
            WHERE ur.`userID` = '" . (int)$userID . "'
            AND ar.`modulname` = '" . $modulname . "'
            GROUP BY r.`role_name`, ar.`modulname`
        ";

        $result = safe_query($query);
        $row = mysqli_fetch_assoc($result);

        // Wenn keine Zeilen zurückgegeben werden oder keine Berechtigung für das Modul besteht
        if ($row === null || $row['access_count'] == 0) {
            $modulnameDisplay = $row ? htmlspecialchars($row['modulname']) : 'Unbekanntes Modul';
            $errorMessage = "<b>Zugriff verweigert:</b> Keine Berechtigung für das Modul '<i>$modulnameDisplay</i>'.<br>";

            // Protokolliere zur Fehlerdiagnose
            error_log("AccessControl Fehler: Modul '$modulnameDisplay' nicht erlaubt für userID $userID");

            // Versuche, den Linknamen aus der Navigations-Tabelle zu laden
            $linkQuery = "
                SELECT `name`
                FROM `navigation_dashboard_links`
                WHERE `modulname` = '" . $modulname . "'
            ";
            $linkResult = safe_query($linkQuery);
            $linkRow = mysqli_fetch_assoc($linkResult);
            $linkName = $linkRow ? htmlspecialchars($linkRow['name']) : 'Unbekannter Link';

            // Verwende den globalen $languageService statt multiLanguage
            try {
                $languageService->detectLanguages($linkName);
            } catch (\Error $e) {
                error_log("Methodenfehler in detectLanguages(): " . $e->getMessage());
                echo "<div class='alert alert-danger'>Ein interner Fehler ist aufgetreten. Bitte wende dich an den Administrator.</div>";
                exit;
            }

            $translatedName = $languageService->getTextByLanguage($linkName);

            $errorMessage .= "<b>Linkname:</b> $translatedName<br>";

            if (!empty($row['role_name'])) {
                $errorMessage .= "<b>Ihre Rolle:</b> " . htmlspecialchars($row['role_name']);
            }

            echo "<div class='alert alert-danger' role='alert'>$errorMessage</div>";
            exit;
        }


    }
*/
    public static function hasAnyRole(array $roleNames): bool
{
    // Kein Zugriff, wenn keine Rollen erlaubt sind
    if (empty($roleNames)) return false;

    // Gast-Zugriff prüfen, wenn kein User eingeloggt ist
    if (!isset($_SESSION['userID']) || $_SESSION['userID'] <= 0) {
        return in_array('gast', array_map('strtolower', $roleNames));
    }

    $userID = (int)$_SESSION['userID'];
    global $_database;

    // Erzeuge Platzhalter (?, ?, ...)
    $placeholders = implode(',', array_fill(0, count($roleNames), '?'));

    // Datentypen: i für userID, danach s für jeden Rollennamen
    $types = 'i' . str_repeat('s', count($roleNames));
    $params = array_merge([$types, $userID], $roleNames);

    // SQL vorbereiten
    $stmt = $_database->prepare("
        SELECT 1
        FROM user_role_assignments a
        JOIN user_roles r ON a.roleID = r.roleID
        WHERE a.userID = ? AND r.role_name IN ($placeholders)
        LIMIT 1
    ");

    if ($stmt === false) {
        error_log("Fehler beim Prepare in hasAnyRole(): " . $_database->error);
        return false;
    }

    // Bindung und Ausführung
    $stmt->bind_param(...self::refValues($params));
    $stmt->execute();
    $res = $stmt->get_result();

    return $res->num_rows > 0;
}


    private static function refValues(array $arr)
    {
        // Ab PHP 8 sind keine Referenzen mehr nötig
        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            return $arr;
        }

        // Für PHP < 8: Referenzen für bind_param
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
}
