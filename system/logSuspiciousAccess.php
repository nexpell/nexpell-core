<?php
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
