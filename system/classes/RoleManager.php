<?php 

namespace webspell;

class RoleManager {

    public static function getUserRoleID(int $userID): ?int {
        global $_database;

        $stmt = $_database->prepare("SELECT roleID FROM user_role_assignments WHERE userID = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $stmt->bind_result($roleID);

        if ($stmt->fetch()) {
            return $roleID;
        }

        return null;
    }

    public static function roleHasPermission(int $roleID, string $permission_key): bool {
        global $_database;

        $stmt = $_database->prepare("SELECT 1 FROM user_role_permissions WHERE roleID = ? AND permission_key = ?");
        $stmt->bind_param("is", $roleID, $permission_key);
        $stmt->execute();
        $stmt->store_result();

        return $stmt->num_rows > 0;
    }
}