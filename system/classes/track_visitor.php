<?php
// --- Besucher Tracking ---
// Datenbankverbindung ($ _database) muss vorher vorhanden sein!
/*
if (!isset($_database)) {
    die('Database connection not established.');
}

// IP-Adresse holen
$ip = $_SERVER['REMOTE_ADDR'];

// Aktuelle Seite holen
$page = $_SERVER['REQUEST_URI'];

// Land holen (optional, wenn du IP to Country hast)
$country_code = null;

// Beispiel mit geoip_country_code_by_name() (nur wenn GeoIP installiert ist):
if (function_exists('geoip_country_code_by_name')) {
    $country_code = @geoip_country_code_by_name($ip);
}

// Oder einfach leer lassen wenn keine GeoIP-Daten verfügbar sind
if (!$country_code) {
    $country_code = '??'; // Unbekannt
}

// IP-Duplikate innerhalb 5 Minuten vermeiden (optional)
$five_minutes_ago = date('Y-m-d H:i:s', time() - 300);

// Prüfen ob dieser Besucher in den letzten 5 Minuten schon eingetragen wurde
$check = $_database->prepare("
    SELECT COUNT(*) AS count
    FROM visitor_statistics
    WHERE ip_address = ? AND created_at > ?
");
$check->bind_param('ss', $ip, $five_minutes_ago);
$check->execute();
$check_result = $check->get_result();
$count = (int) $check_result->fetch_assoc()['count'];

if ($count == 0) {
    // Neuen Besuch eintragen
    $insert = $_database->prepare("
        INSERT INTO visitor_statistics (ip_address, page, country_code)
        VALUES (?, ?, ?)
    ");
    $insert->bind_param('sss', $ip, $page, $country_code);
    $insert->execute();
}


*/
// --- Besucher Tracking ---
// Datenbankverbindung ($_database) muss vorher vorhanden sein!

if (!isset($_database)) {
    die('Database connection not established.');
}

// IP-Adresse holen
$ip = $_SERVER['REMOTE_ADDR'];

// Aktuelle Seite holen
$page = $_SERVER['REQUEST_URI'];

// Land ermitteln über API
$country_code = 'unknown'; // Fallback

if (filter_var($ip, FILTER_VALIDATE_IP) && !in_array($ip, ['127.0.0.1', '::1'])) {
    $api_url = 'https://ipwho.is/' . $ip;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $api_response = curl_exec($ch);
    curl_close($ch);

    if ($api_response) {
        $data = json_decode($api_response, true);
        if (isset($data['success']) && $data['success'] && !empty($data['country_code'])) {
            $country_code = strtolower($data['country_code']); // Wichtig: Kleinbuchstaben für Dateinamen!
        }
    }
}

// Jetzt Flagge anzeigen
$flag_file = 'flags/' . $country_code . '.png';

if (!file_exists($flag_file)) {
    $flag_file = 'flags/unknown.png'; // Optional: Fallback-Flagge
}

// Ausgabe:
#echo '<img src="' . $flag_file . '" alt="' . strtoupper($country_code) . '" style="width:24px;height:16px;"> ' . strtoupper($country_code);



// IP-Duplikate innerhalb 5 Minuten vermeiden (optional)
$five_minutes_ago = date('Y-m-d H:i:s', time() - 300);

// Prüfen ob dieser Besucher in den letzten 5 Minuten schon eingetragen wurde
$check = $_database->prepare("
    SELECT COUNT(*) AS count
    FROM visitor_statistics
    WHERE ip_address = ? AND created_at > ?
");
$check->bind_param('ss', $ip, $five_minutes_ago);
$check->execute();
$check_result = $check->get_result();
$count = (int) $check_result->fetch_assoc()['count'];

if ($count == 0) {
    // Neuen Besuch eintragen
    $insert = $_database->prepare("
        INSERT INTO visitor_statistics (ip_address, page, country_code)
        VALUES (?, ?, ?)
    ");
    $insert->bind_param('sss', $ip, $page, $country_code);
    $insert->execute();
}
?>

