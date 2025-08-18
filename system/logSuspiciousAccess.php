<?php
// ==========================
// OUTPUT-PUFFER UND SETTINGS
// ==========================
ob_start(); // Puffer für alle Ausgaben
ini_set('display_errors', 0); // Fehler nicht direkt ausgeben

// ==========================
// FUNKTIONEN
// ==========================

// Sensible Keys dynamisch erkennen und maskieren
function maskSensitiveData(array $data): array {
    foreach ($data as $key => &$value) {
        if (is_array($value)) {
            $value = maskSensitiveData($value); // Rekursiv prüfen
        } elseif (stripos($key, 'pass') !== false) { // "pass" im Namen?
            $value = '[HIDDEN]';
        }
    }
    return $data;
}

// Verdächtigen Zugriff protokollieren
function logSuspiciousAccess(string $reason = '', array $details = []): void {
    $logDir = __DIR__ . '/../admin/logs';
    $logfile = $logDir . '/suspicious_access.log';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    // Daten maskieren
    $maskedGet = maskSensitiveData($_GET);
    $maskedPost = maskSensitiveData($_POST);
    $maskedDetails = maskSensitiveData($details);

    $logEntry  = date('Y-m-d H:i:s') . " - Grund: $reason - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . PHP_EOL;
    $logEntry .= "URL: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . PHP_EOL;
    $logEntry .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . PHP_EOL;
    $logEntry .= "GET: " . json_encode($maskedGet, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    $logEntry .= "POST: " . json_encode($maskedPost, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

    if (!empty($maskedDetails)) {
        $logEntry .= "Details: " . json_encode($maskedDetails, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    $logEntry .= str_repeat('-', 40) . PHP_EOL;

    file_put_contents($logfile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Prüfen auf verdächtige Eingaben
function detectSuspiciousInput(array $input): ?array {
    $pattern = '/(\bAND\b|\bOR\b|;|\'|"|--|\/\*|\*\/|<|>)/i';

    foreach ($input as $key => $value) {
        // Passwortfelder komplett ignorieren
        if (stripos($key, 'pass') !== false) {
            continue;
        }

        if (is_array($value)) {
            $result = detectSuspiciousInput($value);
            if ($result !== null) {
                return $result;
            }
        } elseif (preg_match($pattern, (string)$value)) {
            return ['param' => $key, 'value' => $value];
        }
    }
    return null;
}


// ==========================
// IP-BLOCK SYSTEM
// ==========================

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$logDir = __DIR__ . '/../admin/logs';
$blockfile = $logDir . '/blocked_ips.json';
$logfile = $logDir . '/block_log.txt';

if (!is_dir($logDir)) mkdir($logDir, 0755, true);

// JSON laden, falls vorhanden
$blocked = file_exists($blockfile) ? json_decode(file_get_contents($blockfile), true) : [];

// Abgelaufene Sperren automatisch entfernen
$now = time();
$blocked = array_filter($blocked, function($b) use ($now) {
    return (isset($b['until']) ? $b['until'] : 0) > $now;
});
file_put_contents($blockfile, json_encode(array_values($blocked), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Prüfen, ob IP aktuell blockiert ist
foreach ($blocked as $b) {
    if ($b['ip'] === $ip) {
        $entry = date('Y-m-d H:i:s') . " - Blocked access from IP: $ip, reason: " 
                 . ($b['reason'] ?? 'unknown') 
                 . ", level: " . ($b['level'] ?? 'warning') . PHP_EOL;
        file_put_contents($logfile, $entry, FILE_APPEND);

        http_response_code(403);
        exit; // Kein Text, nur Header
    }
}

// Funktion zum Sperren einer IP
function blockIP($ip, $reason = '', $level = 'warning', $duration = 3600) {
    global $blockfile, $blocked, $logfile;

    $entry = [
        'ip' => $ip,
        'reason' => $reason,
        'level' => $level,
        'date' => date('Y-m-d H:i:s'),
        'until' => time() + $duration
    ];

    $blocked[] = $entry;
    file_put_contents($blockfile, json_encode($blocked, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $logEntry = date('Y-m-d H:i:s') . " - IP blocked: $ip, reason: $reason, level: $level, until: " 
                . date('Y-m-d H:i:s', $entry['until']) . PHP_EOL;
    file_put_contents($logfile, $logEntry, FILE_APPEND);
}


// ==========================
// EINGABEN CHECK (GET & POST)
// ==========================
foreach (['GET' => $_GET, 'POST' => $_POST] as $method => $data) {
    if ($suspicious = detectSuspiciousInput($data)) {
        $level = 'critical';
        $reason = "Verdächtige Eingabe in $method";

        logSuspiciousAccess($reason, $suspicious);
        blockIP($ip, $reason, $level, 3600);

        http_response_code(403);
        exit; // Kein Text
    }
}

// Ausgabe-Puffer freigeben
ob_end_flush();
