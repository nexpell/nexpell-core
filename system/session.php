<?php
namespace nexpell;

// Nur konfigurieren und starten, wenn noch keine Session läuft
if (session_status() === PHP_SESSION_NONE) {
    // Eigener Session-Name
    session_name('nexpell_session');

    // Cookie-Einstellungen
    ini_set('session.cookie_lifetime', 3600);  // Cookie gültig 1 Stunde
    ini_set('session.cookie_path', '/');       // Cookie gilt für gesamte Domain
    ini_set('session.cookie_secure', 1);       // Nur über HTTPS senden (stelle sicher, dass HTTPS aktiv ist!)
    ini_set('session.cookie_httponly', 1);     // Kein Zugriff via JS auf das Cookie

    // Sicherheits-Settings
    ini_set('session.use_strict_mode', 1);     // Verhindert Session-Hijacking mit ungültigen IDs
    ini_set('session.use_only_cookies', 1);    // Session-ID nur per Cookie, nicht in URL

    // Session starten
    session_start();
}
?>

