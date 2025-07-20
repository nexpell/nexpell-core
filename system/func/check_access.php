<?php

use nexpell\AccessControl;

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