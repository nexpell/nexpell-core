<?php

use nexpell\LanguageService;

// Session absichern
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Standard setzen, wenn nicht vorhanden
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

// Initialisieren
global $languageService;
$lang = $languageService->detectLanguage();
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('plugin_manager', true);

use nexpell\AccessControl;
// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_plugin_manager');

if (isset($_GET['action'])) {
    $action = $_GET['action'];
} else {
    $action = '';
}

$theme_active = safe_query("SELECT * FROM settings_themes WHERE active = '1'");
$db = mysqli_fetch_array($theme_active);

if (!empty(@$db['active'] == 1) !== false) {


    #Aktive und Deaktivieren vom Plugin START

    if (isset($_GET['do'])) {
        $do = $_GET['do'];
    } else {
        $do = "";
    }
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
    } else {
        $id = "";
    }
    if (isset($_GET['modulname'])) {
        $modulname = $_GET['modulname'];
    } else {
        $modulname = "";
    }

    if ($id != "" && $modulname != "" && $do == "dea") {

        try {

            safe_query("UPDATE `settings_plugins` SET `activate` = '0' WHERE `pluginID` = '" . $id . "';");
            safe_query("UPDATE `navigation_website_sub` SET `indropdown` = '0' WHERE `modulname` =  '" . $_GET['modulname'] . "';");
            echo $languageService->get('success_deactivated');
            redirect("admincenter.php?site=plugin_manager", "", 1);
            return false;
        } catch (Exception $e) {
            echo $languageService->get('success_deactivated') . "<br /><br />" . $e->getMessage();
            redirect("admincenter.php?site=plugin_manager", "", 5);
            return false;
        }
    }
    if ($id != "" && $modulname != "" && $do == "act") {
        try {
            safe_query("UPDATE `settings_plugins` SET `activate` = '1' WHERE `pluginID` = '" . $id . "';");
            safe_query("UPDATE `navigation_website_sub` SET `indropdown` = '1' WHERE `modulname` =  '" . $_GET['modulname'] . "';");
            echo $languageService->get('success_activated');
            redirect("admincenter.php?site=plugin_manager", "", 1);
            return false;
        } catch (Exception $e) {
            echo $languageService->get('failed_activated') . "<br /><br />" . $e->getMessage();
            redirect("admincenter.php?site=plugin_manager", "", 5);
            return false;
        }
    }
    #Aktive und Deaktivieren vom Plugin END

    #Erstellt eine neue Plugin-Einstellung START
    if (isset($_POST['svn'])) {
        if (isset($_POST['activate'])) {
            $acti = 1;
        } else {
            $acti = 0;
        }

        # Creazione della tabella dinamica se non esiste
        $table_name = "plugins_" . $_POST['modulname'] . "_settings_widgets";
        $table_name = "plugins_" . $_POST['modulname'] . "_settings_widgets";
        safe_query(
            "CREATE TABLE IF NOT EXISTS `" . $table_name . "` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `position` varchar(255) NOT NULL DEFAULT '',
                `modulname` varchar(100) NOT NULL DEFAULT '',
                `themes_modulname` varchar(255) NOT NULL DEFAULT '',
                `widgetname` varchar(255) NOT NULL DEFAULT '',
                `widgetdatei` varchar(255) NOT NULL DEFAULT '',
                `activated` int(1) DEFAULT 1,
                `sort` int(11) DEFAULT 1,
                PRIMARY KEY (`id`)
            ) AUTO_INCREMENT=1
              DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;"
        );

        safe_query(
            "INSERT INTO `" . $table_name . "` (`id`, `position`, `modulname`, `themes_modulname`, `widgetname`, `widgetdatei`, `activated`, `sort`) VALUES
            (1, 'navigation_widget', 'navigation', 'default', 'Navigation', 'widget_navigation', 1, 1),
            (2, 'footer_widget', 'footer_easy', 'default', 'Footer Easy', 'widget_footer_easy', 1, 1);"
        );

        try {
            safe_query(
                "INSERT INTO `settings_plugins` (
                    `pluginID`, 
                    `name`, 
                    `modulname`, 
                    `info`, 
                    `activate`, 
                    `admin_file`, 
                    `author`, 
                    `website`, 
                    `index_link`,
                    `hiddenfiles`, 
                    `version`, 
                    `path`,
                    `status_display`,
                    `plugin_display`,
                    `widget_display`,
                    `delete_display`,
                    `sidebar`
                    ) VALUES (
                    NULL, 
                    '" . $_POST['name'] . "', 
                    '" . $_POST['modulname'] . "',
                    '" . $_POST['info'] . "', 
                    '1', 
                    '" . $_POST['admin_file'] . "', 
                    '" . $_POST['author'] . "', 
                    '" . $_POST['website'] . "', 
                    '" . $_POST['index'] . "',
                    '" . $_POST['hiddenfiles'] . "', 
                    '" . $_POST['version'] . "', 
                    '" . $_POST['path'] . "',
                    '1',
                    '1',
                    '1',
                    '1',
                    'deactivated'
                );
            "
            );

            echo $languageService->get('success_save') . "<br /><br />";
            redirect("admincenter.php?site=plugin_manager", "", 1);
            return false;
        } catch (Exception $e) {
            echo $languageService->get('failed_save') . "<br /><br />" . $e->getMessage();
            redirect("admincenter.php?site=plugin_manager", "", 5);
            return false;
        }
        return false;
    }
    #Erstellt eine neue Plugin-Einstellung END


    if (isset($_GET['action']) && $_GET['action'] == "delete_plugin" && isset($_GET['modulname'])) {
        $modulname = $_GET['modulname']; // ACHTUNG: Idealerweise per prepared statement, aber hier:
        $modulname_safe = mysqli_real_escape_string($_database, $modulname);

        // Prüfe, ob Plugin existiert
        $plugin_name_query = safe_query("SELECT modulname FROM settings_plugins WHERE modulname = '" . $modulname_safe . "'");

        if (mysqli_num_rows($plugin_name_query) > 0) {
            $plugin_name = mysqli_fetch_assoc($plugin_name_query)['modulname'];

            echo '<div class="alert alert-info"><strong><i class="bi bi-trash3"></i> ' . $languageService->get('delete_plugin') . ':</strong> ' . htmlspecialchars($plugin_name, ENT_QUOTES, 'UTF-8') . '</div>';

            // 1) Entferne aus globaler Widget-Tabelle
            safe_query("DELETE FROM `settings_plugins_widget` WHERE `modulname` = '" . $plugin_name . "'");

            // 2) Entferne genau die plugins_[modulname]_settings_widgets Tabelle
            $table_to_drop = "plugins_" . $plugin_name . "_settings_widgets";
            $check_table = safe_query("SHOW TABLES LIKE '" . $table_to_drop . "'");
            if (mysqli_num_rows($check_table) > 0) {
                safe_query("DROP TABLE `$table_to_drop`");
            }

            // 3) Entferne Plugin aus settings_plugins
            safe_query("DELETE FROM `settings_plugins` WHERE `modulname` = '" . $plugin_name . "'");

            // 4) Redirect
            flush();
            echo '<script>
                    setTimeout(function(){ 
                        window.location.href = "admincenter.php?site=plugin_manager"; 
                    }, 2000);
                </script>';
        } else {
            echo '<div class="alert alert-danger"><strong><i class="bi bi-x-circle"></i> Error:</strong> Plugin <b>' . htmlspecialchars($modulname, ENT_QUOTES, 'UTF-8') . '</b> was not found in <b>settings_plugins</b>.</div>';
        }
    }


    // Fine della cancellazione del plugin e dei suoi widget


    #Erstellt eine neue Widget-Einstellung START
    if (isset($_POST['widget_add'])) {
        try {
            safe_query(
                "INSERT INTO `settings_plugins_widget` (
                    `id`,
                    `modulname`, 
                    `widgetname`, 
                    `widgetdatei`, 
                    `area`
                    ) VALUES (
                    NULL,
                    '" . $_POST['modulname'] . "',
                    '" . $_POST['widgetname'] . "',
                    '" . $_POST['widgetdatei'] . "', 
                    '" . $_POST['area'] . "'
                );
            "
            );

            echo $languageService->get('success_save') . "<br /><br />";
            redirect("admincenter.php?site=plugin_manager&action=edit&id=" . $_POST['id'] . "&do=edit", "", 1);
            return false;
        } catch (Exception $e) {
            echo $languageService->get('failed_save') . "<br /><br />" . $e->getMessage();
            redirect("admincenter.php?site=plugin_manager&action=edit&id=" . $_POST['id'] . "&do=edit", "", 5);
            return false;
        }
        return false;
    }
    #Erstellt eine neue Widget-Einstellung END


    
    ###FOOTER#################################
    if (isset($_POST['sortieren'])) {
        $CAPCLASS = new \nexpell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            $sort = $_POST['sort'];
            foreach ($sort as $sortstring) {
                $sorter = explode("-", $sortstring);
                safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET sort='" . $sorter[1] . "' WHERE id='" . $sorter[0] . "' ");
            }
        } else {
            echo $languageService->get('transaction_invalid');
        }
    }

    if (isset($_GET["delete"])) {
        $CAPCLASS = new \nexpell\Captcha();
        if ($CAPCLASS->checkCaptcha(0, $_GET['captcha_hash'])) {
            $id = $_GET['id'];


            safe_query("DELETE FROM settings_plugins_widget WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM settings_plugins_widget_settings WHERE widgetname='" . $_GET['widgetname'] . "'");

            /*safe_query("DELETE FROM plugins_about_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_articles_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_blog_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_calendar_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_clan_rules_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_counter_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_discord_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_facebook_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_files_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_forum_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_gallery_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_history_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_links_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_memberslist_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_messenger_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_newsletter_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_news_manager_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_partners_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_portfolio_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_projectlist_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_search_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_servers_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_socialmedia_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_sponsors_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_squads_memberslist_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_squads_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_startpage_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_streams_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_tiktok_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_twitter_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_usergallery_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_userlist_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM plugins_whoisonline_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");*/

            echo $languageService->get('success_delete');
            redirect("admincenter.php?site=plugin_manager&action=edit&id=" . $id . "&do=edit", "", 1);
        } else {
            echo $languageService->get('failed_delete') . "<br /><br />";
            echo $languageService->get('transaction_invalid');
        }
    }

if ($action == "edit") {
        $id = $_GET['id'];

        $CAPCLASS = new \nexpell\Captcha;
        $CAPCLASS->createTransaction();
        $hash = $CAPCLASS->getHash();

        echo '<div class="card">
        <div class="card-header"><i class="bi bi-puzzle"></i> 
            ' . $languageService->get('plugin_manager') . '
        </div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admincenter.php?site=plugin_manager">' . $languageService->get('plugin_manager') . '</a></li>
            <li class="breadcrumb-item active" aria-current="page">' . $languageService->get('edit_plugin') . '</li>
          </ol>
        </nav>
        <div class="card-body">';

        $ergebnis = safe_query("SELECT * FROM `settings_plugins` WHERE `pluginID`='" . $id . "' LIMIT 1");
        $ds = mysqli_fetch_array($ergebnis);

        $translate = new multiLanguage($lang);
        $translate->detectLanguages($ds['name']);
        $name = $translate->getTextByLanguage($ds['name']);
        $translate->detectLanguages($ds['info']);
        $info = $translate->getTextByLanguage($ds['info']);

        if (@$ds['admin_file'] != '') {

            echo '<div class="mb-3 row">
            <label class="col-md-1 control-label">' . $languageService->get('options') . ':</label>
            <div class="col-md-8">
                <a class="btn btn-primary" data-toggle="tooltip" data-html="true" title="' . $languageService->get('tooltip_7') . ' " href="admincenter.php?site=' . $ds['admin_file'] . '"><i class="bi bi-gear"></i> ' . $name . '</a>

      <a href="admincenter.php?site=plugin_manager&action=widget_add&id=' . $id . '" class="btn btn-primary" type="button"><i class="bi bi-plus-circle"></i> ' . $languageService->get('new_widget') . '</a>

            </div>
        </div>';
        } else {
        }
        echo '<form class="form-horizontal" method="post" id="post" name="post" action="admincenter.php?site=plugin_manager&id=' . $id . '&do=edit" enctype="multipart/form-data" onsubmit="return chkFormular();">';

        echo '<b>' . $languageService->get('plugin_basic_setting') . ':</b>
        <hr>
        <div class="mb-3 row">
            <input type="hidden" name="pid" value="' . $ds['pluginID'] . '" />    
            <label class="col-sm-5 col-form-label" for="name">Plugin ' . $languageService->get('name') . ':<br>
                ' . $languageService->get('multi_language_info_name') . '
            </label>
            <div class="col-sm-7">
                <h4>' . $name . '</h4>
                <span class="text-muted small"><em>
                <input type="name" class="form-control" name="name" value="' . $ds['name'] . '" placeholder="plugin name"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="name">' . $languageService->get('description') . ':<br>
                ' . $languageService->get('multi_language_info_description') . '
            </label>
            <div class="col-sm-7">
                <p style="margin-top: 7px">' . $info . '</p>
                <span class="text-muted small"><em>
                <textarea class="form-control" name="info" rows="10" cols="" style="width: 100%;" placeholder="info">' . $ds['info'] . '</textarea></em></span>
            </div>
        </div>
   
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="author">' . $languageService->get('author') . ':</label>
            <div class="col-sm-7"><span class="text-muted small"><em>
                <input type="name" class="form-control" rows="5" name="author" value="' . $ds['author'] . '" placeholder="autor"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="website">' . $languageService->get('website') . ':</label>
            <div class="col-sm-7"><span class="text-muted small"><em>
                <input type="name" class="form-control" placeholder="http://" rows="5"  value="' . $ds['website'] . '" name="website"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="name">' . $languageService->get('modulname') . ': <br><small>(' . $languageService->get('for_uninstall') . ')</small></label>
            <div class="col-sm-7"><span class="text-muted small"><em>
                <input type="name" class="form-control" name="modulname" value="' . $ds['modulname'] . '" disabled></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="admin_file">' . $languageService->get('admin_file') . ': <br><small>(' . $languageService->get('index_file_nophp') . ')</small></label>
            <div class="col-sm-7"><span class="text-muted small"><em>
                <input type="name" class="form-control"  name="admin_file" value="' . $ds['admin_file'] . '" placeholder="admin file"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="index">' . $languageService->get('index_file') . ': <br><small>(' . $languageService->get('index_file_nophp') . ')</small></label>
            <div class="col-sm-7"><span class="text-muted small"><em>
                <input type="name" class="form-control" placeholder="index file" rows="5"  value="' . $ds['index_link'] . '" name="index"></em></span>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="hittenfiles">' . $languageService->get('hidden_file') . ': <br><small>(' . $languageService->get('hidden_file_seperate') . ')</small></label>
            <div class="col-sm-7"><span class="text-muted small"><em>
                <input type="name" class="form-control" rows="5" placeholder="myfile,secondfile,anotherfile" value="' . $ds['hiddenfiles'] . '" name="hiddenfiles"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="version">' . $languageService->get('version_file') . ':</label>
            <div class="col-sm-7"><span class="text-muted small"><em>
                <input type="name" class="form-control" rows="5" value="' . $ds['version'] . '" name="version" placeholder="version"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="path">' . $languageService->get('folder_file') . ': <br><small>(' . $languageService->get('folder_file_slash') . ')</small></label>
            <div class="col-sm-7"><span class="text-muted small"><em>
                <input type="name" class="form-control" placeholder="includes/plugins/myplugin/"  value="' . $ds['path'] . '" rows="5" name="path"></em></span>
            </div>
        </div>
<hr>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="path">Widgets: <br><small>(' . $languageService->get('widget_included_with_plugin') . ')</small></label>  
            ';

        $widgetsergebnis = safe_query("SELECT * FROM settings_widgets WHERE plugin = '" . $ds['modulname'] . "'");
        $widget = '';
        while ($df = mysqli_fetch_array($widgetsergebnis)) {
    $widgetdatei = $df['widget_key'];
    $widgetname = $df['title'];
    $modulname = $df['plugin'];

    $widget .= '
    <div class="col-sm-12">
        <div class="mb-3 row">
            <div class="col-sm-5 text-end">
                <button type="button"
                    class="btn btn-info"
                    data-bs-toggle="popover"
                    data-bs-placement="left"
                    data-bs-html="true"
                    data-bs-content="<img src=\'../includes/plugins/' . $modulname . '/images/' . $widgetdatei . '.jpg\' class=\'img-fluid\'>"
                    title="Widget">
                    <i class="bi bi-image"></i> ' . $languageService->get('preview_widget') . '
                </button>
            </div>
            <div class="col-sm-4">
                <div class="form-control">' . $widgetname . '</div>
            </div>
            <div class="col-sm-3">
                <a href="admincenter.php?site=plugin_manager&action=edit_widget&id=' . $id . '&widgetname=' . urlencode($widgetname) . '"
                   class="btn btn-warning">
                   <i class="bi bi-pencil-square"></i> ' . $languageService->get('edit_widget') . '
                </a>
                <button type="button"
                    class="btn btn-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#confirmDeleteModal"
                    data-href="admincenter.php?site=plugin_manager&delete=true&widgetname=' . urlencode($widgetname) . '&id=' . $id . '&captcha_hash=' . $hash . '"
                    title="' . $languageService->get('tooltip_6') . '">
                    <i class="bi bi-trash3"></i> ' . $languageService->get('widget_delete') . '
                </button>

                <!-- Modal -->
                <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                    
                      <div class="modal-header">
                        <h5 class="modal-title" id="confirmDeleteModalLabel">' . $languageService->get('widget_delete') . '</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' . $languageService->get('close') . '"></button>
                      </div>
                      
                      <div class="modal-body">
                        ' . $languageService->get('really_delete') . '
                      </div>
                      
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                          <i class="bi bi-x-square"></i> ' . $languageService->get('close') . '
                        </button>
                        <a class="btn btn-danger btn-ok">
                          <i class="bi bi-trash3"></i> ' . $languageService->get('widget_delete') . '
                        </a>
                      </div>
                      
                    </div>
                  </div>
                </div>

                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    var confirmDeleteModal = document.getElementById("confirmDeleteModal");
                    confirmDeleteModal.addEventListener("show.bs.modal", function(event) {
                        var button = event.relatedTarget;
                        var href = button.getAttribute("data-href");
                        var confirmBtn = confirmDeleteModal.querySelector(".btn-ok");
                        confirmBtn.setAttribute("href", href);
                    });
                
                    // Bootstrap Popover initialisieren
                    var popoverTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="popover"]\'));
                    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                        return new bootstrap.Popover(popoverTriggerEl);
                    });
                });
                </script>
            </div>
        </div>
    </div>
';


        }
        if ($ds['modulname'] == @$modulname) {
            $xwidget = $widget;
        } else {
            $xwidget = $languageService->get('no_widget_available');
        }

        echo '' . $xwidget . '
            
        </div>';

        #Plugin-Grundeinstellungen END

        echo '<div class="col-sm-12">
            <div class="mb-3 row">
                <label class="col-sm- col-form-label" for="name"></label>
                <div class="col-sm-6">
                    <input type="hidden" name="captcha_hash" value="' . $hash . '">
                
                <input type="hidden" name="modulname" value="' . $ds['modulname'] . '">
                <input type="hidden" name="id" value="' . $_GET['id'] . '">
                    <button class="btn btn-warning" type="submit" name="saveedit"><i class="bi bi-save"></i> ' . $languageService->get('edit_plugin_widget') . '</button>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>';

        return false;
    } elseif ($action == "widget_add") {

        $id = $_GET['id'];

        $CAPCLASS = new \nexpell\Captcha;
        $CAPCLASS->createTransaction();
        $hash = $CAPCLASS->getHash();

        echo '<div class="card">
        <div class="card-header"><i class="bi bi-puzzle"></i> 
            ' . $languageService->get('plugin_manager') . '
        </div>
            
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admincenter.php?site=plugin_manager">' . $languageService->get('plugin_manager') . '</a></li>
            <li class="breadcrumb-item active" aria-current="page">' . $languageService->get('add_widget') . '</li>
          </ol>
        </nav>


        <div class="card-body">';

        $ergebnis = safe_query("SELECT * FROM settings_plugins WHERE pluginID = '" . $id . "'");
        $db = mysqli_fetch_array($ergebnis);
        echo '<div class="mb-12 row">
    <label class="col-md-1 control-label"><h4><i class="bi bi-plugin"></i> Plugin:</h4></label>
    <div class="col-md-3"><div class="alert alert-info" role="alert" style="padding: 0px 5px">
<h4>' . $db['modulname'] . '</h4></div>
    </div>
  </div>
<hr>';
        $ergebnis = safe_query("SELECT * FROM settings_plugins_widget WHERE modulname='" . $db['modulname'] . "'");
        $ds = mysqli_fetch_array($ergebnis);

        $widget_alle = '<option value="">' . $languageService->get('no_area') . '</option>
<option value="1">Header</option>
<option value="2">Navigation</option>
<option value="3">Content Head & Content Foot</option>
<option value="4">Sidebar Rechts / Links</option>
<option value="6">Footer</option>';

        $widget = str_replace('value=""', 'value="" selected="selected"', $widget_alle);
        echo '<form class="form-horizontal" method="post" id="post" name="post" action="admincenter.php?site=plugin_manager" onsubmit="return chkFormular();" enctype="multipart/form-data">
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="name">' . $languageService->get('widget_name') . ':<font color="#DD0000">*</font> <br><small>(' . $languageService->get('for_widgetname') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="text"" class="form-control" name="widgetname" placeholder="' . $languageService->get('widget_name') . '"></em></span>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="name">' . $languageService->get('modulname') . ': <br><small>(' . $languageService->get('for_plugin') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="name" class="form-control" name="modulname" value="' . $db['modulname'] . '" disabled></em></span>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="admin_file">' . $languageService->get('widget_datei') . ': <br><small>(' . $languageService->get('widgetdatei_nophp') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="name" class="form-control" name="widgetdatei" placeholder="' . $languageService->get('widgetdatei_nophp') . '"></em></span>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="admin_file">' . $languageService->get('area') . ': <br><small>(' . $languageService->get('area_info') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                                    <select id="area" name="area" class="form-select">' . $widget . '</select></em></span>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="mb-3 row">
                <div class="col-sm-11">
                    <font color="#DD0000">*</font>' . $languageService->get('fields_star_required') . '
                </div>
                <div class="col-sm-11">

                    <input type="hidden" name="modulname" value="' . $db['modulname'] . '" />
                    <input type="hidden" name="id" value="' . $_GET['id'] . '" />
                    <button class="btn btn-success" type="submit" name="widget_add"  /><i class="bi bi-plus-circle"></i> ' . $languageService->get('add_widget') . '</button>

                </div>
            </div>
        </div>';

        echo '</form></div></div>';
    } elseif ($action == "edit_widget") {

        $id = $_GET['id'];
        $widgetname = $_GET['widgetname'];

        $CAPCLASS = new \nexpell\Captcha;
        $CAPCLASS->createTransaction();
        $hash = $CAPCLASS->getHash();

        echo '<div class="card">
        <div class="card-header"><i class="bi bi-puzzle"></i> 
            ' . $languageService->get('plugin_manager') . '
        </div>
            
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admincenter.php?site=plugin_manager">' . $languageService->get('plugin_manager') . '</a></li>
            <li class="breadcrumb-item active" aria-current="page"><i class="bi bi-pencil-square"></i> ' . $languageService->get('edit_widget') . '</li>
          </ol>
        </nav>
        <div class="card-body">';

        $ergebnis = safe_query("SELECT * FROM settings_plugins WHERE pluginID = '" . $id . "'");
        $db = mysqli_fetch_array($ergebnis);
        echo '<div class="mb-12 row">
    <label class="col-md-1 control-label"><h4><i class="bi bi-plugin"></i> Plugin:</h4></label>
    <div class="col-md-3"><div class="alert alert-info" role="alert" style="padding: 0px 5px">
<h4>' . $db['modulname'] . '</h4></div>
    </div>
  </div>
<hr>';

        $ergebnis = safe_query("SELECT * FROM settings_plugins_widget WHERE widgetname='" . $_GET['widgetname'] . "'");
        $ds = mysqli_fetch_array($ergebnis);

        $widget_alle = '<option value="">' . $languageService->get('no_area') . '</option>
<option value="1">Header</option>
<option value="2">Navigation</option>
<option value="3">Content Head & Content Foot</option>
<option value="4">Sidebar Rechts / Links</option>
<option value="6">Footer</option>';

        $widget = str_replace('value="' . $ds['area'] . '"', 'value="' . $ds['area'] . '" selected="selected"', $widget_alle);


        echo '<form class="form-horizontal" method="post" id="post" name="post" action="admincenter.php?site=plugin_manager" onsubmit="return chkFormular();" enctype="multipart/form-data">
       
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="name">' . $languageService->get('widget_name') . ':<font color="#DD0000">*</font> <br><small>(' . $languageService->get('for_widgetname') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="text"" class="form-control" name="widgetname" value="' . $ds['widgetname'] . '" placeholder="widget name"></em></span>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="name">' . $languageService->get('modulname') . ': <br><small>(' . $languageService->get('for_plugin') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="name" class="form-control" name="modulname" value="' . $db['modulname'] . '" disabled></em></span>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="admin_file">' . $languageService->get('widget_datei') . ': <br><small>(' . $languageService->get('widgetdatei_nophp') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="name" class="form-control" name="widgetdatei" value="' . $ds['widgetdatei'] . '" placeholder="widget datei"></em></span>
            </div>
        </div>

        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="admin_file">' . $languageService->get('area') . ': <br><small>(' . $languageService->get('area_info') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                                    <select id="area" name="area" class="form-select">' . $widget . '</select></em></span>
            </div>
        </div>
       <div class="col-sm-12">
            <div class="mb-3 row">
                <div class="col-sm-11">
                    <font color="#DD0000">*</font>' . $languageService->get('fields_star_required') . '
                </div>
                <div class="col-sm-11">
                    <input type="hidden" name="modulname" value="' . $db['modulname'] . '" />
                    <input type="hidden" name="xid" value="' . $_GET['id'] . '" />

                    <input type="hidden" name="id" value="' . $ds['id'] . '" />
                    <button class="btn btn-warning" type="submit" name="edit_widget"  /><i class="bi bi-pencil-square"></i> ' . $languageService->get('edit_widget') . '</button>
                </div>
            </div>
        </div>
';
        echo '</form></div></div>';
    } elseif ($action == "new") {
?><script>
            <!--
            function chkFormular() {
                if (document.getElementById('name').value == "") {
                    alert('<? echo $languageService->get('no_plugin_name'); ?>');
                    document.getElementById('name').focus();
                    return false;
                }

                if (document.getElementById('modulname').value == "") {
                    alert('<? echo $languageService->get('no_modul_name'); ?>');
                    document.getElementById('modulname').focus();
                    return false;
                }

            }
            -->
        </script><?php


                    $themeergebnis = safe_query("SELECT * FROM settings_themes WHERE active = '1'");
                    $db = mysqli_fetch_array($themeergebnis);


                    echo '<div class="card">
        <div class="card-header"><i class="bi bi-puzzle"></i> 
            ' . $languageService->get('plugin_manager') . '
        </div>
            
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admincenter.php?site=plugin_manager">' . $languageService->get('plugin_manager') . '</a></li>
                <li class="breadcrumb-item active" aria-current="page">' . $languageService->get('add_plugin') . '</li>
            </ol>
        </nav>
        <div class="card-body">';

                    echo '<form class="form-horizontal" method="post" id="post" name="post" action="admincenter.php?site=plugin_manager" onsubmit="return chkFormular();" enctype="multipart/form-data">
       <form class="form-horizontal" method="post" id="post" name="post" action="admincenter.php?site=plugin_manager" enctype="multipart/form-data" onsubmit="return chkFormular();"> 
  

        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="name">' . $languageService->get('name') . ':<font color="#DD0000">*</font></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="text" name="name" id="name" placeholder="plugin name" maxlength="30" autocomplete="name" class="form-control"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="name">' . $languageService->get('description') . ':</label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <textarea class="form-control" name="info" rows="5" cols="" style="width: 100%;" placeholder="info"></textarea></em></span>
            </div>
        </div>
  
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="author">' . $languageService->get('author') . ':</label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="name" class="form-control" rows="5" name="author" placeholder="author"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="website">' . $languageService->get('website') . ':</label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="name" class="form-control" placeholder="http://" rows="5" name="website"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="name">' . $languageService->get('modulname') . ': <font color="#DD0000">*</font> <br><small>(' . $languageService->get('for_uninstall') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="text" name="modulname" id="modulname" placeholder="modulname" maxlength="30" autocomplete="modulname" class="form-control"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="admin_file">' . $languageService->get('admin_file') . ': <br><small>(' . $languageService->get('index_file_nophp') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="name" class="form-control" name="admin_file" placeholder="admin_file"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="index">' . $languageService->get('index_file') . ': <br><small>(' . $languageService->get('index_file_nophp') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="name" class="form-control" placeholder="index file" rows="5" name="index"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="hittenfiles">' . $languageService->get('hidden_file') . ': <br><small>(' . $languageService->get('hidden_file_seperate') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="name" class="form-control" rows="5" placeholder="myfile,secondfile,anotherfile" name="hiddenfiles"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="version">' . $languageService->get('version_file') . ':</label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="name" class="form-control" rows="5" name="version" placeholder="version"></em></span>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-sm-5 col-form-label" for="path">' . $languageService->get('folder_file') . ':  <font color="#DD0000">*</font> <br><small>(' . $languageService->get('folder_file_slash') . ')</small></label>
            <div class="col-sm-6"><span class="text-muted small"><em>
                <input type="name" class="form-control" placeholder="includes/plugins/myplugin/" rows="5" name="path"></em></span>
            </div>
        </div>

        <div class="col-sm-12">
            <div class="mb-3 row">
                <div class="col-sm-11">
                    <font color="#DD0000">*</font>' . $languageService->get('fields_star_required') . '
                </div>
                <div class="col-sm-11">
                    <input type="hidden" name="themes_modulname" value="' . $db['modulname'] . '" />
                    <button class="btn btn-success" type="submit" name="svn"  /><i class="bi bi-save"></i> ' . $languageService->get('save_plugin') . '</button>
                </div>
            </div>
        </div>

        </form>
    </div>
</div>';
                    return false;
                    echo '</div></div>';
} else {
                    echo '<div class="card">
        <div class="card-header"><i class="bi bi-puzzle"></i> 
            ' . $languageService->get('plugin_manager') . '
        </div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admincenter.php?site=plugin_manager">' . $languageService->get('plugin_manager') . '</a></li>
            <li class="breadcrumb-item active" aria-current="page">new & edit</li>
          </ol>
        </nav>
        <div class="card-body">
        <div class="mb-3 row">
    <label class="col-md-1 control-label">' . $languageService->get('options') . ':</label>
    <div class="col-md-8">

      <a href="admincenter.php?site=plugin_manager&action=new" class="btn btn-primary" type="button"><i class="bi bi-plus-circle"></i> ' . $languageService->get('new_plugin') . '</a>

    </div>
  </div>';
                    $thergebnis = safe_query("SELECT * FROM settings_themes WHERE active = '1'");
                    $db = mysqli_fetch_array($thergebnis);

                    $CAPCLASS = new \nexpell\Captcha;
                    $CAPCLASS->createTransaction();
                    $hash = $CAPCLASS->getHash();

                    echo '<table id="plugini" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <th><strong>' . $languageService->get('id') . '</strong></th>
            <th width="10%"><strong>' . $languageService->get('plugin') . ' ' . $languageService->get('name') . '</strong></th>
            <th><strong>' . $languageService->get('plugin') . ' ' . $languageService->get('description') . '</strong></th>
            <th class="text-center" width="12%"><strong>' . $languageService->get('plugin_status') . '</strong></th>
            <th class="text-center" width="12%"><strong>' . $languageService->get('plugin_setting') . '</strong></th>
            <th class="text-center" width="12%"><strong>' . $languageService->get('action') . '</strong></th>

        </thead>';
                    $ergebnis = safe_query("SELECT * FROM settings_plugins");
                    while ($ds = mysqli_fetch_array($ergebnis)) {

                        $dx = mysqli_fetch_array(safe_query("SELECT * FROM settings_plugins WHERE pluginID='" . $ds['pluginID'] . "'"));

                        if ($ds['activate'] == "1") {
                            $actions = '<div class="d-grid gap-2"><a href="admincenter.php?site=plugin_manager&id=' . $ds['pluginID'] . '&modulname=' . $ds['modulname'] . '&do=dea" class="btn btn-info" data-toggle="tooltip" data-html="true" title="' . $languageService->get('tooltip_2') . ' " type="button"><i class="bi bi-toggle-off"></i> ' . $languageService->get('deactivate') . '</a></div>';
                        } else {
                            $actions = '<div class="d-grid gap-2"><a href="admincenter.php?site=plugin_manager&id=' . $ds['pluginID'] . '&modulname=' . $ds['modulname'] . '&do=act" class="btn btn-success" data-toggle="tooltip" data-html="true" title="' . $languageService->get('tooltip_1') . ' " type="button"><i class="bi bi-toggle-on"></i> ' . $languageService->get('activate') . '</a></div>';
                        }

                        $translate = new multiLanguage($lang);
                        $translate->detectLanguages($ds['name']);
                        $ds['name'] = $translate->getTextByLanguage($ds['name']);
                        $translate->detectLanguages($ds['info']);
                        $ds['info'] = $translate->getTextByLanguage($ds['info']);

                        echo '<tr>
                    <td>' . $ds['pluginID'] . '</td>
                    <td><b>' . $ds['name'] . '</b></td>
                    <td>' . $ds['info'] . '</td>';


                        if ($dx['status_display'] == "1") {
                            echo '<td class="text-center">' . $actions . '</div>';
                        } else {

                            echo '<td class="text-center">
                                <div class="d-grid gap-2">
                            <button type="button" class="btn btn-danger" disabled><i class="bi bi-slash-circle"></i> ' . $languageService->get('status_cannot_assigned') . '</button>
                                 </div></td>';
                        }
                        if ($dx['plugin_display'] == "1") {
                            echo '
                    <td class="text-center">
                    <div class="d-grid gap-2">
                    <a href="admincenter.php?site=plugin_manager&action=edit&id=' . $ds['pluginID'] . '&do=edit" class="btn btn-warning" data-toggle="tooltip" data-html="true" title="' . $languageService->get('tooltip_4') . '" type="button"><i class="bi bi-pencil-square"></i> ' . $languageService->get('edit') . '</a></div></td>';
                        } else {

                            echo '<td class="text-center">
                            <div class="d-grid gap-2">
                        <button type="button" class="btn btn-danger" disabled><i class="bi bi-slash-circle"></i> ' . $languageService->get('plugin_cannot_assigned') . '</button>
                        </div></td>';
                        }


                        


                        if ($dx['delete_display'] != "1") {

                            echo '<td class="text-center">
                            <div class="d-grid gap-2">
                            <button type="button" class="btn btn-danger" disabled><i class="bi bi-slash-circle"></i> ' . $languageService->get('delete_cannot_assigned') . '</button>
                            </div></td>';
                        } else {

                           echo '
                            <td class="text-center">
                                <div class="d-grid gap-2">
                                    <a href="#"
                                       class="btn btn-danger"
                                       data-bs-toggle="modal"
                                       data-bs-target="#confirmDeleteModal"
                                       data-plugin-id="' . $ds['pluginID'] . '"
                                       data-plugin-name="' . $ds['modulname'] . '"
                                       title="' . $languageService->get('tooltip_8') . '">
                                       <i class="bi bi-trash3"></i> ' . $languageService->get('delete_plugin') . '
                                    </a>
                                </div>
                            </td>

                            <!-- Bootstrap Modal for Confirm Delete -->
                            <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                    
                                        <div class="modal-header">
                                            <h5 class="modal-title">' . $languageService->get('modulname') . ': 
                                                <span id="modalPluginTitle"></span>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        
                                        <div class="modal-body">
                                            ' . $languageService->get('really_delete_plugin') . '
                                        </div>
                                        
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                <i class="bi bi-x-square"></i> ' . $languageService->get('close') . '
                                            </button>
                                            <a id="confirmDeleteBtn" href="#" class="btn btn-danger">
                                                <i class="bi bi-trash3"></i> ' . $languageService->get('delete') . '
                                            </a>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>

                            <script>
                            document.querySelectorAll("a[data-bs-target=\'#confirmDeleteModal\']").forEach(button => {
                                button.addEventListener("click", function() {
                                    var pluginID = this.getAttribute("data-plugin-id");
                                    var pluginName = this.getAttribute("data-plugin-name");

                                    // Format plugin name
                                    function formatPluginName(name) {
                                        name = name.replace(/_/g, " ");
                                        name = name.replace(/([a-z])([A-Z])/g, "$1 $2");
                                        return name.replace(/\\b\\w/g, char => char.toUpperCase());
                                    }
                                    var formattedName = formatPluginName(pluginName);

                                    // Set formatted name into the modal title
                                    document.getElementById("modalPluginTitle").innerText = formattedName;

                                    // Compose final delete URL
                                    var deleteUrl = "admincenter.php?site=plugin_manager&action=delete_plugin&id="
                                        + pluginID
                                        + "&modulname=" + pluginName
                                        + "&do=delete";

                                    // Set href of confirm delete button
                                    document.getElementById("confirmDeleteBtn").setAttribute("href", deleteUrl);
                                });
                            });
                            </script>
                            ';
                        }

                        echo '</tr>';
                    }

                    echo '</table></div></div></div>';
                }
            } else {

                echo '<style type="text/css">
     p.test {
        font-family: Georgia, serif;
        font-size: 78px;
        font-style: italic;
    }
    .titlehead {
        border: 3px solid;
        border-color: #c4183c; 
        background-color: #fff}
    </style>
    <div class="card">
        <div class="card-body">
            <div class="titlehead"><br>
                <center>
                    <div>
                        <img class="img-fluid" src="/images/install-logo.jpg" alt="" style="height: 150px"/><br>
                          <small>Ohje !</small><br>
                          <p class="test">404 Error.</p><br>
                          ' . $languageService->get('info') . '
                    </div>
                    <br />
                    <p><a class="btn btn-warning" href="/admin/admincenter.php?site=settings_templates">' . $languageService->get('activate_template') . '</a></p>
                    <br />
                </center>
            </div>
        </div>
    </div>';
            }
?>