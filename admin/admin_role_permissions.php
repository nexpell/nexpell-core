<?php

// Überprüfen, ob die Session bereits gestartet wurde
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use nexpell\RoleManager;
use nexpell\AccessControl;
// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_role_permissions');

global $_database;

// Alle Rollen abrufen
$roles = $_database->query("SELECT roleID, role_name FROM user_roles ORDER BY role_name ASC")->fetch_all(MYSQLI_ASSOC);

// Verfügbare Rechte definieren
$available_permissions = [
    'ckeditor_full' => 'CKEditor Vollzugriff',
    'manage_users' => 'Benutzer verwalten',
    'edit_articles' => 'Artikel bearbeiten',
    'view_dashboard_only' => 'Nur Dashboard sehen'
];

// Rolle wählen
$selected_roleID = isset($_GET['roleID']) ? (int)$_GET['roleID'] : null;

// Rechte speichern
if (isset($_POST['save']) && $selected_roleID) {
    // Alte Rechte entfernen
    $stmt_delete = $_database->prepare("DELETE FROM user_role_permissions WHERE roleID = ?");
    $stmt_delete->bind_param('i', $selected_roleID);
    $stmt_delete->execute();

    // Neue Rechte speichern
    if (!empty($_POST['permissions'])) {
        $stmt_insert = $_database->prepare("INSERT INTO user_role_permissions (roleID, permission_key) VALUES (?, ?)");
        foreach ($_POST['permissions'] as $perm) {
            $stmt_insert->bind_param('is', $selected_roleID, $perm);
            $stmt_insert->execute();
        }
    }

    echo '<div class="alert alert-success mt-3">Rechte gespeichert! Weiterleitung...</div>';
    echo '<meta http-equiv="refresh" content="1; URL=admincenter.php?site=admin_role_permissions&roleID=' . $selected_roleID . '">';
    exit();
}

// Aktuelle Rechte laden
$current_permissions = [];
if ($selected_roleID) {
    $stmt = $_database->prepare("SELECT permission_key FROM user_role_permissions WHERE roleID = ?");
    $stmt->bind_param('i', $selected_roleID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $current_permissions[] = $row['permission_key'];
    }
}
?>

<div class="container mt-4">
    <h2>Rollenrechte verwalten</h2>

    <form method="GET" action="admincenter.php" class="mb-4">
        <input type="hidden" name="site" value="admin_role_permissions">
        <div class="mb-3">
            <label for="roleID" class="form-label">Rolle wählen:</label>
            <select name="roleID" id="roleID" onchange="this.form.submit()" class="form-select">
                <option value="">-- wählen --</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= (int)$role['roleID'] ?>" <?= $selected_roleID == $role['roleID'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($role['role_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($selected_roleID): ?>
        <form method="POST">
            <h4 class="mb-3">Rechte für Rolle:
                <?= htmlspecialchars($roles[array_search($selected_roleID, array_column($roles, 'roleID'))]['role_name']) ?>
            </h4>

            <?php foreach ($available_permissions as $key => $label): ?>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="permissions[]" id="<?= $key ?>" value="<?= $key ?>"
                        <?= in_array($key, $current_permissions) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="<?= $key ?>">
                        <?= htmlspecialchars($label) ?>
                    </label>
                </div>
            <?php endforeach; ?>

            <button type="submit" name="save" class="btn btn-primary mt-3">Speichern</button>
        </form>
    <?php endif; ?>
</div>
