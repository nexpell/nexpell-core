<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$message = '';

use nexpell\LoginSecurity;
use nexpell\LanguageService;
use nexpell\SeoUrlHandler;

// Initialisieren
global $_database, $languageService;
$lang = $languageService->detectLanguage();
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('login', false);

$ip = $_SERVER['REMOTE_ADDR'];
$message_zusatz = '';
$isIpBanned = '';
$is_active = '';
$is_locked = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password_hash = $_POST['password_hash'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = $languageService->get('error_invalid_email');
        header("Location: " . SeoUrlHandler::convertToSeoUrl('index.php?site=login'));
        exit;
    }

    $ip = $_SERVER['REMOTE_ADDR'];
    $loginResult = LoginSecurity::verifyLogin($email, $password_hash, $ip, $is_active, $is_locked);

    if ($loginResult['success']) {
        if (LoginSecurity::isIpBanned($ip)) {
            $message = '<div class="alert alert-danger" role="alert">' . $languageService->get('error_ip_banned') . '</div>';
            $isIpBanned = true;
        }

        $stmt = $_database->prepare("SELECT userID, username, email, is_locked FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            if (!empty($user['is_locked']) && (int)$user['is_locked'] === 1) {
                $message = '<div class="alert alert-danger" role="alert">' . $languageService->get('error_account_locked') . '</div>';
                $isIpBanned = true;
            } else {
                $_SESSION['userID'] = (int)$user['userID'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];

                LoginSecurity::saveSession($user['userID']);

                $now = date('Y-m-d H:i:s');
                $updateStmt = $_database->prepare("UPDATE users SET lastlogin = ? WHERE userID = ?");
                $updateStmt->bind_param("si", $now, $user['userID']);
                $updateStmt->execute();

                $_SESSION['success_message'] = $languageService->get('success_login');
                header("Location: /");
                exit;
            }
        } else {
            $message = '<div class="alert alert-danger" role="alert">' . $languageService->get('error_not_found') . '</div>';
        }
    } else {
        $userID = null;
        $stmt = $_database->prepare("SELECT userID, is_active FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $userID = (int)$row['userID'];

            if ((int)$row['is_active'] === 0) {
                $message = '<div class="alert alert-danger" role="alert">' . $languageService->get('error_account_inactive') . '</div>';
                $isIpBanned = true;
            } else {
                if (!LoginSecurity::isEmailOrIpBanned($email, $ip)) {
                    LoginSecurity::trackFailedLogin($userID, $email, $ip);

                    $failCount = LoginSecurity::getFailCount($ip, $email);
                    if ($failCount >= 5) {
                        LoginSecurity::banIp($ip, $userID, "Zu viele Fehlversuche", $email);
                        $_SESSION['error_message'] = $languageService->get('error_login_locked');
                    } else {
                        $_SESSION['error_message'] = str_replace('{failcount}', $failCount, $languageService->get('error_invalid_login'));
                    }
                } else {
                    $message = '<div class="alert alert-danger" role="alert">' . $languageService->get('error_email_or_ip_banned') . '</div>';
                    $isIpBanned = true;
                }
            }
        } else {
            $message = '<div class="alert alert-danger" role="alert">' . $languageService->get('error_not_found') . '</div>';
            $isIpBanned = true;
        }
    }

    if (isset($_SESSION['error_message'])) {
        $message = '<div class="alert alert-danger" role="alert">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']);
    }
}

if (!empty($email)) {
    $isEmailBanned = LoginSecurity::isEmailBanned($ip, $email);
} else {
    $isEmailBanned = false;
}

if ($isEmailBanned) {
    $message = '<div class="alert alert-danger" role="alert">' . $languageService->get('error_email_banned') . '</div>';
    $isIpBanned = true;
}

$registerlink = '<a href="' . SeoUrlHandler::convertToSeoUrl('index.php?site=register') . '">' . $languageService->get('register_link') . '</a>';
$lostpasswordlink = '<a href="' . SeoUrlHandler::convertToSeoUrl('index.php?site=lostpassword') . '">' . $languageService->get('lostpassword_link') . '</a>';

$data_array = [
    'login_headline' => $languageService->get('title'),
    'email_label' => $languageService->get('email_label'),
    'your_email' => $languageService->get('your_email'),
    'pass_label' => $languageService->get('pass_label'),
    'your_pass' => $languageService->get('your_pass'),
    'remember_me' => $languageService->get('remember_me'),
    'login_button' => $languageService->get('login_button'),
    'register_link' => $languageService->get('register_link'),
    'registerlink' => $registerlink,
    'lostpasswordlink' => $lostpasswordlink,
    'error_message' => $message,
    'message_zusatz' => $message_zusatz,
    'isIpBanned' => $isIpBanned,
    'welcome_back' => $languageService->get('welcome_back'),
    'reg_text' => $languageService->get('reg_text'),
    'login_text' => $languageService->get('login_text'),
];

echo $tpl->loadTemplate("login", "content", $data_array, 'theme');
