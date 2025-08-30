<?php

namespace nexpell;

// Nur konfigurieren und starten, wenn noch keine Session läuft
if (session_status() === PHP_SESSION_NONE) {
    // Eigener Session-Name
    session_name('nexpell_session');

    // Cookie- und Sicherheits-Einstellungen
    session_set_cookie_params([
        'lifetime' => 3600,       // Cookie gültig für 1 Stunde
        'path'     => '/',        // Gilt für gesamte Domain
        'secure'   => true,       // Nur über HTTPS senden
        'httponly' => true,       // Kein Zugriff via JavaScript
        'samesite' => 'Strict'    // Schutz vor CSRF (oder 'Lax' falls externe Logins nötig sind)
    ]);

    // Sicherheits-Settings
    ini_set('session.use_strict_mode', 1);   // Verhindert Session-Fixation
    ini_set('session.use_only_cookies', 1);  // Session-ID nur per Cookie
    ini_set('session.gc_maxlifetime', 3600); // Serverseitige Session-Lebensdauer

    // Session starten
    session_start();
}


