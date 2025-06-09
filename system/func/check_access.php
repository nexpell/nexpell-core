<?php

use webspell\AccessControl;

/**
 * Überprüft, ob der Benutzer als Admin Zugriff auf ein bestimmtes Modul hat
 */
/*function checkAdminAccess($currentModule) {
    AccessControl::enforceLogin();

    global $_database, $_SESSION, $_language;

    // Prüfe, ob der aktuelle Benutzer ein Admin ist
    $stmt = $_database->prepare("SELECT is_admin FROM `users` WHERE `userID` = ?");
    $stmt->bind_param("i", $_SESSION['userID']);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data || intval($data['is_admin']) !== 1) {
        echo $_language->module['access_denied'];
        header("Refresh: 3; url=/admin/admincenter.php");
        exit;
    }

    // Prüfe, ob der Admin Zugriff auf das aktuelle Modul hat
    $stmt = $_database->prepare("SELECT `modulname` FROM `user_access_rights` WHERE `adminID` = ?");
    $stmt->bind_param("i", $_SESSION['userID']);
    $stmt->execute();
    $result = $stmt->get_result();

    $allowedModules = [];
    while ($row = $result->fetch_assoc()) {
        $allowedModules[] = $row['modulname'];
    }

    if (!in_array($currentModule, $allowedModules)) {
        echo $_language->module['access_denied'];
        header("Refresh: 3; url=/admin/admincenter.php");
        exit;
    }
}*/

/*function checkAdminAccess($currentModule) {
    AccessControl::enforceLogin();

    global $_database, $_SESSION, $_language;

    $userID = $_SESSION['userID'] ?? 0;

    // Prüfe, ob der Benutzer ein Admin ist
    $stmt = $_database->prepare("SELECT is_admin FROM `users` WHERE `userID` = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data || intval($data['is_admin']) !== 1) {
        echo $_language->module['access_denied'];
        header("Refresh: 3; url=/index.php");
        exit;
    }

    // Prüfe, ob der Admin Zugriff auf das aktuelle Modul hat
    $stmt = $_database->prepare("SELECT `modulname` FROM `admin_access_rights` WHERE `userID` = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    $allowedModules = [];
    while ($row = $result->fetch_assoc()) {
        $allowedModules[] = $row['modulname'];
    }

    if (!in_array($currentModule, $allowedModules)) {
        echo $_language->module['access_denied'];
        header("Refresh: 3; url=/admin/admincenter.php");
        exit;
    }
}*/


/**
 * Prüft, ob ein Benutzer einer Rolle zugewiesen wurde
 */
/*function checkUserRoleAssignment($userID) {
    global $_database;
    $stmt = $_database->prepare("SELECT COUNT(*) AS cnt FROM `user_role_assignments` WHERE `adminID` = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['cnt'] > 0;
}*/

function checkUserRoleAssignment($userID) {
    global $_database;

    $stmt = $_database->prepare("SELECT COUNT(*) AS cnt FROM `user_role_assignments` WHERE `userID` = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();
    return $result['cnt'] > 0;
}


/**
 * Überprüft, ob ein Benutzer Zugriff auf eine bestimmte Kategorie oder einen Link hat
 */
function checkAccessRights($userID, $catID = null, $linkID = null) {
    global $_database;

    if (!$catID && !$linkID) {
        return false;
    }

    // Grundstruktur des SQL-Statements mit Platzhaltern
    $query = "
        SELECT ar.type, ar.accessID
        FROM `user_role_admin_navi_rights` AS ar
        JOIN `user_role_assignments` AS ur ON ar.roleID = ur.roleID
        WHERE ur.userID = ?
        AND (
            (ar.type = 'category' AND ar.accessID = ?) 
            OR 
            (ar.type = 'link' AND ar.accessID = ?)
        )
    ";

    $stmt = $_database->prepare($query);
    $cat = $catID ?? 0;  // Wenn null, dann auf 0 setzen
    $link = $linkID ?? 0;

    $stmt->bind_param('iii', $userID, $cat, $link);
    $stmt->execute();
    $stmt->store_result();

    return $stmt->num_rows > 0;
}


/**
 * Weist einem Benutzer eine Rolle zu und überträgt die zugehörigen Rechte
 */
/*function assignRoleToUser($userID, $roleID) {
    safe_query("INSERT INTO `user_roles` (`userID`, `roleID`) VALUES ('$userID', '$roleID')");

    $rolePermissions = safe_query("SELECT * FROM `user_role_permissions` WHERE `roleID` = '$roleID'");

    while ($permission = mysqli_fetch_array($rolePermissions)) {
        $type = $permission['type'];
        $accessID = $permission['accessID'];
        safe_query("
            INSERT INTO `user_role_admin_navi_rights` (`adminID`, `roleID`, `type`, `accessID`) 
            VALUES ('$userID', '$roleID', '$type', '$accessID')
        ");
    }
}*/

/**
 * Prüft, ob ein Benutzer eine bestimmte Rolle hat
 */
/*function hasRole($userID, $roleID) {
    global $_database;
    $stmt = $_database->prepare("SELECT 1 FROM `user_role_assignments` WHERE `userID` = ? AND `roleID` = ?");
    $stmt->bind_param("ii", $userID, $roleID);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}*/

/**
 * Gibt alle im System verfügbaren Rollen zurück
 */
/*function getAvailableRoles() {
    global $_database;
    $query = "SELECT `roleID` FROM `user_roles`";
    $result = $_database->query($query);

    $roles = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $roles[] = $row['roleID'];
    }
    return $roles;
}*/

/**
 * Prüft, ob ein Benutzer eine beliebige Rolle aus einer Liste besitzt
 */
/*function hasAnyRole($userID, array $allowedRoles): bool {
    $availableRoles = getAvailableRoles();
    $userRoles = getUserRoles($userID);

    foreach ($userRoles as $roleID) {
        if (in_array($roleID, $allowedRoles) && in_array($roleID, $availableRoles)) {
            return true;
        }
    }
    return false;
}*/

/**
 * Gibt alle Rollen eines Benutzers zurück
 */
/*function getUserRoles($userID) {
    global $_database;
    $stmt = $_database->prepare("SELECT `roleID` FROM `user_role_assignments` WHERE `adminID` = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    $roles = [];
    while ($row = $result->fetch_assoc()) {
        $roles[] = $row['roleID'];
    }
    return $roles;
}*/

/**
 * Bannt einen Benutzer mit optionalem Grund und Dauer
 */
/*function ban_user($userID, $reason = '', $duration = null) {
    $userID = intval($userID);
    $ban_until = $duration ? date('Y-m-d H:i:s', strtotime("+$duration")) : null;

    $query = "
        UPDATE `users` 
        SET `banned` = 1, `ban_reason` = '$reason', `ban_until` = " . ($ban_until ? "'$ban_until'" : "NULL") . " 
        WHERE `userID` = $userID
    ";

    return safe_query($query) !== false;
}*/
