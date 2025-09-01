<?php
if (!isset($_database)) {
    die('Database connection not established.');
}

// IP-Adresse
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// IP-Hash (für IPv4 und IPv6)
$ip_hash = hash('sha256', $ip);

// Aktuelle Seite
$page = $_SERVER['REQUEST_URI'] ?? 'unknown';

// Referer
$referer = $_SERVER['HTTP_REFERER'] ?? 'direct';

// User-Agent
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Device, OS, Browser
$device_type = 'Unknown';
$os = 'Unknown';
$browser = 'Unknown';

if (function_exists('get_browser')) {
    $browser_info = get_browser(null, true);
    $device_type = $browser_info['device_type'] ?? 'Unknown';
    $os = $browser_info['platform'] ?? 'Unknown';
    $browser = $browser_info['browser'] ?? 'Unknown';
} else {
    if (stripos($user_agent, 'mobile') !== false) $device_type = 'Mobile';
    else $device_type = 'Desktop';

    if (stripos($user_agent, 'windows') !== false) $os = 'Windows';
    elseif (stripos($user_agent, 'mac') !== false) $os = 'Mac';
    elseif (stripos($user_agent, 'linux') !== false) $os = 'Linux';

    if (stripos($user_agent, 'firefox') !== false) $browser = 'Firefox';
    elseif (stripos($user_agent, 'chrome') !== false) $browser = 'Chrome';
    elseif (stripos($user_agent, 'safari') !== false && stripos($user_agent, 'chrome') === false) $browser = 'Safari';
}

// Land über API
$country_code = 'unknown';
if (filter_var($ip, FILTER_VALIDATE_IP) && !in_array($ip, ['127.0.0.1', '::1'])) {
    $api_url = 'https://ipwho.is/' . $ip;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $data = curl_exec($ch);
    curl_close($ch);

    if ($data) {
        $json = json_decode($data, true);
        if (!empty($json['country_code'])) $country_code = strtolower($json['country_code']);
    }
}

// User-ID aus Session holen (falls angemeldet)
$user_id = $_SESSION['userID'] ?? null;

// 5 Minuten prüfen, um doppelte Einträge zu vermeiden
$five_minutes_ago = date('Y-m-d H:i:s', time() - 300);

$check = $_database->prepare("
    SELECT COUNT(*) AS count
    FROM visitor_statistics
    WHERE ip_hash = ? AND created_at > ?
");
$check->bind_param('ss', $ip_hash, $five_minutes_ago);
$check->execute();
$count = (int)$check->get_result()->fetch_assoc()['count'];

if ($count === 0) {
    // Neuer Eintrag
    $insert = $_database->prepare("
        INSERT INTO visitor_statistics 
        (user_id, ip_address, pageviews, page, country_code, device_type, os, browser, ip_hash, referer, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $pageviews = 1;
    $insert->bind_param(
        'iisssssssss',
        $user_id,
        $ip,
        $pageviews,
        $page,
        $country_code,
        $device_type,
        $os,
        $browser,
        $ip_hash,
        $referer,
        $user_agent
    );
    $insert->execute();
} else {
    // Optional: pageviews erhöhen
    $update = $_database->prepare("
        UPDATE visitor_statistics 
        SET user_id = ?, pageviews = pageviews + 1, page = ?, country_code = ?, device_type = ?, os = ?, browser = ?, referer = ?, user_agent = ?
        WHERE ip_hash = ? AND created_at > ?
    ");
    $update->bind_param(
        'isssssssss',
        $user_id,
        $page,
        $country_code,
        $device_type,
        $os,
        $browser,
        $referer,
        $user_agent,
        $ip_hash,
        $five_minutes_ago
    );
    $update->execute();
}
?>
