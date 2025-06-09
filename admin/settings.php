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
                default_language='" . $_POST[ 'language' ] . "',                
                de_lang='" . isset($_POST[ 'de_lang' ]) . "',
                en_lang='" . isset($_POST[ 'en_lang' ]) . "',
                it_lang='" . isset($_POST[ 'it_lang' ]) . "',
                keywords='" . $_POST[ 'keywords' ] . "',
                description='" . $_POST[ 'description' ] . "',
                webkey ='" . $_POST['webkey'] . "',
                seckey ='" . $_POST['seckey'] . "',
                startpage='"  . $_POST[ 'startpage' ] . "'"
        );
        
        redirect("admincenter.php?site=settings", $languageService->get('updated_successfully'), 2);
    } else {
        redirect("admincenter.php?site=settings", $languageService->get('transaction_invalid'), 3);  
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

            redirect("admincenter.php?site=settings&action=social_setting", $languageService->get('updated_successfully'), 2);
        } else {
            redirect("admincenter.php?site=settings&action=social_setting", $languageService->get('transaction_invalid'), 3);
        }

}


#==== Allgemeine Einstellungen=============#
if ($action == "") { 


$settings = safe_query("SELECT * FROM settings");
$ds = mysqli_fetch_array($settings);

// Sprachverzeichnis vorbereiten
$langdirs = '';
$filepath = "../languages/";
$langs = array();

if ($dh = opendir($filepath)) {
    while (($file = readdir($dh)) !== false) {
        $langcode = mb_substr($file, 0, 2);
        if ($langcode != "." && $langcode != ".." && is_dir($filepath . $langcode)) {
            if (isset($mysql_langs[$langcode])) {
                $name = ucfirst($mysql_langs[$langcode]);
                $langs[$name] = $langcode;
            } else {
                $langs[$langcode] = $langcode;
            }
        }
    }
    closedir($dh);
}

ksort($langs, SORT_NATURAL);
foreach ($langs as $lang => $flag) {
    $langdirs .= '<option value="' . $flag . '">' . $lang . '</option>';
}

$lang = $default_language;
$langdirs = str_replace('value="' . $lang . '"', 'value="' . $lang . '" selected="selected"', $langdirs);

// Sprachcheckboxen
$de_lang = '<input class="form-check-input" type="checkbox" name="de_lang" value="1" ' . ($ds['de_lang'] ? 'checked="checked"' : '') . ' />';
$en_lang = '<input class="form-check-input" type="checkbox" name="en_lang" value="1" ' . ($ds['en_lang'] ? 'checked="checked"' : '') . ' />';
$it_lang = '<input class="form-check-input" type="checkbox" name="it_lang" value="1" ' . ($ds['it_lang'] ? 'checked="checked"' : '') . ' />';

// Ausgabe starten
echo '<div class="card">
    <div class="card-header"><i class="bi bi-house-gear"></i> ' . $languageService->get('settings') . '</div>
    <div class="card-body">';

echo '
    <a href="admincenter.php?site=settings" class="btn btn-primary disabled" type="button"><i class="bi bi-gear"></i> ' . $languageService->get('settings') . '</a>
    <a href="admincenter.php?site=settings&action=social_setting" class="btn btn-primary" type="button"><i class="bi bi-gear-wide-connected"></i> ' . $languageService->get('social_settings') . '</a>';

$CAPCLASS = new \webspell\Captcha;
$CAPCLASS->createTransaction();
$hash = $CAPCLASS->getHash();

echo '<div class="">
    <form class="form-horizontal" method="post" id="post" name="post" action="admincenter.php?site=settings" onsubmit="return chkFormular();">

    <div class="card">
        <div class="card-header"><i class="bi bi-gear"></i> ' . $languageService->get('settings') . '</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">

                    <div class="mb-3 row">
                        <div class="col-md-4">' . $languageService->get('page_url') . ':</div>
                        <div class="col-md-8">
                            <input class="form-control" type="url" name="url" value="' . htmlspecialchars($ds['hpurl']) . '">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <div class="col-md-4">' . $languageService->get('since') . ':</div>
                        <div class="col-md-8">
                            <input class="form-control" type="text" name="since" value="' . htmlspecialchars($ds['since']) . '">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <div class="col-md-4">SEO & ' . $languageService->get('page_title') . ':</div>
                        <div class="col-md-8">
                            <input class="form-control" type="text" name="hptitle" value="' . htmlspecialchars($ds['hptitle']) . '">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <div class="col-md-4">' . $languageService->get('meta_keywords') . ':</div>
                        <div class="col-md-8">
                            <input class="form-control" type="text" name="keywords" value="' . htmlspecialchars($ds['keywords']) . '">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <div class="col-md-4">' . $languageService->get('meta_description') . ':</div>
                        <div class="col-md-8">
                            <textarea class="form-control" name="description" rows="5">' . htmlspecialchars($ds['description']) . '</textarea>
                        </div>
                    </div>

                </div>

                <div class="col-md-6">

                    <div class="mb-3 row">
                        <div class="col-md-4">' . $languageService->get('clan_name') . ':</div>
                        <div class="col-md-8">
                            <input class="form-control" type="text" name="clanname" value="' . htmlspecialchars($ds['clanname']) . '">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <div class="col-md-4">' . $languageService->get('clan_tag') . ':</div>
                        <div class="col-md-8">
                            <input class="form-control" type="text" name="clantag" value="' . htmlspecialchars($ds['clantag']) . '">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <div class="col-md-4">' . $languageService->get('admin_name') . ':</div>
                        <div class="col-md-8">
                            <input class="form-control" type="text" name="admname" value="' . htmlspecialchars($ds['adminname']) . '">
                        </div>
                    </div>

                    <div class="mb-3 row">
                        <div class="col-md-4">' . $languageService->get('admin_email') . ':</div>
                        <div class="col-md-8">
                            <input class="form-control" type="email" name="admmail" value="' . htmlspecialchars($ds['adminemail']) . '">
                        </div>
                    </div>

                    <hr>';

$db = mysqli_fetch_array(safe_query("SELECT * FROM settings"));
$lock = ($db['closed'] == '1') ? 'success' : 'danger';
$text_lock = ($db['closed'] == '1') ? $languageService->get('off_pagelock') : $languageService->get('on_pagelock');

echo '
                    <div class="mb-3 row">
                        <div class="col-md-4">' . $languageService->get('additional_options') . ':</div>
                        <div class="col-md-8">
                            <a class="btn btn-' . $lock . '" href="admincenter.php?site=site_lock">' . $text_lock . '</a>
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
                    <div class="mb-3 row">
                        <div class="col-md-4">' . $languageService->get('startpage') . ':</div>
                        <div class="col-md-8">
                            <select class="form-select" name="startpage">' . $widget_startpage . '</select>
                        </div>
                    </div>

                </div> <!-- col-md-6 -->
            </div> <!-- row -->
        </div> <!-- card-body -->
    </div> <!-- card -->

   
</div>
';






















    echo'      
      
<div class="card">
    <div class="card-header"><i class="bi bi-google"></i> 
        '.$languageService->get('reCaptcha').' 
    </div>
    <div class="card-body">

        <div class="row">
            <div class="col-md-4">
                <div class="mb-3 row">
                    <label class="col-md-12">
                '.$languageService->get('important_text').' </label>
                </div>
            </div> 

            <div class="col-md-4">
                <div class="mb-3 row">
                    <label class="col-md-12">
                    <img src="/admin/images/recapcha.png" class="img-fluid" style="height:150px" alt="...">
                    </label>
                </div>
            </div>    

            <div class="col-md-4">
                <div class="mb-3 row">
                    <label class="col-md-4 control-label">'.$languageService->get('web-key').' :</label>
                    <div class="col-md-8"><span class="text-muted mdall"><em><input class="form-control" type="text" name="webkey" value="'.$ds['webkey'].'"></em></span>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label class="col-md-4 control-label">'.$languageService->get('secret-key').' :</label>
                    <div class="col-md-8"><span class="text-muted mdall"><em><input class="form-control" type="text" name="seckey" value="'.$ds['seckey'].'"></em></span>
                    </div>
                </div>

                
            </div>
        </div>
    </div>
</div>
   





        <div class="card">
            <div class="card-header"><i class="bi bi-three-dots"></i> 
                '.$languageService->get('other').' 
            </div>
            <div class="card-body">
                
                    <div class="col-md-12">
                        
                                
                        <div class="mb-3 row">
                            <div class="col-md-5">
                                '.$languageService->get('default_language').' :
                            </div>

                            <div class="col-md-4">
                                <span class="pull-left text-muted mdall"><em data-toggle="tooltip" data-html="true" title="'.$languageService->get('tooltip_40').'"><select class="form-select" name="language">
                                                '.$langdirs.' 
                                            </select></em></span>
                            </div>
                        </div>

                                    


                        <div class="mb-3 row">
                            <div class="col-md-12">'.$languageService->get('language_navi').' </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-md-5">
                                '.$languageService->get('de_language').' :
                            </div>

                            <div class="col-md-4 form-check form-switch" style="padding: 0px 43px;">
                                <span class="text-start"><em data-toggle="tooltip" data-html="true" title="'.$languageService->get('tooltip_66').'">'.$de_lang.'</em></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-md-5">
                                '.$languageService->get('en_language').' :
                            </div>
                            <div class="col-md-4 form-check form-switch" style="padding: 0px 43px;">
                                <span class="pull-left text-muted mdall"><em data-toggle="tooltip" data-html="true" title="'.$languageService->get('tooltip_67').'">'.$en_lang.'</em></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-md-5">
                                '.$languageService->get('it_language').' :
                            </div>
                            <div class="col-md-4 form-check form-switch" style="padding: 0px 43px;">
                                <span class="pull-left text-muted mdall"><em data-toggle="tooltip" data-html="true" title="'.$languageService->get('tooltip_68').'">'.$it_lang.'</em></span>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-md-5" style="height:50px">
                                            
                            </div>
                            <div class="col-md-7 form-check form-switch" style="padding: 0px 43px;">
                                        
                            </div>
                        </div>


                                    

                    </div>
                    
            </div>
        </div>
        
        <div class="mb-3 row">
            <div class="col-md-12"><br>
                <input type="hidden" name="captcha_hash" value="'.$hash.'"> 
                <button class="btn btn-warning" type="submit" name="submit"><i class="bi bi-box-arrow-down"></i> '.$languageService->get('update').' </button>
            </div>
        </div>

    </form>';



#==== Social Einstellungen=============#

#==== Social Einstellungen=============#

} elseif ($action == "social_setting") {

        echo '<div class="card">
                <div class="card-header"><i class="bi bi-gear-wide-connected"></i> ' . $languageService->get('social_settings') . '</div>
                <div class="card-body">';

        echo '<a href="admincenter.php?site=settings" class="btn btn-primary"><i class="bi bi-house-gear"></i> ' . $languageService->get('settings') . '</a>
              <a href="admincenter.php?site=settings&action=social_setting" class="btn btn-primary disabled">' . $languageService->get('social_settings') . '</a>';

        $ds = mysqli_fetch_array(safe_query("SELECT * FROM settings_social_media"));

        $CAPCLASS = new \webspell\Captcha;
        $CAPCLASS->createTransaction();
        $hash = $CAPCLASS->getHash();

        echo '<div class="card mt-4">
                <div class="card-header"><i class="bi bi-gear-wide-connected"></i> ' . $languageService->get('title_social_media') . '</div>
                <div class="card-body">
                    <form action="admincenter.php?site=settings&action=social_setting" method="post" role="form" class="form-horizontal">';

        $social_fields = [
            'gametracker' => 'bi-controller',
            'discord' => 'bi-discord',
            'twitch' => 'bi-twitch',
            'steam' => 'bi-steam',
            'facebook' => 'bi-facebook',
            'twitter' => 'bi-twitter-x',
            'youtube' => 'bi-youtube',
            'rss' => 'bi-rss',
            'vine' => '', // eigener SVG
            'flickr' => '', // eigener SVG
            'linkedin' => 'bi-linkedin',
            'instagram' => 'bi-instagram'
        ];

        foreach ($social_fields as $field => $icon) {
            $label = ucfirst($field);
            echo '<div class="mb-3 row">';
            echo '<label class="col-xs-12 col-md-2 control-label">';
            if ($icon) {
                echo '<i class="bi ' . $icon . '"></i>';
            } else {
                if ($field == 'vine') {
                    echo '<svg xmlns="http://www.w3.org/2000/svg" height="16" width="12" viewBox="0 0 384 512"><path d="M384 254.7v52.1c-18.4 4.2-36.9 6.1-52.1 6.1-36.9 77.4-103 143.8-125.1 156.2-14 7.9-27.1 8.4-42.7-.8C137 452 34.2 367.7 0 102.7h74.5C93.2 261.8 139 343.4 189.3 404.5c27.9-27.9 54.8-65.1 75.6-106.9-49.8-25.3-80.1-80.9-80.1-145.6 0-65.6 37.7-115.1 102.2-115.1 114.9 0 106.2 127.9 81.6 181.5 0 0-46.4 9.2-63.5-20.5 3.4-11.3 8.2-30.8 8.2-48.5 0-31.3-11.3-46.6-28.4-46.6-18.2 0-30.8 17.1-30.8 50 .1 79.2 59.4 118.7 129.9 101.9z"/></svg>';
                } elseif ($field == 'flickr') {
                    echo '<svg xmlns="http://www.w3.org/2000/svg" height="16" width="12" viewBox="0 0 448 512"><path d="M400 32H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V80c0-26.5-21.5-48-48-48zM144.5 319c-35.1 0-63.5-28.4-63.5-63.5s28.4-63.5 63.5-63.5 63.5 28.4 63.5 63.5-28.4 63.5-63.5 63.5zm159 0c-35.1 0-63.5-28.4-63.5-63.5s28.4-63.5 63.5-63.5 63.5 28.4 63.5 63.5-28.4 63.5-63.5 63.5z"/></svg>';
                }
            }
            echo '&nbsp;' . $label . ':</label>';
            echo '<div class="col-xs-12 col-md-10">';
            echo '<input type="text" name="' . $field . '" class="form-control" value="' . htmlspecialchars($ds[$field]) . '">';
            echo '</div></div>';
        }

        echo '<div class="mb-3 row">
                <div class="col-sm-11"></div>
                <div class="col-sm-11">
                    <input type="hidden" name="captcha_hash" value="' . $hash . '" />
                    <input type="hidden" name="socialID" value="' . (int)$ds['socialID'] . '" />
                    <button class="btn btn-warning" type="submit" name="saveedit"><i class="bi bi-box-arrow-down"></i> ' . $languageService->get('update') . '</button>
                </div>
              </div>
            </form>
        </div>
      </div>';
    

}

echo '</div></div>
  </div>';
  
?>