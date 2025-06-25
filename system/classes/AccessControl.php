<?php

namespace webspell;

class AccessControl
{
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
