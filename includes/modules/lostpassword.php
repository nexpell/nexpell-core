<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LoginSecurity;
use nexpell\Email;
use nexpell\LanguageService;
use nexpell\SeoUrlHandler;

global $_database,$languageService;

$lang = $languageService->detectLanguage();
$languageService->readModule('lostpassword');

// Einstellungen laden
$settings_result = safe_query("SELECT * FROM `settings`");
$settings = mysqli_fetch_assoc($settings_result);

$hp_title = $settings['hptitle'] ?? 'nexpell';
$hp_url = $settings['hpurl'] ?? 'https://' . $_SERVER['HTTP_HOST'];
$admin_email = $settings['adminemail'] ?? 'info@' . $_SERVER['HTTP_HOST'];

$success = isset($_GET['success']) && $_GET['success'] == 1;

if ($success && isset($_SESSION['success_message'])) {
    $data_array = [
        'title' => $languageService->module['title'],
        'forgotten_your_password' => $languageService->module['forgotten_your_password'],
        'message' => '<div class="alert alert-success" role="alert">' . $_SESSION['success_message'] . '</div>',
        'return_to_login' => '<a href="' . convertToSeoUrl('index.php?site=login') . '" class="btn btn-success">' . $languageService->module['login'] . '</a>'
    ];
    unset($_SESSION['success_message']);
    echo $tpl->loadTemplate("lostpassword", "success", $data_array, 'theme');
    return;
}

if (isset($_POST['submit'])) {
    // E-Mail aus POST-Daten holen und sicherstellen, dass sie nicht leer ist
    $email = LoginSecurity::escape(trim($_POST['email']));

    if ($email !== '') {
        // Datenbankabfrage, um den Benutzer zu finden
        $result = safe_query("SELECT * FROM `users` WHERE `email` = '" . $email . "'");

        if (mysqli_num_rows($result)) {
            $ds = mysqli_fetch_array($result);

            // Überprüfen, ob ein Pepper vorhanden ist
            if (!empty($ds['password_pepper'])) {
                // Neues lesbares Passwort generieren
                $new_password_plain = LoginSecurity::generateReadablePassword();
                
                // Pepper entschlüsseln
                $pepper_plain = LoginSecurity::decryptPepper($ds['password_pepper']);

                if ($pepper_plain === false || $pepper_plain === '') {
                    $_SESSION['error_message'] = '❌ Fehler beim Entschlüsseln des Peppers.';
                    header("Location: " . convertToSeoUrl('index.php?site=lostpassword'));
                    exit;
                }

                // Neues Passwort hashen
                $new_password_hash = password_hash($new_password_plain . $ds['email'] . $pepper_plain, PASSWORD_BCRYPT);

                // Passwort in der Datenbank aktualisieren
                safe_query("
                    UPDATE `users`
                    SET `password_hash` = '" . LoginSecurity::escape($new_password_hash) . "'
                    WHERE `userID` = '" . intval($ds['userID']) . "'
                ");

                // Platzhalter und Ersetzungen für E-Mail-Versand vorbereiten
                $vars = ['%pagetitle%', '%email%', '%new_password%', '%homepage_url%'];
                $repl = [$hp_title, $ds['email'], $new_password_plain, $hp_url];

                // Betreff und Nachricht der E-Mail
                $subject = str_replace($vars, $repl, $languageService->module['email_subject']);
                $message = str_replace($vars, $repl, $languageService->module['email_text']);

                // E-Mail senden
                $sendmail = Email::sendEmail($admin_email, 'Passwort zurückgesetzt', $ds['email'], $subject, $message);

                if ($sendmail['result'] === 'fail') {
                    // Fehler bei der E-Mail-Zustellung
                    $_SESSION['error_message'] = $languageService->module['email_failed'] . ' ' . $sendmail['error'];
                    header("Location: " . convertToSeoUrl('index.php?site=lostpassword'));
                    exit;
                } else {
                    // Erfolgreiche Passwortzurücksetzung
                    $_SESSION['success_message'] = str_replace($vars, $repl, $languageService->module['successful']);
                    header("Location: " . convertToSeoUrl('index.php?site=lostpassword&success=1'));
                    exit;
                }
            } else {
                // Kein Pepper in der Datenbank vorhanden
                $_SESSION['error_message'] = '❌ Kein Pepper in der Datenbank.';
                header("Location: " . convertToSeoUrl('index.php?site=lostpassword'));
                exit;
            }
        } else {
            // Benutzer nicht gefunden
            $_SESSION['error_message'] = $languageService->module['no_user_found'];
            header("Location: " . convertToSeoUrl('index.php?site=lostpassword'));
            exit;
        }
    } else {
        // Keine E-Mail eingegeben
        $_SESSION['error_message'] = $languageService->module['no_mail_given'];
        header("Location: " . convertToSeoUrl('index.php?site=lostpassword'));
        exit;
    }
}

// Fehlernachricht
$message = '';
if (isset($_SESSION['error_message'])) {
    $message = '<div class="alert alert-danger" role="alert">' . $_SESSION['error_message'] . '</div>';
    unset($_SESSION['error_message']);
}

$registerlink = '<a href="' . SeoUrlHandler::convertToSeoUrl('index.php?site=register') . '">' . $languageService->get('register_link') . '</a>';
$loginlink = '<a href="' . SeoUrlHandler::convertToSeoUrl('index.php?site=login') . '">' . $languageService->get('login') . '</a>';


// Formular anzeigen
$data_array = [
    'title' => $languageService->module['title'],
    'forgotten_your_password' => $languageService->module['forgotten_your_password'],
    'info1' => $languageService->module['info1'],
    'info2' => $languageService->module['info2'],
    'info3' => $languageService->module['info3'],
    'your_email' => $languageService->module['your_email'],
    'get_password' => $languageService->module['get_password'],
    'return_to' => $languageService->module['return_to'],
    'loginlink' => $loginlink,
    'email-address' => $languageService->module['email-address'],
    'reg' => $languageService->module['reg'],
    'need_account' => $languageService->module['need_account'],
    'error_message' => $message,
    'lastpassword_txt' => $languageService->module['lastpassword_txt'],
    'registerlink' =>  $registerlink,
    'welcome_back' =>  $languageService->module['welcome_back'],    
    'reg_text' =>  $languageService->module['reg_text'],
    'login_text' =>  $languageService->module['login_text'],
];

echo $tpl->loadTemplate("lostpassword", "content_area", $data_array, 'theme');
