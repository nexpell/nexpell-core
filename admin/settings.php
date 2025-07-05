<?php

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
$languageService->readModule('settings', true);

use webspell\AccessControl;
// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_settings');

if (isset($_GET[ 'action' ])) {
    $action = $_GET[ 'action' ];
} else {
    $action = '';
}


if(isset($_POST['submit'])) {
    $CAPCLASS = new \webspell\Captcha;
    if ($CAPCLASS->checkCaptcha(0, $_POST[ 'captcha_hash' ])) {
        safe_query(
            "UPDATE
                settings
            SET
                hptitle='" . $_POST[ 'hptitle' ] . "',
                hpurl='" . $_POST[ 'url' ] . "',
                clanname='" . $_POST[ 'clanname' ] . "',
                clantag='" . $_POST[ 'clantag' ] . "',
                adminname='" . $_POST[ 'admname' ] . "',
                adminemail='" . $_POST[ 'admmail' ] . "',
                since ='" . $_POST[ 'since' ] . "',
                webkey ='" . $_POST['webkey'] . "',
                seckey ='" . $_POST['seckey'] . "',
                keywords='" . $_POST[ 'keywords' ] . "',
                description='" . $_POST[ 'description' ] . "',
                webkey ='" . $_POST['webkey'] . "',
                seckey ='" . $_POST['seckey'] . "',
                startpage='"  . $_POST[ 'startpage' ] . "'"
        );
        echo '<div class="alert alert-success" role="alert">' . $languageService->get('updated_successfully') . '</div>';
        redirect("admincenter.php?site=settings", '', 2);
    } else {
        echo '<div class="alert alert-danger" role="alert">' . $languageService->get('transaction_invalid') . '</div>';
        redirect("admincenter.php?site=settings", '', 3);  
    }
}

if (isset($_POST["saveedit"])) {
        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            safe_query(
                "UPDATE settings_social_media SET
                    twitch = '" . $_POST[ 'twitch' ] . "',
                    facebook = '" . $_POST[ 'facebook' ] . "',
                    twitter = '" . $_POST[ 'twitter' ] . "',
                    youtube = '" . $_POST[ 'youtube' ] . "',
                    rss = '" . $_POST[ 'rss' ] . "',
                    vine = '" . $_POST[ 'vine' ] . "',
                    flickr = '" . $_POST[ 'flickr' ] . "',
                    linkedin = '" . $_POST[ 'linkedin' ] . "',
                    instagram = '" . $_POST[ 'instagram' ] . "',
                    gametracker = '" . $_POST[ 'gametracker' ] . "',
                    steam = '" . $_POST[ 'steam' ] . "',
                    discord = '" . $_POST[ 'discord' ] . "'"
            );
            echo '<div class="alert alert-success" role="alert">' . $languageService->get('updated_successfully') . '</div>';
            redirect("admincenter.php?site=settings&action=social_setting", '', 2);
        } else {
            echo '<div class="alert alert-danger" role="alert">' . $languageService->get('transaction_invalid') . '</div>';
            redirect("admincenter.php?site=settings&action=social_setting", '', 3);
        }

}


#==== Allgemeine Einstellungen=============#
if ($action == "") { 

$settings = safe_query("SELECT * FROM settings");
$ds = mysqli_fetch_array($settings);

// Ausgabe starten
echo '
<div class="card">
    <div class="card-header"><i class="bi bi-house-gear"></i> ' . $languageService->get('settings') . '</div>
    <div class="card-body">
        <a href="admincenter.php?site=settings" class="btn btn-primary disabled" type="button">
            <i class="bi bi-gear"></i> ' . $languageService->get('settings') . '
        </a>
        <a href="admincenter.php?site=settings&action=social_setting" class="btn btn-primary" type="button">
            <i class="bi bi-gear-wide-connected"></i> ' . $languageService->get('social_settings') . '
        </a>';

$CAPCLASS = new \webspell\Captcha;
$CAPCLASS->createTransaction();
$hash = $CAPCLASS->getHash();

echo '
        
            <form class="form-horizontal" method="post" id="post" name="post" action="admincenter.php?site=settings" onsubmit="return chkFormular();">

               
                        <div class="row">
                            <div class="col-md-6">

                                <div class="card border-primary mb-4 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title text-primary mb-3">
                                            🌐 ' . $languageService->get('site_settings') .'
                                        </h5>
                                        <p class="card-text text-muted">
                                            Hier kannst du die grundlegenden Informationen zu deiner Website sowie wichtige SEO-Einstellungen eintragen. Diese helfen, deine Seite besser in Suchmaschinen zu positionieren.
                                        </p>

                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label fw-semibold">' . $languageService->get('page_url') . ':</label>
                                            <div class="col-md-8">
                                                <input class="form-control" type="url" name="url" value="' . htmlspecialchars($ds['hpurl']) . '">
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label fw-semibold">' . $languageService->get('since') . ':</label>
                                            <div class="col-md-8">
                                                <input class="form-control" type="text" name="since" value="' . htmlspecialchars($ds['since']) . '">
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label fw-semibold">SEO & ' . $languageService->get('page_title') . ':</label>
                                            <div class="col-md-8">
                                                <input class="form-control" type="text" name="hptitle" value="' . htmlspecialchars($ds['hptitle']) . '">
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label fw-semibold">' . $languageService->get('meta_keywords') . ':</label>
                                            <div class="col-md-8">
                                                <textarea class="form-control" name="keywords" rows="5">' . htmlspecialchars($ds['keywords']) . '</textarea>
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label fw-semibold">' . $languageService->get('meta_description') . ':</label>
                                            <div class="col-md-8">
                                                <textarea class="form-control" name="description" rows="5">' . htmlspecialchars($ds['description']) . '</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div> <!-- col-md-6 -->

                            <div class="col-md-6">

                                <div class="card border-success mb-4 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title text-success mb-3">
                                            ⚙️ ' . $languageService->get('general_settings') . '
                                        </h5>
                                        <p class="card-text text-muted">
                                            Allgemeine Informationen zu deinem Clan oder Projekt, die auf der gesamten Seite sichtbar sind. Diese Angaben können jederzeit geändert werden.
                                        </p>

                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label fw-semibold">' . $languageService->get('clan_name') . ':</label>
                                            <div class="col-md-8">
                                                <input class="form-control" type="text" name="clanname" value="' . htmlspecialchars($ds['clanname']) . '">
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label fw-semibold">' . $languageService->get('clan_tag') . ':</label>
                                            <div class="col-md-8">
                                                <input class="form-control" type="text" name="clantag" value="' . htmlspecialchars($ds['clantag']) . '">
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label fw-semibold">' . $languageService->get('admin_name') . ':</label>
                                            <div class="col-md-8">
                                                <input class="form-control" type="text" name="admname" value="' . htmlspecialchars($ds['adminname']) . '">
                                            </div>
                                        </div>

                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label fw-semibold">' . $languageService->get('admin_email') . ':</label>
                                            <div class="col-md-8">
                                                <input class="form-control" type="email" name="admmail" value="' . htmlspecialchars($ds['adminemail']) . '">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                ';

// Site lock info holen und Button bestimmen
$db = mysqli_fetch_array(safe_query("SELECT * FROM settings"));
$lock = ($db['closed'] == '1') ? 'success' : 'danger';
$text_lock = ($db['closed'] == '1') ? $languageService->get('off_pagelock') : $languageService->get('on_pagelock');

echo '
                                <div class="card border-warning mb-4 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title text-warning mb-3">🔒 Website deaktivieren</h5>
                                        <p class="card-text text-muted">
                                            Deaktiviere die öffentliche Erreichbarkeit deiner Website vorübergehend. Besucher sehen eine Wartungsseite, während Administratoren weiterhin Zugriff auf das Backend haben.
                                        </p>

                                        <div class="row align-items-center mt-3">
                                            <div class="col-md-4 fw-semibold">' . $languageService->get('additional_options') . ':</div>
                                            <div class="col-md-8">
                                                <a class="btn btn-' . $lock . '" href="admincenter.php?site=site_lock">' . $text_lock . '</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>';


// Plugins einlesen
$modules = ['news_manager', 'about_us', 'history', 'calendar', 'blog', 'forum'];
$widget_alle = "<option value='blank'>" . $languageService->get('no_startpage') . "</option>\n";
$widget_alle .= "<option value='startpage'>Startpage</option>\n";

foreach ($modules as $modul) {
    $dx = mysqli_fetch_array(safe_query("SELECT * FROM settings_plugins WHERE modulname='" . $modul . "'"));
    if (@$dx['modulname'] == $modul) {
        $widget_alle .= "<option value='{$modul}'>" . ucfirst(str_replace("_", " ", $modul)) . "</option>\n";
    }
}

$widget_startpage = str_replace(
    "value='" . $ds['startpage'] . "'",
    "value='" . $ds['startpage'] . "' selected='selected'",
    $widget_alle
);

echo '
                                <div class="card border-info mb-4 shadow-sm">
                                    <div class="card-body">
                                        <h5 class="card-title text-info mb-3">🏠 ' . $languageService->get('startpage') . '</h5>
                                        <p class="card-text text-muted">
                                            Wähle die Seite aus, die als Startseite deiner Website angezeigt werden soll. Diese Seite wird nach dem Aufruf der Domain zuerst geladen.
                                        </p>

                                        <div class="row align-items-center mt-3">
                                            <div class="col-md-4 fw-semibold">' . $languageService->get('startpage') . ':</div>
                                            <div class="col-md-8">
                                                <select class="form-select" name="startpage">' . $widget_startpage . '</select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div> <!-- col-md-6 -->
                        </div> <!-- row -->

                        <div class="card border-secondary mb-4 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title text-secondary mb-3">🖼️ ' . $languageService->get('reCaptcha') . '</h5>
                                <div class="row align-items-center">
                                    <!-- Beschreibung -->
                                    <div class="col-md-4 mb-3">
                                        <p class="text-muted">' . $languageService->get('recaptcha_description') . '</p>
                                    </div>

                                    <!-- Bild -->
                                    <div class="col-md-4 mb-3 text-center">
                                        <img src="/admin/images/recapcha.png" class="img-fluid rounded shadow-sm" style="max-height:150px;" alt="Google reCAPTCHA">
                                    </div>

                                    <!-- Eingabefelder -->
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="webkey" class="form-label fw-semibold">' . $languageService->get('web-key') . ':</label>
                                            <input id="webkey" class="form-control" type="text" name="webkey" value="' . htmlspecialchars($ds['webkey']) . '">
                                        </div>
                                        <div class="mb-3">
                                            <label for="seckey" class="form-label fw-semibold">' . $languageService->get('secret-key') . ':</label>
                                            <input id="seckey" class="form-control" type="text" name="seckey" value="' . htmlspecialchars($ds['seckey']) . '">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    

            <div class="mb-3 row">
                <div class="col-md-12"><br>
                    <input type="hidden" name="captcha_hash" value="' . $hash . '">
                    <button class="btn btn-warning" type="submit" name="submit">
                        <i class="bi bi-box-arrow-down"></i> ' . $languageService->get('update') . '
                    </button>
                </div>
            </div>
            </form>

            
       
    </div>
</div>
';




#==== Social Einstellungen=============#

#==== Social Einstellungen=============#

} elseif ($action == "social_setting") {

echo '<div class="card">
        <div class="card-header">
            <i class="bi bi-house-gear"></i> ' . $languageService->get('social_settings') . '
        </div>
        <div class="card-body">
            <a href="admincenter.php?site=settings" class="btn btn-primary">
                <i class="bi bi-gear"></i> ' . $languageService->get('settings') . '
            </a>
            <a href="admincenter.php?site=settings&action=social_setting" class="btn btn-primary disabled">
               <i class="bi bi-gear-wide-connected"></i> ' . $languageService->get('social_settings') . '
            </a>';

// Social-Media-Einstellungen aus der DB laden
$ds = mysqli_fetch_array(safe_query("SELECT * FROM settings_social_media"));

// Captcha-Objekt erzeugen und Transaktion starten
$CAPCLASS = new \webspell\Captcha;
$CAPCLASS->createTransaction();
$hash = $CAPCLASS->getHash();

// Formular-Karte starten
echo '<div class="card border-secondary mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="card-title text-secondary mb-3">
                💡 ' . $languageService->get('title_social_media') . '
            </h5>
            <div class="row align-items-center">

                <!-- Beschreibung -->
                <div class="col-md-4 mb-3">
                    <p class="text-muted">
                        Dieses Formular ermöglicht die zentrale Verwaltung aller Social-Media-Links der Website. Admins können Plattformen wie Facebook, Twitter, Discord & Co. über passende Icons schnell erkennen und die URLs bequem aktualisieren. Ideal für eine einheitliche Darstellung im Frontend.
                    </p>
                </div>

                <form action="admincenter.php?site=settings&action=social_setting" method="post" class="row g-3">';

$social_fields = [
    'gametracker' => 'bi-controller',
    'discord'     => 'bi-discord',
    'twitch'      => 'bi-twitch',
    'steam'       => 'bi-steam',
    'facebook'    => 'bi-facebook',
    'twitter'     => 'bi-twitter-x',
    'youtube'     => 'bi-youtube',
    'rss'         => 'bi-rss',
    'linkedin'    => 'bi-linkedin',
    'instagram'   => 'bi-instagram',
];

// Social-Media-Felder mit Icons und Eingabefeldern
foreach ($social_fields as $field => $icon) {
    $label = ucfirst($field);
    echo '<div class="col-md-6">
            <label class="form-label fw-semibold">
                <i class="bi ' . $icon . '"></i> ' . $label . ':
            </label>
            <input type="text" name="' . $field . '" class="form-control" value="' . htmlspecialchars($ds[$field]) . '">
          </div>';
}

// Spezialfälle: Vine & Flickr mit eigenen SVG-Icons
echo '<div class="col-md-6">
        <label class="form-label fw-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" height="16" viewBox="0 0 384 512"><path fill="currentColor" d="..."/></svg> Vine:
        </label>
        <input type="text" name="vine" class="form-control" value="' . htmlspecialchars($ds['vine']) . '">
      </div>';

echo '<div class="col-md-6">
        <label class="form-label fw-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" height="16" viewBox="0 0 448 512"><path fill="currentColor" d="..."/></svg> Flickr:
        </label>
        <input type="text" name="flickr" class="form-control" value="' . htmlspecialchars($ds['flickr']) . '">
      </div>';

echo '<div class="col-12 text-end mt-3">
        <input type="hidden" name="captcha_hash" value="' . $hash . '" />
        <input type="hidden" name="socialID" value="' . (int)$ds['socialID'] . '" />
        <button class="btn btn-warning" type="submit" name="saveedit">
            <i class="bi bi-save"></i> ' . $languageService->get('update') . '
        </button>
      </div>
    </form>

    </div> <!-- .row -->
  </div> <!-- .card-body -->
</div> <!-- .card -->
</div> <!-- .card-body main -->
</div> <!-- .main card -->';

}  
?>