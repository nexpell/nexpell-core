<?php
use nexpell\LoginCookie;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $_database;

// Cache verhindern
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// --- 1. Onlinezeit speichern ---
if (!empty($_SESSION['userID'])) {
    $userID = $_SESSION['userID'];

    $sql = "SELECT login_time, total_online_seconds, is_online FROM users WHERE userID = ?";
    $stmt = $_database->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && $user['is_online'] && !empty($user['login_time'])) {
        $current_session_seconds = time() - strtotime($user['login_time']);
        $new_total = $user['total_online_seconds'] + $current_session_seconds;

        // Onlinezeit in DB speichern
        $sql = "UPDATE users 
                SET total_online_seconds = ?, login_time = NULL, is_online = 0 
                WHERE userID = ?";
        $stmt = $_database->prepare($sql);
        $stmt->bind_param("ii", $new_total, $userID);
        $stmt->execute();
        $stmt->close();
    }
}

// --- 2. Session + Cookies löschen ---
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();

if (class_exists(LoginCookie::class)) {
    LoginCookie::clear('ws_auth'); // Name anpassen
}

setcookie('ws_session', '', time() - 3600, '/');
setcookie('ws_cookie', '', time() - 3600, '/');

// --- 3. Weiterleiten ---
header("Location: /");
exit;

// --- Hilfsfunktion (optional für Anzeige vor Logout) ---
function formatTime($seconds) {
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    return $h . " Stunde" . ($h !== 1 ? "n" : "") . ", " .
           $m . " Minute" . ($m !== 1 ? "n" : "");
}
