<?php
use nexpell\LoginCookie;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $_database;

if (!empty($_SESSION['userID'])) {
    $userID = (int)$_SESSION['userID'];

    // Aktuelle Userdaten laden
    $sql = "SELECT login_time, last_activity, total_online_seconds, is_online 
            FROM users WHERE userID = ?";
    $stmt = $_database->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && $user['is_online']) {
        // Online-Zeit berechnen
        $login_time = !empty($user['login_time']) ? strtotime($user['login_time']) : time();
        $last_activity = !empty($user['last_activity']) ? strtotime($user['last_activity']) : $login_time;
        
        $session_seconds = max(0, time() - $login_time);
        $activity_seconds = max(0, time() - $last_activity);

        $new_total = $user['total_online_seconds'] + $session_seconds + $activity_seconds;

        // User sofort offline setzen
        $sql = "UPDATE users 
                SET total_online_seconds = ?, 
                    login_time = NULL, 
                    is_online = 0, 
                    last_activity = NULL
                WHERE userID = ?";
        $stmt = $_database->prepare($sql);
        $stmt->bind_param("ii", $new_total, $userID);
        $stmt->execute();
        $stmt->close();
    }
}

// --- Session + Cookies löschen ---
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Optional: Remember-Me Cookie löschen
if (class_exists(LoginCookie::class)) {
    LoginCookie::clear('ws_auth');
}

setcookie('ws_session', '', time() - 3600, '/');
setcookie('ws_cookie', '', time() - 3600, '/');

// --- Verhalten unterscheiden ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Aufruf via sendBeacon → kein Redirect
    http_response_code(204); // No Content
    exit;
} else {
    // Direkter Aufruf (User klickt Logout-Link/GET)
    header("Location: /");
    exit;
}
