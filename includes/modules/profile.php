<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use webspell\LanguageService;

global $_database, $languageService;

$lang = $languageService->detectLanguage();
$languageService->readModule('profile');

// Style aus settings holen
$config = mysqli_fetch_array(safe_query("SELECT selected_style FROM settings_headstyle_config WHERE id=1"));
$class = htmlspecialchars($config['selected_style']);

// Header-Daten
$data_array_header = [
    'class'    => $class,
    'title' => $languageService->get('title'),
    'subtitle' => 'Profil'
];
echo $tpl->loadTemplate("profile", "head", $data_array_header);

// userID aus GET oder Session (neu: prüfe 'userID' oder 'id')
if (isset($_GET['userID'])) {
    $userID = (int)$_GET['userID'];
} elseif (isset($_GET['id'])) {
    $userID = (int)$_GET['id'];
} else {
    $userID = $_SESSION['userID'] ?? 0;
}

if ($userID === 0) {
    echo "Kein Benutzer angegeben.";
    exit();
}

// user_profile laden
$sql_users = "SELECT * FROM users WHERE userID = $userID LIMIT 1";
$result_users = $_database->query($sql_users);
if (!$result_users || $result_users->num_rows === 0) {
    echo "Benutzerprofil nicht gefunden.";
    exit();
}
$user_users = $result_users->fetch_assoc();

$sql_profiles = "SELECT * FROM user_profiles WHERE userID = $userID LIMIT 1";
$result_profiles = $_database->query($sql_profiles);
$user_profile = $result_profiles->fetch_assoc();

$sql_stats = "SELECT * FROM user_stats WHERE userID = $userID LIMIT 1";
$result_stats = $_database->query($sql_stats);
$user_stats = $result_stats && $result_stats->num_rows > 0 ? $result_stats->fetch_assoc() : [];

$sql_settings = "SELECT * FROM user_settings WHERE userID = $userID LIMIT 1";
$result_settings = $_database->query($sql_settings);
$user_settings = $result_settings && $result_settings->num_rows > 0 ? $result_settings->fetch_assoc() : [];

$sql_socials = "SELECT * FROM user_socials WHERE userID = $userID LIMIT 1";
$result_socials = $_database->query($sql_socials);
$user_socials = $result_socials && $result_socials->num_rows > 0 ? $result_socials->fetch_assoc() : [];

$sql = "
    SELECT 
        u.username,
        u.registerdate,
        u.lastlogin,
        r.role_name
    FROM users u
    LEFT JOIN user_role_assignments ura ON u.userID = ura.userID
    LEFT JOIN user_roles r ON ura.roleID = r.roleID
    WHERE u.userID = $userID
    LIMIT 1
";
$result = $_database->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $username = htmlspecialchars($row['username']);
    $role_name = !empty($row['role_name']) ? htmlspecialchars($row['role_name']) : 'Benutzer';
    $register_date_raw = $row['registerdate'];
    $last_visit_raw = $row['lastlogin'];
} else {
    $username = 'Unbekannt';
    $role_name = 'Benutzer';
    $register_date_raw = null;
    $last_visit_raw = null;
}

$firstname    = htmlspecialchars($user_profile['firstname'] ?? 'Nicht angegeben');
$lastname     = htmlspecialchars($user_profile['lastname'] ?? 'Nicht angegeben');
$about_me     = !empty($user_profile['about_me']) ? htmlspecialchars($user_profile['about_me']) : 'Keine Informationen über mich.';
$register_date = (!empty($register_date_raw) && strtotime($register_date_raw) !== false)
    ? date('d.m.Y', strtotime($register_date_raw))
    : 'Unbekannt';

$last_visit = (!empty($last_visit_raw) && strtotime($last_visit_raw) !== false)
    ? date('d.m.Y H:i', strtotime($last_visit_raw))
    : 'Nie besucht';

$avatar_url = !empty($user_profile['avatar']) ? '' . $user_profile['avatar'] : "/images/avatars/noavatar.png";
$location = !empty($user_profile['location']) ? htmlspecialchars($user_profile['location']) : 'Unbekannter Ort';
$age = !empty($user_profile['age']) ? (int)$user_profile['age'] : 'Nicht angegeben';
$sexuality = !empty($user_profile['sexuality']) ? htmlspecialchars($user_profile['sexuality']) : 'Nicht angegeben';

$articles  = getUserCount('plugins_articles', 'userID', $userID);
$comments  = getUserCount('comments', 'userID', $userID);
$rules     = getUserCount('plugins_rules', 'userID', $userID);
$links     = getUserCount('plugins_links', 'userID', $userID);
$partners  = getUserCount('plugins_partners', 'userID', $userID);
$sponsors  = getUserCount('plugins_sponsors', 'userID', $userID);
$forum     = getUserCount('plugins_forum_posts', 'userID', $userID);
$points    = ($articles * 10) + ($comments * 2) + ($rules * 5) + ($links * 5) + ($partners * 5) + ($sponsors * 5) + ($forum * 2);

$level = floor($points / 100);
$level_percent = $points % 100;

$facebook_url  = !empty($user_socials['facebook']) ? htmlspecialchars($user_socials['facebook']) : '#';
$twitter_url   = !empty($user_socials['twitter']) ? htmlspecialchars($user_socials['twitter']) : '#';
$instagram_url = !empty($user_socials['instagram']) ? htmlspecialchars($user_socials['instagram']) : '#';
$website_url   = !empty($user_socials['website']) ? htmlspecialchars($user_socials['website']) : '#';
$github_url    = !empty($user_socials['github']) ? htmlspecialchars($user_socials['github']) : '#';

$is_own_profile = ($_SESSION['userID'] ?? 0) === $userID;
$edit_button = $is_own_profile ? '<a href="/edit_profile" class="btn btn-outline-primary mt-3"><i class="fas fa-user-edit"></i>' . $languageService->get('edit_profile_button') . '</a>' : '';

$isLocked = isset($user_users['is_locked']) && (int)$user_users['is_locked'] === 1;

$last_activity = (!empty($last_visit_raw) && strtotime($last_visit_raw) !== false) ? strtotime($last_visit_raw) : 0;
$current_time = time();

if ($last_activity > 0 && $last_activity <= $current_time) {
    $online_seconds = $current_time - $last_activity;
    $online_hours = floor($online_seconds / 3600);
    $online_minutes = floor(($online_seconds % 3600) / 60);
    $online_time = "$online_hours Stunden, $online_minutes Minuten";
} else {
    $online_time = "Keine Aktivität";
}

$stmt = $_database->prepare("SELECT COUNT(*) AS login_count FROM user_sessions WHERE userID = ?");
$stmt->bind_param('i', $userID);
$stmt->execute();
$stmt->bind_result($logins_count);
$stmt->fetch();
$stmt->close();
$logins = $logins_count > 0 ? $logins_count : 0;

function getUserCount($table, $col, $userID) {
    global $_database;
    if (!tableExists($table)) {
        return 0;
    }
    $stmt = $_database->prepare("SELECT COUNT(*) FROM `$table` WHERE `$col` = ?");
    $stmt->bind_param('i', $userID);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

$post_type = '';
$userID = $_GET['userID'] ?? 0;

$tables = [
    ['table' => 'plugins_articles', 'user_col' => 'userID', 'type' => 'Artikel'],
    ['table' => 'comments', 'user_col' => 'userID', 'type' => 'Kommentare'],
    ['table' => 'plugins_clan_rules', 'user_col' => 'userID', 'type' => 'Clan-Regeln'],
    ['table' => 'plugins_partners', 'user_col' => 'userID', 'type' => 'Partners'],
    ['table' => 'plugins_sponsors', 'user_col' => 'userID', 'type' => 'Sponsoren'],
    ['table' => 'plugins_links', 'user_col' => 'userID', 'type' => 'Links'],
    ['table' => 'plugins_forum_posts', 'user_col' => 'userID', 'type' => 'Forum'],
];

$counts = [];

foreach ($tables as $table) {
    $tableName = $table['table'];
    $userCol = $table['user_col'];
    $type = $table['type'] ?? ucfirst($tableName);

    if (tableExists($tableName)) {
        $counts[$type] = getUserCount($tableName, $userCol, $userID);
    }
}

foreach ($counts as $type => $count) {
    $post_type .= "<p>$type: $count</p>";
}

if ($isLocked == 1 ) {
    $isrowLocked='<div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="bi bi-lock-fill me-2"></i> Dieses Profil ist gesperrt.
    </div>';
}

$data_array = [
    'username'        => $username,
    'user_picture'    => $avatar_url,
    'user_role'       => $role_name,
    'user_points'     => $points,
    'user_about'      => $about_me,
    'user_signature'  => $user_profile['signature'] ?? '„Keep coding & carry on.“',
    'user_name'       => $firstname,
    'user_surname'    => $lastname,
    'user_age'        => $age,
    'user_location'   => $location,
    'user_sexuality'  => $sexuality,
    'register_date'   => $register_date, 
    'user_activity'   => '<p>Zuletzt online: ' . $last_visit . '</p><p>Online-Zeit: ' . $online_time . '</p><p>Logins: ' . $logins . '</p>',
    'github_url'      => $github_url,
    'twitter_url'     => $twitter_url,
    'facebook_url'    => $facebook_url,
    'website_url'     => $website_url,
    'instagram_url'   => $instagram_url,
    'last_visit'      => $last_visit,
    'user_level'      => $level,
    'level_progress'  => $level_percent,
    'edit_button'     => $edit_button,
    'user_posts'      => $post_type,
    'isLocked'        => $isrowLocked ?? '',

    // Sprachvariablen ergänzen
    'lang_alt_profile_picture' => $languageService->get('alt_profile_picture'),
    'lang_points'              => $languageService->get('points'),
    'lang_level'               => $languageService->get('level'),
    'lang_tooltip_github'      => $languageService->get('tooltip_github'),
    'lang_tooltip_twitter'     => $languageService->get('tooltip_twitter'),
    'lang_tooltip_instagram'   => $languageService->get('tooltip_instagram'),
    'lang_tooltip_website'     => $languageService->get('tooltip_website'),

    'lang_tab_about'           => $languageService->get('tab_about'),
    'lang_tab_posts'           => $languageService->get('tab_posts'),
    'lang_tab_activity'        => $languageService->get('tab_activity'),

    'lang_about_title'         => $languageService->get('about_title'),
    'lang_about_firstname'     => $languageService->get('about_firstname'),
    'lang_about_lastname'      => $languageService->get('about_lastname'),
    'lang_about_age'           => $languageService->get('about_age'),
    'lang_about_location'      => $languageService->get('about_location'),
    'lang_about_sexuality'     => $languageService->get('about_sexuality'),
    'lang_registered_since'    => $languageService->get('registered_since'),
    'lang_about_text'          => $languageService->get('about_text'),
    'lang_about_signature'     => $languageService->get('about_signature'),

    'lang_latest_posts'        => $languageService->get('latest_posts'),
    'lang_latest_activity'     => $languageService->get('latest_activity'),
];


// Ausgabe Template
echo $tpl->loadTemplate("profile", "content", $data_array);

// Neue Ausgabe-Zeile mit Fallback-Check
#if ($tpl->templateExists("profile", "content")) {
#    echo $tpl->loadTemplate("profile", "content", $data_array);
#} else {
#    echo "<div class='alert alert-warning'>Profil-Template nicht gefunden.</div>";
#}
