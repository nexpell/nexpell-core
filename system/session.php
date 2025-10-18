<?php
namespace nexpell;

// Diese Datei sehr früh einbinden, vor JEDEM Output!

if (session_status() === \PHP_SESSION_NONE) {
    // Eindeutiger Name (vermeidet Kollisionen mit anderen Apps auf dem Hoster)
    \session_name('NXSESSID');

    $isHttps  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    // Wenn du www UND non-www benutzt, kommentiere die Domain-Zeile ein und setze deine Domain:
    // $cookieDomain = '.deinedomain.tld';

    session_set_cookie_params([
        'lifetime' => 0,          // Session-Cookie (endet beim Browser-Schließen)
        'path'     => '/',
        // 'domain'   => $cookieDomain ?? null, // <- nur setzen, wenn nötig (www/non-www)
        'secure'   => $isHttps,    // true, wenn du HTTPS durchgehend nutzt (empfohlen)
        'httponly' => true,
        'samesite' => 'Lax',       // WICHTIG: nicht 'Strict'; 'None' nur bei Cross-Site + HTTPS
    ]);

    // Härtung
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', '7200'); // z.B. 2 Stunden Server-Lebensdauer

    session_start();
}



