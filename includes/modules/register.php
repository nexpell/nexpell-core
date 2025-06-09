<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use webspell\LoginSecurity;
use webspell\Email;
use webspell\LanguageService;

global $_database,$languageService;

$lang = $languageService->detectLanguage();
$languageService->readModule('register');

$form_data = $_POST ?? [];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$password_repeat = $_POST['password_repeat'] ?? '';
$terms = isset($_POST['terms']);
$ip_address = $_SERVER['REMOTE_ADDR'];

$registrierung_erfolgreich = false;
$isreg = false;
$message = '';

$stmt = $_database->prepare("
    SELECT COUNT(*) FROM user_register_attempts 
    WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL 30 MINUTE)
");
$stmt->bind_param("s", $ip_address);
$stmt->execute();
$stmt->bind_result($attempt_count);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ungültiges Formular (CSRF-Schutz).");
    }

    $captcha_valid = true;
    $errors = false;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = $languageService->get('invalid_email');
        $errors = true;
    } elseif (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/', $username)) {
        $_SESSION['error_message'] = $languageService->get('invalid_username');
        $errors = true;
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $_SESSION['error_message'] = $languageService->get('invalid_password');
        $errors = true;
    } elseif ($password !== $password_repeat) {
        $_SESSION['error_message'] = $languageService->get('password_mismatch');
        $errors = true;
    } elseif (!$terms) {
        $_SESSION['error_message'] = $languageService->get('terms_required');
        $errors = true;
    }

    $stmt = $_database->prepare("SELECT userID FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = $languageService->get('email_exists');
    }
    $stmt->close();

    if (!empty($errors)) {
        $_SESSION['error_message'] = implode("<br>", (array)$errors);
        header("Location: index.php?site=register");
        exit;
    }

    $avatar = 'noavatar.png';
    $role = 1;
    $is_active = 0;

    $stmt = $_database->prepare("INSERT INTO users (username, email, registerdate, role, is_active, avatar) VALUES (?, ?, UNIX_TIMESTAMP(), ?, ?, ?)");
    $stmt->bind_param("ssiis", $username, $email, $role, $is_active, $avatar);
    if (!$stmt->execute()) {
        die("Fehler beim Einfügen: " . $stmt->error);
    }

    $userID = $_database->insert_id;
    $pepper_plain = LoginSecurity::generatePepper();
    $pepper_encrypted = openssl_encrypt($pepper_plain, 'aes-256-cbc', LoginSecurity::AES_KEY, 0, LoginSecurity::AES_IV);
    $password_hash = LoginSecurity::createPasswordHash($password, $email, $pepper_plain);

    $stmt = $_database->prepare("UPDATE users SET password_hash = ?, password_pepper = ? WHERE userID = ?");
    $stmt->bind_param("ssi", $password_hash, $pepper_encrypted, $userID);
    $stmt->execute();

    $stmt = $_database->prepare("
        INSERT INTO user_register_attempts (ip_address, status, reason, username, email)
        VALUES (?, ?, ?, ?, ?)
    ");

    if ($captcha_valid && !$errors) {
        $status = 'success';
        $reason = null;
    } else {
        $status = 'failed';
        $reason = !$captcha_valid ? 'Captcha falsch' : 'Unbekannter Fehler';
    }

    $stmt->bind_param("sssss", $ip_address, $status, $reason, $username, $email);
    $stmt->execute();
    $stmt->close();

    $activation_code = bin2hex(random_bytes(32));
    $activation_expires = time() + 86400;

    $stmt = $_database->prepare("UPDATE users SET activation_code = ?, activation_expires = ? WHERE userID = ?");
    $stmt->bind_param("sii", $activation_code, $activation_expires, $userID);
    $stmt->execute();

    $activation_link = 'https://' . $_SERVER['HTTP_HOST'] . '/index.php?site=activate&code=' . urlencode($activation_code);

    $settings_result = safe_query("SELECT * FROM `settings`");
    $settings = mysqli_fetch_assoc($settings_result);
    $hp_title = $settings['title'] ?? 'Webspell-RM';
    $hp_url = $settings['hpurl'] ?? 'https://' . $_SERVER['HTTP_HOST'];
    $admin_email = $settings['adminemail'] ?? 'info@' . $_SERVER['HTTP_HOST'];

    $vars = ['%username%', '%activation_link%', '%hp_title%', '%hp_url%'];
    $repl = [$username, $activation_link, $hp_title, $hp_url];

    $subject = str_replace($vars, $repl, $languageService->get('mail_subject'));
    $message = str_replace($vars, $repl, $languageService->get('mail_text'));

    $module = $languageService->get('mail_from_module');

    $sendmail = Email::sendEmail($admin_email, $module, $email, $subject, $message);

    if (is_array($sendmail) && isset($sendmail['result']) && $sendmail['result'] === 'done') {
        $_SESSION['success_message'] = $languageService->get('register_successful');
        $registrierung_erfolgreich = true;
    } else {
        $_SESSION['error_message'] = $languageService->get('mail_failed');
    }

    $isreg = true;
}

$errormessage = '';
$successmessage = '';

if (isset($_SESSION['error_message'])) {
    $errormessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    $successmessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if ($registrierung_erfolgreich) {
    $isreg = true;
}

$values = $_SESSION['formdata'] ?? [];
unset($_SESSION['formdata']);

$data_array = [
    'csrf_token' => htmlspecialchars($csrf_token),
    'error_message' => $errormessage,
    'success_message' => $successmessage,
    'message_zusatz' => '',
    'isreg' => $registrierung_erfolgreich,
    'username' => $username,
    'email' => $email,
    'password_repeat' => $password_repeat,
    'recaptcha_site_key' => 'DEIN_SITE_KEY',
    'reg_title' => $languageService->get('reg_title'),
    'reg_info_text' =>  $languageService->get('reg_info_text'),
    'login_link' => $languageService->get('login_link'),
    'login_text' =>  $languageService->get('login_text'),
    'mail' => $languageService->get('mail'),
    'username_label' => $languageService->get('username'),
    'password_label' => $languageService->get('password'),
    'password_repeat_label' => $languageService->get('password_repeat'),
    'email_address' => $languageService->get('email_address'),
    'enter_your_email' => $languageService->get('enter_your_email'),
    'enter_your_name' => $languageService->get('enter_your_name'),
    'enter_password' => $languageService->get('enter_password'),
    'enter_password_repeat' => $languageService->get('enter_password_repeat'),
    'pass_text' => $languageService->get('pass_text'),
    'register' => $languageService->get('register'),
    'terms_of_use_text' => $languageService->get('terms_of_use_text'),
    'terms_of_use' => $languageService->get('terms_of_use'),
];

echo $tpl->loadTemplate("register", "content", $data_array);

?>
<script>
document.getElementById("password").addEventListener("input", function () {
    const strengthText = document.getElementById("passwordStrength");
    const val = this.value;
    let strength = 0;

    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/\d/.test(val)) strength++;
    if (/[\W_]/.test(val)) strength++;

    const levels = ["Sehr schwach", "Schwach", "Okay", "Stark"];
    const colors = ["#f44336", "#ff9800", "#ffeb3b", "#4caf50"];

    strengthText.textContent = levels[strength - 1] || "";
    strengthText.style.color = colors[strength - 1] || "#f44336";
});
</script>
