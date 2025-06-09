<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use webspell\LoginSecurity;

global $_database;

// CSRF-Token validieren
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token validieren
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ungültiger CSRF-Token.");
    }

    // Eingabefelder bereinigen
    $current_email = isset($_POST['current_email']) ? filter_var(trim($_POST['current_email']), FILTER_SANITIZE_EMAIL) : '';
    $new_email = isset($_POST['new_email']) ? filter_var(trim($_POST['new_email']), FILTER_SANITIZE_EMAIL) : '';
    $confirm_email = isset($_POST['confirm_email']) ? filter_var(trim($_POST['confirm_email']), FILTER_SANITIZE_EMAIL) : '';
    $password_hash = isset($_POST['password_hash']) ? trim($_POST['password_hash']) : '';
    $password_pepper = isset($_POST['password_pepper']) ? trim($_POST['password_pepper']) : ''; // Sicherstellen, dass Pepper gesetzt ist

    // E-Mail-Validierung
    if (!filter_var($current_email, FILTER_VALIDATE_EMAIL)) {
        die("Ungültige aktuelle E-Mail-Adresse.");
    }
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        die("Ungültige neue E-Mail-Adresse.");
    }
    if ($new_email !== $confirm_email) {
        die("Die neue E-Mail-Adresse und die Bestätigung stimmen nicht überein.");
    }

    // Hier kannst du die Benutzer-ID aus der Session holen
    if (!isset($_SESSION['userID'])) {
        die("Benutzer ist nicht eingeloggt.");
    }

    // Datenbankverbindung
    $userID = $_SESSION['userID'];

    // Überprüfen, ob die aktuelle E-Mail-Adresse korrekt ist
    $stmt = $_database->prepare("SELECT email, password_hash, password_pepper FROM users WHERE userID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        die("Benutzer nicht gefunden.");
    }

    $stmt->bind_result($db_current_email, $db_password_hash, $db_password_pepper);
    $stmt->fetch();

    // Überprüfen, ob die aktuelle E-Mail-Adresse korrekt ist
    if ($db_current_email !== $current_email) {
        die("Die angegebene aktuelle E-Mail-Adresse ist falsch.");
    }

    // Entschlüsselten Pepper laden
    $decrypted_pepper = LoginSecurity::decryptPepper($db_password_pepper);

    if (is_null($decrypted_pepper)) {
        die("Pepper konnte nicht entschlüsselt werden.");
    }

    // Passwort prüfen
    if (!LoginSecurity::verifyPassword($password_hash, $current_email, $decrypted_pepper, $db_password_hash)) {
        die("Das Passwort ist falsch.");
    }

    // Neue E-Mail und Pepper verwenden
    $new_pepper = LoginSecurity::generateReadablePassword(32); // Einen neuen zufälligen Pepper generieren
    $encrypted_pepper = LoginSecurity::encryptPepper($new_pepper); // Den neuen Pepper verschlüsseln

    // Neues Passwort-Hash mit der neuen E-Mail und dem neuen Pepper erstellen
    $new_password_hash = LoginSecurity::createPasswordHash($password_hash, $new_email, $new_pepper);

    // E-Mail und Passwort-Hash + Pepper aktualisieren
    $stmt = $_database->prepare("UPDATE users SET email = ?, password_hash = ?, password_pepper = ? WHERE userID = ?");
    $stmt->bind_param("sssi", $new_email, $new_password_hash, $encrypted_pepper, $userID);

    if ($stmt->execute()) {
        echo "E-Mail-Adresse und Passwort erfolgreich geändert.";
        // Optional: Bestätigungsmail an den Benutzer senden
        // mail($new_email, "E-Mail geändert", "Ihre E-Mail-Adresse wurde erfolgreich geändert.");
    } else {
        echo "Fehler beim Ändern der E-Mail-Adresse oder des Passworts.";
    }
} else {
    // CSRF-Token generieren, wenn es noch nicht existiert
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// CSRF-Token an das Template übergeben
$data_array = [
    'csrf_token' => $_SESSION['csrf_token'], // CSRF-Token im Template verfügbar machen
];

echo $tpl->loadTemplate("update_email", "content", $data_array, 'theme');
?>
