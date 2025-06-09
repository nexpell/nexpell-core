<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use webspell\LanguageService;

global $_database,$languageService;

$lang = $languageService->detectLanguage();
$languageService->readModule('edit_profile');

// Style aus settings holen
$config = mysqli_fetch_array(safe_query("SELECT selected_style FROM settings_headstyle_config WHERE id=1"));
$class = htmlspecialchars($config['selected_style']);

// Header-Daten
$data_array = [
    'class'    => $class,
    'title' => $languageService->get('title'),
    'subtitle' => 'Imprint'
];

echo $tpl->loadTemplate("edit_profiles", "head", $data_array, 'theme');


$userID = $_SESSION['userID'] ?? null;
if (!$userID) {
    die('Nicht eingeloggt.');
}

/*
if (isset($_GET['username']) && !empty($_GET['username'])) {
    $username = $_GET['username'];

    $stmt = $_database->prepare("SELECT userID FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($userID);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo "Benutzer nicht gefunden.";
        exit();
    }
    $stmt->close();
} else {
    // Falls es über userID läuft oder Session, hier fallback
    $userID = $_SESSION['userID'] ?? 0;
    if ($userID === 0) {
        echo "Kein Benutzer angegeben.";
        exit();
    }
}
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $location = $_POST['location'] ?? '';
    $about_me = $_POST['about_me'] ?? '';

    $twitter = $_POST['twitter'] ?? '';
    $facebook = $_POST['facebook'] ?? '';
    $website = $_POST['website'] ?? '';
    $github = $_POST['github'] ?? '';
    $instagram = $_POST['instagram'] ?? '';

    $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;

    $avatar_url = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['avatar']['tmp_name'];
        $fileName = basename($_FILES['avatar']['name']);
        $fileType = mime_content_type($fileTmpPath);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

        if (!in_array($fileType, $allowedTypes)) {
            die('Ungültiger Dateityp.');
        }

        $uploadDir = 'images/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $newName = "avatar_user{$userID}_" . time() . '.' . $ext;
        $avatarPath = $uploadDir . $newName;

        if (!move_uploaded_file($fileTmpPath, $avatarPath)) {
            die('Upload fehlgeschlagen.');
        }

        $avatar_url = $avatarPath;
    }

    $result = $_database->query("SELECT userID FROM user_profiles WHERE userID = $userID");
    if ($result->num_rows > 0) {
        $query = "UPDATE user_profiles SET 
            firstname = '$firstname',
            lastname = '$lastname',
            location = '$location',
            about_me = '$about_me'";
        if ($avatar_url) {
            $query .= ", avatar = '$avatar_url'";
        }
        $query .= " WHERE userID = $userID";
    } else {
        $columns = "userID, firstname, lastname, location, about_me";
        $values = "$userID, '$firstname', '$lastname', '$location', '$about_me'";
        if ($avatar_url) {
            $columns .= ", avatar";
            $values .= ", '$avatar_url'";
        }
        $query = "INSERT INTO user_profiles ($columns) VALUES ($values)";
    }
    $_database->query($query);

    $result = $_database->query("SELECT userID FROM user_socials WHERE userID = $userID");
    if ($result->num_rows > 0) {
        $_database->query("UPDATE user_socials SET 
            instagram = '$instagram',
            github = '$github',
            twitter = '$twitter',
            facebook = '$facebook',
            website = '$website'
            WHERE userID = $userID");
    } else {
        $_database->query("INSERT INTO user_socials (userID, twitter, facebook, website, github, instagram) 
            VALUES ($userID, '$twitter', '$facebook', '$website', '$github', '$instagram')");
    }

    $result = $_database->query("SELECT userID FROM user_settings WHERE userID = $userID");
    if ($result->num_rows > 0) {
        $_database->query("UPDATE user_settings SET 
            dark_mode = $dark_mode, 
            email_notifications = $email_notifications 
            WHERE userID = $userID");
    } else {
        $_database->query("INSERT INTO user_settings (userID, dark_mode, email_notifications) 
            VALUES ($userID, $dark_mode, $email_notifications)");
    }

    header("Location: index.php?site=profile&userID=$userID");
    exit;
}

$firstname = $lastname = $location = $about_me = $avatar = '';
$twitter = $facebook = $website = $github = $instagram = '';
$dark_mode = $email_notifications = 0;

$result = $_database->query("SELECT firstname, lastname, location, about_me, avatar FROM user_profiles WHERE userID = $userID");
if ($row = $result->fetch_assoc()) {
    extract($row);
}

$result = $_database->query("SELECT twitter, facebook, website, github, instagram FROM user_socials WHERE userID = $userID");
if ($row = $result->fetch_assoc()) {
    extract($row);
}

$result = $_database->query("SELECT dark_mode, email_notifications FROM user_settings WHERE userID = $userID");
if ($row = $result->fetch_assoc()) {
    extract($row);
}

$data_array = [
    // Userdaten mit htmlspecialchars zur Sicherheit
    'userID' => htmlspecialchars($userID ?? ''),
    'firstname' => htmlspecialchars($firstname ?? ($user['firstname'] ?? '')),
    'lastname' => htmlspecialchars($lastname ?? ($user['lastname'] ?? '')),
    'location' => htmlspecialchars($location ?? ($user['location'] ?? '')),
    'about_me' => htmlspecialchars($about_me ?? ($user['about_me'] ?? '')),
    
    // Avatar-URL: wenn $avatar gesetzt, sonst aus $user['avatar']
    'avatar_url' => !empty($avatar) ? $avatar : (!empty($user['avatar']) ? '/path/to/avatars/' . htmlspecialchars($user['avatar']) : ''),
    
    // Social Media
    'twitter' => htmlspecialchars($twitter ?? ($user['twitter'] ?? '')),
    'facebook' => htmlspecialchars($facebook ?? ($user['facebook'] ?? '')),
    'website' => htmlspecialchars($website ?? ($user['website'] ?? '')),
    'github' => htmlspecialchars($github ?? ($user['github'] ?? '')),
    'instagram' => htmlspecialchars($instagram ?? ($user['instagram'] ?? '')),
    
    // Settings Checkbox (checked oder leer)
    'dark_mode_checked' => !empty($dark_mode) ? 'checked' : (!empty($user['dark_mode']) ? 'checked' : ''),
    'email_notifications_checked' => !empty($email_notifications) ? 'checked' : (!empty($user['email_notifications']) ? 'checked' : ''),
    
    // Sprachstrings
    'edit_profile_title' => $languageService->get('edit_profile_title'),
    'label_firstname' => $languageService->get('label_firstname'),
    'label_lastname' => $languageService->get('label_lastname'),
    'label_location' => $languageService->get('label_location'),
    'label_about_me' => $languageService->get('label_about_me'),
    'label_avatar' => $languageService->get('label_avatar'),
    'title_social_networks' => $languageService->get('title_social_networks'),
    'label_twitter' => $languageService->get('label_twitter'),
    'label_facebook' => $languageService->get('label_facebook'),
    'label_website' => $languageService->get('label_website'),
    'label_github' => $languageService->get('label_github'),
    'label_instagram' => $languageService->get('label_instagram'),
    'title_settings' => $languageService->get('title_settings'),
    'label_dark_mode' => $languageService->get('label_dark_mode'),
    'label_email_notifications' => $languageService->get('label_email_notifications'),
    'btn_save' => $languageService->get('btn_save'),
];



echo $tpl->loadTemplate("edit_profiles", "content", $data_array, 'theme');
?>
