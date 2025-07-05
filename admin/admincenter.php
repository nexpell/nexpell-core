<?php
/**
 * ─────────────────────────────────────────────────────────────────────────────
 * Webspell-RM 3.0 - Modern Content & Community Management System
 * ─────────────────────────────────────────────────────────────────────────────
 *
 * @version       3.0
 * @build         Stable Release
 * @release       2025
 * @copyright     © 2018–2025 Webspell-RM | https://www.webspell-rm.de
 * 
 * @description   Webspell-RM is a modern open source CMS designed for gaming
 *                communities, esports teams, and digital projects of any kind.
 * 
 * @author        Based on the original WebSPELL Clanpackage by Michael Gruber
 *                (webspell.at), further developed by the Webspell-RM Team.
 * 
 * @license       GNU General Public License (GPL)
 *                This software is distributed under the terms of the GPL.
 *                It is strictly prohibited to remove this copyright notice.
 *                For license details, see: https://www.gnu.org/licenses/gpl.html
 * 
 * @support       Support, updates, and plugins available at:
 *                → Website: https://www.webspell-rm.de
 *                → Forum:   https://www.webspell-rm.de/forum.html
 *                → Wiki:    https://www.webspell-rm.de/wiki.html
 * 
 * ─────────────────────────────────────────────────────────────────────────────
 */

// Session starten, falls noch nicht gestartet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use webspell\RoleManager;


// Überprüfen, ob der Benutzer bereits eingeloggt ist
#if (isset($_SESSION['userID'])) {
    // Wenn der Benutzer eingeloggt ist, Weiterleitung zum Admincenter
#    header("Location: /admin/admincenter.php");
#    exit;
#}

// Überprüfen, ob ein Login-Versuch gemacht wurde
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ws_user'], $_POST['password'])) {
    $ws_user = trim($_POST['ws_user']);
    $password = $_POST['password'];

    // loginCheck-Funktion aufrufen, die den Benutzer validiert
    $result = loginCheck($ws_user, $password);

    if ($result->state == "success") {
        // Erfolgreiches Login, setze die Session und leite weiter
        $_SESSION['userID'] = $result->userID; // Setze die Benutzer-ID (falls nötig)
        $_SESSION['username'] = $result->username; // Setze den Benutzernamen
        $_SESSION['email'] = $result->email; // Setze die E-Mail (falls nötig)

        // Weiterleitung zur entsprechenden Seite
        $redirect_url = isset($_SESSION['login_redirect']) ? $_SESSION['login_redirect'] : '/admin/admincenter.php'; // Standard zu admincenter.php
        unset($_SESSION['login_redirect']); // Lösche den Referrer, um Endlosschleifen zu vermeiden
        header("Location: " . $redirect_url);
        exit;
    } else {
        // Fehlermeldung anzeigen, wenn Login fehlgeschlagen ist
        echo "<div class='alert alert-warning'>" . $result->message . "</div>";
    }
}

// Fehlernachricht anzeigen, falls aus admincheck.php weitergeleitet wurde
if (isset($_GET['error']) && $_GET['error'] === 'login_required') {
    echo "<div class='alert alert-warning'>Bitte melde dich zuerst an.</div>";
}

// Einbindung wichtiger Systemdateien
chdir('../');
include('system/config.inc.php');
include('system/settings.php');
include('system/functions.php');
include('system/plugin.php');
include('system/widget.php');
include('system/version.php');
include('system/multi_language.php');
include('system/classes/Template.php');
include('system/classes/TextFormatter.php');
chdir('admin');


// Plugin-Manager laden und Sprachmodul für Admincenter einbinden
$load = new plugin_manager();
#$_language->readModule('admincenter', false, true);


use webspell\LanguageService;

// Session absichern
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Standard setzen, wenn nicht vorhanden
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

// Initialisieren
global $languageService;
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('admincenter', true);
#$languageService->readModule('admincenter'); 

// Site-Parameter festlegen, falls vorhanden
$site = isset($_GET['site']) ? $_GET['site'] : (isset($site) ? $site : null);

// Cookie für Adminrechte prüfen
$cookievalueadmin = 'false';
if (isset($_COOKIE['ws_cookie'])) {
    $cookievalueadmin = 'accepted';
}

// Überprüfen, ob der Benutzer eine gültige Rolle hat und eingeloggt ist
if (!isset($_SESSION['userID']) || !checkUserRoleAssignment($_SESSION['userID'])) {
    // Fehlerseite anzeigen, wenn der Benutzer keine Rolle zugewiesen hat oder nicht eingeloggt ist
    echo '
    <div style="
        background-color: #e74c3c;
        color: white;
        padding: 20px;
        border-radius: 8px;
        font-family: Arial, sans-serif;
        max-width: 600px;
        margin: 50px auto;
        text-align: center;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    ">
        <img src="images/rm.png" alt="Logo" style="
            width: 400px;
            height: auto;
            margin-bottom: 20px;
            border-radius: 6px;
        ">
        <h2 style="margin-top: 0;">Zugriff verweigert</h2>
        <p>Sie haben derzeit <strong>keine Benutzerrolle</strong> zugewiesen und können daher nicht auf diesen Bereich zugreifen.</p>
        <p>Bitte wenden Sie sich an einen Administrator, um Ihre Zugriffsrechte zu prüfen.</p>
        <p style="margin-top: 20px;">Sie werden in <strong>10 Sekunden</strong> automatisch zur Login-Seite weitergeleitet...</p>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = "login.php";
        }, 10000);
    </script>
    ';
    exit;
}

$userID = $_SESSION['userID'];

if (!isset($_SERVER['REQUEST_URI'])) {
	$arr = explode('/', $_SERVER['PHP_SELF']);
	$_SERVER['REQUEST_URI'] = '/' . $arr[count($arr) - 1];
	if ($_SERVER['argv'][0] != '') {
		$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['argv'][0];
	}
}

function getplugincatID($catname)
{
    // Bereite die SQL-Anfrage vor, um SQL-Injection zu verhindern
    $stmt = $_database->prepare("SELECT * FROM `navigation_dashboard_categories` WHERE name LIKE ?");
    $searchTerm = '%' . $catname . '%';
    $stmt->bind_param('s', $searchTerm);  // 's' für String
    $stmt->execute();
    $result = $stmt->get_result();

    // Prüfen, ob eine Kategorie gefunden wurde
    if ($ds = $result->fetch_assoc()) {
        // Kategorie gefunden, nun Links überprüfen
        $stmt2 = $_database->prepare("SELECT * FROM `navigation_dashboard_links` WHERE catID = ?");
        $stmt2->bind_param('i', $ds['catID']);  // 'i' für Integer
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        // Wenn Links vorhanden sind, zurückgeben, dass Links existieren
        if ($result2->num_rows >= 1) {
            return true;
        } else {
            return false; // Keine Links in der Kategorie
        }
    } else {
        // Keine Kategorie mit diesem Namen gefunden
        return false;
    }
}



function dashnavi() {
    global $userID;

    $links = '';
    // aktuelle URL ermitteln
    $current_script = basename($_SERVER['PHP_SELF']);
    $current_query = isset($_GET['site']) ? $_GET['site'] : '';

    // Kategorien holen
    $ergebnis = safe_query("SELECT * FROM navigation_dashboard_categories ORDER BY sort");

    while ($ds = mysqli_fetch_array($ergebnis)) {
        $catID = (int)$ds['catID'];
        $name = $ds['name'];
        $fa_name = $ds['fa_name'];

        $lang = $_SESSION['language'] ?? 'de';

        $translate = new multiLanguage($lang);
        $translate->detectLanguages($name);
        $name = $translate->getTextByLanguage($name);

        if (checkAccessRights($userID, $catID)) {

            // Prüfen ob ein Link dieser Kategorie aktiv ist
            $catlinks = safe_query("SELECT * FROM navigation_dashboard_links WHERE catID='" . $catID . "' ORDER BY sort");

            $cat_active = false; // merken ob irgendwas aktiv ist
            $cat_links_html = '';

            while ($db = mysqli_fetch_array($catlinks)) {
                $linkID = (int)$db['linkID'];
                $url = $db['url'];

                $translate->detectLanguages($db['name']);
                $link_name = $translate->getTextByLanguage($db['name']);

                if (checkAccessRights($userID, null, $linkID)) {

                    // Ist der Link aktiv?
                    $url_parts = parse_url($url);
                    parse_str($url_parts['query'] ?? '', $url_query);

                    $is_active = false;
                    if (isset($url_query['site']) && $url_query['site'] == $current_query) {
                        $is_active = true;
                        $cat_active = true; // Sobald einer aktiv, ganze Kategorie merken
                    }

                    $active_class = $is_active ? 'active' : '';

                    $icon_class = $active_class ? 'bi bi-arrow-right' : 'bi bi-plus-lg';

                    $cat_links_html .= '<li class="' . $active_class . '">'
                        . '<a href="' . $url . '">'
                        . '<i class="' . $icon_class . ' ac-link"></i> ' 
                        . $link_name 
                        . '</a></li>';
                }
            }

            if ($cat_links_html != '') {
                $expand_class = $cat_active ? 'mm-active' : ''; // mm-active hält Menü offen
                $aria_expanded = $cat_active ? 'true' : 'false';
                $show_class = $cat_active ? 'style="display:block;"' : '';

                $links .= '<li class="' . $expand_class . '">
                    <a class="has-arrow" aria-expanded="' . $aria_expanded . '" href="#">
                        <i class="' . $fa_name . '" style="font-size: 1rem;"></i> ' . $name . '
                    </a>
                    <ul class="nav nav-third-level" ' . $show_class . '>
                        ' . $cat_links_html . '
                    </ul>
                </li>';
            }
        }
    }

    return $links ? $links : '<li>Keine zugriffsberechtigten Links gefunden.</li>';
}


if ($userID && !isset($_GET['userID']) && !isset($_POST['userID'])) {
	$ds = mysqli_fetch_array(safe_query("SELECT registerdate FROM `users` WHERE userID='" . $userID . "'"));
	$username = '<a class="nav-link nav-link-3" href="../index.php?site=profile&amp;id=' . $userID . '">' . getusername($userID) . '</a>';
	$lastlogin = !empty($ds['lastlogin']) ? date("d.m.Y H:i", strtotime($ds['lastlogin'])) : '-';
    $registerdate = date("d.m.Y H:i", strtotime($ds['registerdate']));
    
	$data_array = array();
	$data_array['$username'] = $username;
	$data_array['$lastlogin'] = $lastlogin;
	$data_array['$registerdate'] = $registerdate;
}

if ($getavatar = getavatar($userID)) {
	$l_avatar = $getavatar;
} else {
	$l_avatar = "noavatar.png";
}

header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($languageService->detectLanguage(), ENT_QUOTES, 'UTF-8') ?>">

<head>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Website using webSPELL-RM CMS">
	<meta name="copyright" content="Copyright &copy; 2017-2023 by webspell-rm.de">
	<meta name="author" content="webspell-rm.de">

	<link rel="SHORTCUT ICON" href="/admin/images/favicon.ico">

	<title>Webspell-RM - Bootstrap Admin Theme</title>

	<!-- Bootstrap Core CSS -->
	<link href="/admin/css/bootstrap.min.css" rel="stylesheet">
	<link href="/admin/css/bootstrap-switch.css" rel="stylesheet">

	<!-- side-bar CSS -->
	<link href="/admin/css/page.css" rel="stylesheet">
	<link href="/admin/css/metisMenu.css" rel="stylesheet" />

	<!-- Custom Fonts -->
	<link href="/admin/css/bootstrap-icons.min.css" rel="stylesheet">

	<!-- colorpicker -->
	<link href="/admin/css/bootstrap-colorpicker.min.css" rel="stylesheet">
    

</head>

<body>

	<div id="wrapper">
		<!-- Navigation -->

		<ul class="nav justify-content-between" style="width: 100%; margin-bottom: 25px; margin-top: 0px;background: #eaeaea;box-shadow: rgba(0, 0, 0, 0.1) 0px 4px 6px -1px, rgba(0, 0, 0, 0.06) 0px 2px 4px -1px; !important;">
   
            <li class="nav-item" style="width: 80%;margin-left: 6px;">
                <a class="navbar-brand" href="/admin/admincenter.php">
        		            <img src="/admin/images/logo.png" style="width: 230px;margin-top: 7px; margin-bottom: 7px;" alt="setting">
        		        </a>
            </li>
            <li class="nav-item">
                <a class="nav-link nav-link-2"><?= $languageService->module['welcome'] ?> </a>
            </li>
            <li class="nav-item">
                <?php echo @$username ?>
            </li>
            <li class="nav-item dropdown" style="margin-right: 18px;">
                <a class="nav-link nav-link-3 dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo $languageService->module['logout'] ?>
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="../index.php"><i class="bi bi-arrow-clockwise text-success"></i> <?php echo $languageService->module['back_to_website'] ?></a></li>
                    <li><a class="dropdown-item" href="/admin/admincenter.php?site=logout"><i class="bi bi-x-lg text-danger"></i> <?php echo $languageService->module['logout'] ?></a></li>
                </ul>
            </li>
        </ul>



		<!-- /.navbar-top-links -->
		<!-- sidebar-links -->
		<nav class="navbar-default sidebar navbar-dark" role="navigation" style="margin-top: 5px;">
		    <div style="padding: 0 0 10px 0;" id="ws-image">
		        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
		            <span class="navbar-toggler-icon"></span>
		        </button>
                <?php
                $avatar_url = getavatar($userID);
                $username = getusername($userID);
                ?>
                <img id="avatar-big"
                     src="../<?php echo $avatar_url ?>"
                     class="rounded-circle profile_img"
                     style="height: 90px; margin-top: 9px; margin-bottom: 9px; -webkit-box-shadow: 2px 2px 15px 3px rgba(0,0,0,0.54); box-shadow: 2px 2px 15px 3px rgba(0,0,0,0.54); border: 3px solid #fe821d; border-radius: 25px; --bs-tooltip-bg: #fe821d;"
                     data-bs-toggle="tooltip"
                     data-bs-placement="right"
                     title="<?php echo $username ?>"
                     data-bs-html="true">
		        <div class="sidebar-nav col1lapse navbar-collapse" id="navbarNavDropdown">
                    <a class="link-head" href="admincenter.php">Dashboard</a>
		            <ul class="nav metismenu text-start navbar-nav" id="side-bar">
		                <?php echo dashnavi(); ?>
		            </ul>
		        </div>
		        <div class="copy">
                    <em>Admin Template powered by <a href="https://www.nexpell.de" target="_blank" rel="noopener">nexpell</a></em>
                </div>
		    </div>
		</nav>

		<!-- /.navbar-static-side -->

		<div id="page-wrapper">
			<?php
			if (isset($site) && $site != "news") {
    $invalide = array('\\', '/', '//', ':', '.');
    $site = str_replace($invalide, ' ', $site); // Entferne ungültige Zeichen

    if (file_exists($site . '.php')) {
        include($site . '.php');
    } else {
        chdir("../"); // <<< WICHTIG: Hier wechselst du ins Elternverzeichnis (aus admin/ raus)
        
        $plugin = $load->plugin_data($site, 0, true);
        $plugin_path = @$plugin['path'];  // z.B. "includes/plugins/news/"
        @$ifiles = $plugin['admin_file']; // z.B. "news.php"
        @$tfiles = explode(",", $ifiles);

        if (file_exists($plugin_path . "admin/" . $site . ".php")) {
            include($plugin_path . "admin/" . $site . ".php");
        } else {
            #echo '<div class="alert alert-danger" role="alert">' . $languageService->module['plugin_not_found'] . '</div>';
            include('info.php');
        }
    }
} else {
    include('info.php');
}
			?>
		</div><!-- /#wrapper -->
		
		<?php
		

		#$roleID = RoleManager::getUserRoleID($userID);

        #if ($roleID !== null && RoleManager::roleHasPermission($roleID, 'ckeditor_full')) {
            echo '<script src="../components/ckeditor/ckeditor.js"></script>';
            echo '<script src="../components/ckeditor/config.js"></script>';
        #} else {
        #    echo '<script src="../components/ckeditor/ckeditor.js"></script>';
        #    echo '<script src="../components/ckeditor/user_config.js"></script>';
        #}
		?>

		<!-- jQuery -->
		<script src="/admin/js/jquery.min.js"></script>

		<script src="/admin/js/page.js"></script>

		<!-- colorpicker -->
		<script src="/admin/js/bootstrap-colorpicker.min.js"></script>
		<script src="/admin/js/colorpicker-rm.js"></script>

		<!-- Bootstrap -->
		<script src="/admin/js/bootstrap.bundle.min.js"></script>
		<script src="/admin/js/bootstrap-switch.js"></script>

		<!-- Menu Plugin JavaScript -->
		<script src="/admin/js/metisMenu.min.js"></script>
		<script src="/admin/js/side-bar.js"></script>

		<script>
			var calledfrom = 'admin';
		</script>
		<!-- dataTables -->
		<!--<script type="text/javascript" src="/admin/js/jquery.dataTables.min.js"></script>
		<script type="text/javascript" src="/admin/js/dataTables.bootstrap5.min.js"></script>-->
		

		<script type="text/javascript">
			// setup tools tips trigger
			const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
			const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
				return new Tooltip(tooltipTriggerEl, {
					html: true // <- this should do the trick!
				})
			});
		</script>
        
</body>

</html>