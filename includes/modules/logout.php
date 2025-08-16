<?php
use nexpell\LoginCookie;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

global $_database;

if (!empty($_SESSION['userID'])) {
    $userID = (int)$_SESSION['userID'];

    // Aktuelle Userdaten laden
    $sql = "SELECT login_time, total_online_seconds, is_online 
            FROM users WHERE userID = ?";
    $stmt = $_database->prepare($sql);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && $user['is_online'] && !empty($user['login_time'])) {
        $current_session_seconds = time() - strtotime($user['login_time']);
        if ($current_session_seconds < 0) {
            $current_session_seconds = 0; // falls Serverzeit mal spinnt
        }
        $new_total = $user['total_online_seconds'] + $current_session_seconds;

        // Onlinezeit speichern und User abmelden
        $sql = "UPDATE users 
                SET total_online_seconds = ?, login_time = NULL, is_online = 0, last_activity = NULL
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
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();

if (class_exists(LoginCookie::class)) {
    LoginCookie::clear('ws_auth');
}

setcookie('ws_session', '', time() - 3600, '/');
setcookie('ws_cookie', '', time() - 3600, '/');

// --- Redirect ---
header("Location: /");
exit;
