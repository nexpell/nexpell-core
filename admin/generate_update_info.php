<?php

// Überprüfen, ob die Session bereits gestartet wurde
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Aktuelle CMS-Version
$version = '2.1.8';

// Optional: Changelog aus Datei lesen
$changelog_file = __DIR__ . '/changelog.txt';
$changelog = file_exists($changelog_file) ? file_get_contents($changelog_file) : 'Keine Änderungen dokumentiert.';

// Ziel-JSON-Datei
$target_file = __DIR__ . '/update_info.json';

// URL zum ZIP-Archiv
$zip_url = "https://update.webspell-rm.de/updates/webspell-$version.zip";

// JSON-Daten vorbereiten
$data = [
    'version' => $version,
    'changelog' => trim($changelog),
    'zip_url' => $zip_url,
];

// JSON schreiben
if (file_put_contents($target_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    echo "update_info.json erfolgreich erstellt.";
} else {
    echo "Fehler beim Schreiben von update_info.json.";
}
