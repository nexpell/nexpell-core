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
$lang = $languageService->detectLanguage();
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('plugin_manager', true);

use webspell\AccessControl;
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


    #edit Widget-Einstellung START
    if (isset($_POST['edit_widget'])) {

        #@$modulname = $_POST[ 'modulname' ];
        # $themes_modulname = $_POST[ 'themes_modulname' ];
        #echo "<pre>";
        #print_r($_POST);
        #echo "</pre>";

        try {

            $modul = safe_query("UPDATE `settings_plugins_widget` SET 
      `modulname` = '" . $_POST['modulname'] . "',
      `widgetname` = '" . $_POST['widgetname'] . "',       
      `widgetdatei` = '" . $_POST['widgetdatei'] . "', 
      `area` = '" . $_POST['area'] . "'

      WHERE `id` = '" . intval($_POST['id']) . "'");

            echo $languageService->get('success_edit') . "<br /><br />";
            redirect("admincenter.php?site=plugin_manager&action=edit&id=" . $_POST['xid'] . "&do=edit", "", 1);
            return false;
        } catch (Exception $e) {
            echo $languageService->get('failed_edit') . "<br /><br />" . $e->getMessage();
            redirect("admincenter.php?site=plugin_manager&action=edit&id=" . $_POST['xid'] . "&do=edit", "", 5);
            return false;
        }
        return false;
    }
    #edit Widget-Einstellung END

    #Editiert die komplette Einstellung START
    if (isset($_POST['saveedit'])) {
        @$modulname = $_POST['modulname'];
        $themes_modulname = $_POST['themes_modulname'];
        #echo "<pre>";
        #print_r($_POST);
        #echo "</pre>";

        try {

            $ergebnis = safe_query("SELECT * FROM `settings_plugins` WHERE `pluginID`='" . $id . "' LIMIT 1");
            $ds = mysqli_fetch_array($ergebnis);

            $modul = safe_query("UPDATE `settings_plugins` SET 
            `name` = '" . $_POST['name'] . "',
            `info` = '" . $_POST['info'] . "',       
            `admin_file` = '" . $_POST['admin_file'] . "', 
            `author` = '" . $_POST['author'] . "', 
            `website` = '" . $_POST['website'] . "', 
            `index_link` = '" . $_POST['index'] . "',
            `version` = '" . $_POST['version'] . "', 
            `path` = '" . $_POST['path'] . "'
	        
            WHERE `pluginID` = '" . intval($_POST['pid']) . "'");

            echo $languageService->get('success_edit') . "<br /><br />";
            redirect("admincenter.php?site=plugin_manager", "", 1);
            return false;
        } catch (Exception $e) {
            echo $languageService->get('failed_edit') . "<br /><br />" . $e->getMessage();
            redirect("admincenter.php?site=plugin_manager", "", 5);
            return false;
        }
        return false;
    }

    ###HEADER#################################
    if (isset($_POST['header_deactivated'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['header_on'])) {
                foreach ($_POST['header_on'] as $k => $v) {
                    if ($v == "header_widget") {
                        safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET position = 'header_widget' where widgetname = '" . $k . "'");
                    } else if ($v == "deactivated") {
                        safe_query("DELETE FROM plugins_" . $_POST['modulname'] . "_settings_widgets WHERE widgetname = '" . $k . "'");
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }

    if (isset($_POST['header_activ'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['header_off_1'])) {
                foreach ($_POST['header_off_1'] as $k => $v) {
                    if ($v == "header_widget") {
                        safe_query(
                            "INSERT INTO
                        `plugins_" . $_POST['modulname'] . "_settings_widgets` (                        
                        `position`, 
                        `modulname`, 
                        `themes_modulname`,                        
                        `widgetname`, 
                        `widgetdatei`, 
                        `activated`, 
                        `sort`
                        ) VALUES (
                        '" . $v . "',
                        '',
                        '" . $_POST['themes_modulname'] . "',                        
                        '" . $k . "', 
                        '', 
                        '1', 
                        '1'
                        )"
                        );

                        foreach ($_POST['header_off_2'] as $k => $a) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET widgetdatei = '" . $a . "' WHERE widgetname='" . $k . "' ");
                        }
                        foreach ($_POST['header_off_3'] as $k => $d) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET modulname = '" . $d . "' WHERE widgetname='" . $k . "' ");
                        }
                    } elseif ($v == "") {
                        $name = $k;
                        DeleteData("plugins_" . $_POST['modulname'] . "_settings_widgets", "modulname", $name);
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }
    ###HEADER#################################

    ###NAVIGATION#################################
    if (isset($_POST['navigation_deactivated'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['navigation_on'])) {
                foreach ($_POST['navigation_on'] as $k => $v) {
                    if ($v == "navigation_widget") {
                        safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET position = 'navigation_widget' where widgetname = '" . $k . "'");
                    } else if ($v == "deactivated") {
                        safe_query("DELETE FROM plugins_" . $_POST['modulname'] . "_settings_widgets WHERE widgetname = '" . $k . "'");
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }

    if (isset($_POST['navigation_activ'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['navigation_off_1'])) {
                foreach ($_POST['navigation_off_1'] as $k => $v) {
                    if ($v == "navigation_widget") {
                        safe_query(
                            "INSERT INTO
                        `plugins_" . $_POST['modulname'] . "_settings_widgets` (                        
                        `position`, 
                        `modulname`, 
                        `themes_modulname`,                        
                        `widgetname`, 
                        `widgetdatei`, 
                        `activated`, 
                        `sort`
                        ) VALUES (
                        '" . $v . "',
                        '',
                        '" . $_POST['themes_modulname'] . "',                        
                        '" . $k . "', 
                        '', 
                        '1', 
                        '1'
                        )"
                        );

                        foreach ($_POST['navigation_off_2'] as $k => $a) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET widgetdatei = '" . $a . "' WHERE widgetname='" . $k . "' ");
                        }
                        foreach ($_POST['navigation_off_3'] as $k => $d) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET modulname = '" . $d . "' WHERE widgetname='" . $k . "' ");
                        }
                    } elseif ($v == "") {
                        $name = $k;
                        DeleteData("plugins_" . $_POST['modulname'] . "_settings_widgets", "modulname", $name);
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }
    ###NAVIGATION#################################

    ###content_head#################################
    if (isset($_POST['content_head_deactivated'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['content_head_on'])) {
                foreach ($_POST['content_head_on'] as $k => $v) {
                    if ($v == "content_head_widget") {
                        safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET position = 'content_head_widget' where widgetname = '" . $k . "'");
                    } else if ($v == "deactivated") {
                        safe_query("DELETE FROM plugins_" . $_POST['modulname'] . "_settings_widgets WHERE widgetname = '" . $k . "'");
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }

    if (isset($_POST['content_head_activ'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['content_head_off_1'])) {
                foreach ($_POST['content_head_off_1'] as $k => $v) {
                    if ($v == "content_head_widget") {
                        safe_query(
                            "INSERT INTO
                        `plugins_" . $_POST['modulname'] . "_settings_widgets` (                        
                        `position`, 
                        `modulname`, 
                        `themes_modulname`,                        
                        `widgetname`, 
                        `widgetdatei`, 
                        `activated`, 
                        `sort`
                        ) VALUES (
                        '" . $v . "',
                        '',
                        '" . $_POST['themes_modulname'] . "',                        
                        '" . $k . "', 
                        '', 
                        '1', 
                        '1'
                        )"
                        );

                        foreach ($_POST['content_head_off_2'] as $k => $a) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET widgetdatei = '" . $a . "' WHERE widgetname='" . $k . "' ");
                        }
                        foreach ($_POST['content_head_off_3'] as $k => $d) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET modulname = '" . $d . "' WHERE widgetname='" . $k . "' ");
                        }
                    } elseif ($v == "") {
                        $name = $k;
                        DeleteData("plugins_" . $_POST['modulname'] . "_settings_widgets", "modulname", $name);
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }
    ###content_head#################################

    ###content_up#################################
    if (isset($_POST['content_up_deactivated'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['content_up_on'])) {
                foreach ($_POST['content_up_on'] as $k => $v) {
                    if ($v == "content_up_widget") {
                        safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET position = 'content_up_widget' where widgetname = '" . $k . "'");
                    } else if ($v == "deactivated") {
                        safe_query("DELETE FROM plugins_" . $_POST['modulname'] . "_settings_widgets WHERE widgetname = '" . $k . "'");
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }

    if (isset($_POST['content_up_activ'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['content_up_off_1'])) {
                foreach ($_POST['content_up_off_1'] as $k => $v) {
                    if ($v == "content_up_widget") {
                        safe_query(
                            "INSERT INTO
                        `plugins_" . $_POST['modulname'] . "_settings_widgets` (                        
                        `position`, 
                        `modulname`, 
                        `themes_modulname`,                        
                        `widgetname`, 
                        `widgetdatei`, 
                        `activated`, 
                        `sort`
                        ) VALUES (
                        '" . $v . "',
                        '',
                        '" . $_POST['themes_modulname'] . "',                        
                        '" . $k . "', 
                        '', 
                        '1', 
                        '1'
                        )"
                        );

                        foreach ($_POST['content_up_off_2'] as $k => $a) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET widgetdatei = '" . $a . "' WHERE widgetname='" . $k . "' ");
                        }
                        foreach ($_POST['content_up_off_3'] as $k => $d) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET modulname = '" . $d . "' WHERE widgetname='" . $k . "' ");
                        }
                    } elseif ($v == "") {
                        $name = $k;
                        DeleteData("plugins_" . $_POST['modulname'] . "_settings_widgets", "modulname", $name);
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }
    ###content_up#################################

    ###content_down#################################
    if (isset($_POST['content_down_deactivated'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['content_down_on'])) {
                foreach ($_POST['content_down_on'] as $k => $v) {
                    if ($v == "content_down_widget") {
                        safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET position = 'content_down_widget' where widgetname = '" . $k . "'");
                    } else if ($v == "deactivated") {
                        safe_query("DELETE FROM plugins_" . $_POST['modulname'] . "_settings_widgets WHERE widgetname = '" . $k . "'");
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }

    if (isset($_POST['content_down_activ'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['content_down_off_1'])) {
                foreach ($_POST['content_down_off_1'] as $k => $v) {
                    if ($v == "content_down_widget") {
                        safe_query(
                            "INSERT INTO
                        `plugins_" . $_POST['modulname'] . "_settings_widgets` (                        
                        `position`, 
                        `modulname`, 
                        `themes_modulname`,                        
                        `widgetname`, 
                        `widgetdatei`, 
                        `activated`, 
                        `sort`
                        ) VALUES (
                        '" . $v . "',
                        '',
                        '" . $_POST['themes_modulname'] . "',                        
                        '" . $k . "', 
                        '', 
                        '1', 
                        '1'
                        )"
                        );

                        foreach ($_POST['content_down_off_2'] as $k => $a) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET widgetdatei = '" . $a . "' WHERE widgetname='" . $k . "' ");
                        }
                        foreach ($_POST['content_down_off_3'] as $k => $d) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET modulname = '" . $d . "' WHERE widgetname='" . $k . "' ");
                        }
                    } elseif ($v == "") {
                        $name = $k;
                        DeleteData("plugins_" . $_POST['modulname'] . "_settings_widgets", "modulname", $name);
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }
    ###content_down#################################

    ###left sidebar#################################
    if (isset($_POST['sidebar_left_deactivated'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['sidebar_left_on'])) {
                foreach ($_POST['sidebar_left_on'] as $k => $v) {
                    if ($v == "left_side_widget") {
                        safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET position = 'left_side_widget' where widgetname = '" . $k . "'");
                    } else if ($v == "deactivated") {
                        safe_query("DELETE FROM plugins_" . $_POST['modulname'] . "_settings_widgets WHERE widgetname = '" . $k . "'");
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }

    if (isset($_POST['sidebar_left_activ'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['sidebar_left_off_1'])) {
                foreach ($_POST['sidebar_left_off_1'] as $k => $v) {
                    if ($v == "left_side_widget") {
                        safe_query(
                            "INSERT INTO
                        `plugins_" . $_POST['modulname'] . "_settings_widgets` (                        
                        `position`, 
                        `modulname`, 
                        `themes_modulname`,                        
                        `widgetname`, 
                        `widgetdatei`, 
                        `activated`, 
                        `sort`
                        ) VALUES (
                        '" . $v . "',
                        '',
                        '" . $_POST['themes_modulname'] . "',                        
                        '" . $k . "', 
                        '', 
                        '1', 
                        '1'
                        )"
                        );

                        foreach ($_POST['sidebar_left_off_2'] as $k => $a) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET widgetdatei = '" . $a . "' WHERE widgetname='" . $k . "' ");
                        }
                        foreach ($_POST['sidebar_left_off_3'] as $k => $d) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET modulname = '" . $d . "' WHERE widgetname='" . $k . "' ");
                        }
                    } elseif ($v == "") {
                        $name = $k;
                        DeleteData("plugins_" . $_POST['modulname'] . "_settings_widgets", "modulname", $name);
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }
    ###left sidebar#################################

    ###right sidebar#################################
    if (isset($_POST['sidebar_right_deactivated'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['sidebar_right_on'])) {
                foreach ($_POST['sidebar_right_on'] as $k => $v) {
                    if ($v == "right_side_widget") {
                        safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET position = 'right_side_widget' where widgetname = '" . $k . "'");
                    } else if ($v == "deactivated") {
                        safe_query("DELETE FROM plugins_" . $_POST['modulname'] . "_settings_widgets WHERE widgetname = '" . $k . "'");
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }

    if (isset($_POST['sidebar_right_activ'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['sidebar_right_off_1'])) {
                foreach ($_POST['sidebar_right_off_1'] as $k => $v) {
                    if ($v == "right_side_widget") {
                        safe_query(
                            "INSERT INTO
                        `plugins_" . $_POST['modulname'] . "_settings_widgets` (                        
                        `position`, 
                        `modulname`, 
                        `themes_modulname`,                        
                        `widgetname`, 
                        `widgetdatei`, 
                        `activated`, 
                        `sort`
                        ) VALUES (
                        '" . $v . "',
                        '',
                        '" . $_POST['themes_modulname'] . "',                        
                        '" . $k . "', 
                        '', 
                        '1', 
                        '1'
                        )"
                        );

                        foreach ($_POST['sidebar_right_off_2'] as $k => $a) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET widgetdatei = '" . $a . "' WHERE widgetname='" . $k . "' ");
                        }
                        foreach ($_POST['sidebar_right_off_3'] as $k => $d) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET modulname = '" . $d . "' WHERE widgetname='" . $k . "' ");
                        }
                    } elseif ($v == "") {
                        $name = $k;
                        DeleteData("plugins_" . $_POST['modulname'] . "_settings_widgets", "modulname", $name);
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }
    ###right sidebar#################################

    ###left right sidebar#################################
    if (isset($_POST['sidebar_left_right_deactivated'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['sidebar_on'])) {
                foreach ($_POST['sidebar_on'] as $k => $v) {
                    if ($v == "left_side_widget") {
                        safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET position = 'left_side_widget' where widgetname = '" . $k . "'");
                    } else if ($v == "right_side_widget") {
                        safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET position = 'right_side_widget' where widgetname = '" . $k . "'");
                    } else if ($v == "deactivated") {
                        safe_query("DELETE FROM plugins_" . $_POST['modulname'] . "_settings_widgets WHERE widgetname = '" . $k . "'");
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }



    if (isset($_POST['sidebar_left_right_activ'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['sidebar_left_off_1'])) {
                foreach ($_POST['sidebar_left_off_1'] as $k => $v) {
                    if ($v == "left_side_widget") {
                        safe_query(
                            "INSERT INTO
                        `plugins_" . $_POST['modulname'] . "_settings_widgets` (                        
                        `position`, 
                        `modulname`, 
                        `themes_modulname`,                        
                        `widgetname`, 
                        `widgetdatei`, 
                        `activated`, 
                        `sort`
                        ) VALUES (
                        '" . $v . "',
                        '',
                        '" . $_POST['themes_modulname'] . "',                        
                        '" . $k . "', 
                        '', 
                        '1', 
                        '1'
                        )"
                        );

                        foreach ($_POST['sidebar_left_off_2'] as $k => $a) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET widgetdatei = '" . $a . "' WHERE widgetname='" . $k . "' ");
                        }
                        foreach ($_POST['sidebar_left_off_3'] as $k => $d) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET modulname = '" . $d . "' WHERE widgetname='" . $k . "' ");
                        }
                    } elseif ($v == "") {
                        $name = $k;
                        DeleteData("plugins_" . $_POST['modulname'] . "_settings_widgets", "modulname", $name);
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }
    ###right sidebar#################################

    ###content_foot#################################
    if (isset($_POST['content_foot_deactivated'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['content_foot_on'])) {
                foreach ($_POST['content_foot_on'] as $k => $v) {
                    if ($v == "content_foot_widget") {
                        safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET position = 'content_foot_widget' where widgetname = '" . $k . "'");
                    } else if ($v == "deactivated") {
                        safe_query("DELETE FROM plugins_" . $_POST['modulname'] . "_settings_widgets WHERE widgetname = '" . $k . "'");
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }

    if (isset($_POST['content_foot_activ'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['content_foot_off_1'])) {
                foreach ($_POST['content_foot_off_1'] as $k => $v) {
                    if ($v == "content_foot_widget") {
                        safe_query(
                            "INSERT INTO
                        `plugins_" . $_POST['modulname'] . "_settings_widgets` (                        
                        `position`, 
                        `modulname`, 
                        `themes_modulname`,                        
                        `widgetname`, 
                        `widgetdatei`, 
                        `activated`, 
                        `sort`
                        ) VALUES (
                        '" . $v . "',
                        '',
                        '" . $_POST['themes_modulname'] . "',                        
                        '" . $k . "', 
                        '', 
                        '1', 
                        '1'
                        )"
                        );

                        foreach ($_POST['content_foot_off_2'] as $k => $a) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET widgetdatei = '" . $a . "' WHERE widgetname='" . $k . "' ");
                        }
                        foreach ($_POST['content_foot_off_3'] as $k => $d) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET modulname = '" . $d . "' WHERE widgetname='" . $k . "' ");
                        }
                    } elseif ($v == "") {
                        $name = $k;
                        DeleteData("plugins_" . $_POST['modulname'] . "_settings_widgets", "modulname", $name);
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }
    ###content_foot#################################

    ###FOOTER#################################
    if (isset($_POST['footer_deactivated'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['footer_on'])) {
                foreach ($_POST['footer_on'] as $k => $v) {
                    if ($v == "footer_widget") {
                        safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET position = 'footer_widget' where widgetname = '" . $k . "'");
                    } else if ($v == "deactivated") {
                        safe_query("DELETE FROM plugins_" . $_POST['modulname'] . "_settings_widgets WHERE widgetname = '" . $k . "'");
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }

    if (isset($_POST['footer_activ'])) {

        $CAPCLASS = new \webspell\Captcha;
        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

            if (isset($_POST['footer_off_1'])) {
                foreach ($_POST['footer_off_1'] as $k => $v) {
                    if ($v == "footer_widget") {
                        safe_query(
                            "INSERT INTO
                        `plugins_" . $_POST['modulname'] . "_settings_widgets` (                        
                        `position`, 
                        `modulname`, 
                        `themes_modulname`,                        
                        `widgetname`, 
                        `widgetdatei`, 
                        `activated`, 
                        `sort`
                        ) VALUES (
                        '" . $v . "',
                        '',
                        '" . $_POST['themes_modulname'] . "',                        
                        '" . $k . "', 
                        '', 
                        '1', 
                        '1'
                        )"
                        );

                        foreach ($_POST['footer_off_2'] as $k => $a) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET widgetdatei = '" . $a . "' WHERE widgetname='" . $k . "' ");
                        }
                        foreach ($_POST['footer_off_3'] as $k => $d) {
                            safe_query("UPDATE plugins_" . $_POST['modulname'] . "_settings_widgets SET modulname = '" . $d . "' WHERE widgetname='" . $k . "' ");
                        }
                    } elseif ($v == "") {
                        $name = $k;
                        DeleteData("plugins_" . $_POST['modulname'] . "_settings_widgets", "modulname", $name);
                    }
                }
            } else {
                print "alle checkbox schlafen schon.\n";
            }

            redirect("admincenter.php?site=plugin_manager&action=widget_edit&id=" . $id . "&do=edit", "", "1");
        } else {
            echo '' . $languageService->get('transaction_invalid') . '';
        }
    }
    ###FOOTER#################################
    if (isset($_POST['sortieren'])) {
        $CAPCLASS = new \webspell\Captcha;
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
        $CAPCLASS = new \webspell\Captcha();
        if ($CAPCLASS->checkCaptcha(0, $_GET['captcha_hash'])) {
            $id = $_GET['id'];


            safe_query("DELETE FROM settings_plugins_widget WHERE widgetname='" . $_GET['widgetname'] . "'");
            safe_query("DELETE FROM settings_plugins_widget_settings WHERE widgetname='" . $_GET['widgetname'] . "'");

            safe_query("DELETE FROM plugins_about_us_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");
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
            safe_query("DELETE FROM plugins_whoisonline_settings_widgets WHERE widgetname='" . $_GET['widgetname'] . "'");

            echo $languageService->get('success_delete');
            redirect("admincenter.php?site=plugin_manager&action=edit&id=" . $id . "&do=edit", "", 1);
        } else {
            echo $languageService->get('failed_delete') . "<br /><br />" . $e->getMessage();
            echo $languageService->get('transaction_invalid');
        }
    }

    if ($action == "widget_edit") {
        $id = $_GET['id'];


        $CAPCLASS = new \webspell\Captcha;
        $CAPCLASS->createTransaction();
        $hash = $CAPCLASS->getHash();

        $CAPCLASS->createTransaction();
        $hash_2 = $CAPCLASS->getHash();

        echo '<div class="card">
        <div class="card-header"><i class="bi bi-puzzle"></i> 
            ' . $languageService->get('plugin_manager') . '
        </div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admincenter.php?site=plugin_manager">' . $languageService->get('plugin_manager') . '</a></li>
            <li class="breadcrumb-item active" aria-current="page">' . $languageService->get('widget_side') . '</li>
          </ol>
        </nav>
        <div class="card-body">';

        $thergebnis = safe_query("SELECT * FROM settings_themes WHERE active = '1'");
        $dx = mysqli_fetch_array($thergebnis);

        $thergebnis = safe_query("SELECT * FROM settings_plugins WHERE pluginID = '" . $id . "'");
        $dw = mysqli_fetch_array($thergebnis);

        $result = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND modulname='" . $dw['modulname'] . "'");
        $ds = mysqli_fetch_array($result);

        $xergebnis = safe_query("SELECT * FROM settings_plugins WHERE name= '" . $dw['name'] . "'");
        while ($db = mysqli_fetch_array($xergebnis)) {

            $plugin_ID = $db['pluginID'];

            echo '<h5>' . $languageService->get('page') . ': <a href="admincenter.php?site=plugin_manager&amp;action=edit&amp;id=' . $plugin_ID . '">' . $db['name'] . '</a></h5>';
        }

        echo '<form class="form-horizontal" method="post">
            <div class="table-responsive">
            <table class="table table-striped table-bordered">              
            <thead>
                <tr>                              
                    <th class="text-bg-secondary p-3" style="width:25%"><i class="bi bi-layout-text-sidebar-reverse"></i> ' . $languageService->get('widget_name') . '</th>
                    <th class="text-bg-secondary p-3"><i class="bi bi-grid-3x2"></i> ' . $languageService->get('area') . '</th>
                </tr>
            </thead>
            <tbody>
            <tr>
                <td><span class="badge border border-success text-black bg-info" style="width: 100%">' . $languageService->get('header1') . '</span><br>';
        $header_plugins_widget = safe_query("SELECT * FROM settings_plugins_widget WHERE area = '1' ORDER BY widgetname ASC");
        $i = 1;
        while ($header_off = mysqli_fetch_array($header_plugins_widget)) {
            $modul_name = $header_off['modulname'];
            $widgetname = $header_off['widgetname'];
            $widgetdatei = $header_off['widgetdatei'];
            $id = $header_off['id'];
            $yheader_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND widgetname='" . $widgetname . "' ORDER BY sort");
            $yheader = mysqli_fetch_array($yheader_ergebnis);
            if (@$yheader['activated'] == '1') {
            } else {
                echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="checkbox" name="header_off_1[' . $widgetname . ']" id="' . $id . '[]" value="header_widget" >
                        <input type="hidden" name="header_off_2[' . $widgetname . ']" id="' . $id . '[]" value="' . $widgetdatei . '" >
                        <input type="hidden" name="header_off_3[' . $widgetname . ']" id="' . $id . '[]" value="' . $modul_name . '" >';
            }
            $i++;
        }
        echo '<br><button class="btn btn-success" style="font-size: 10px;margin-top:10px;" type="submit" name="header_activ"><i class="bi bi-plus-circle"></i> ' . $languageService->get('widget_off_setting') . '</button>
                </td>
                <td class=" text-center"  style="align-content: center;">     
                    ' . $languageService->get('header') . '
                    <div class="alert alert-success text-center" style="padding: 5px"><div class="border border-danger container text-center mt-3 alert alert-secondary">';
        $xheader_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND  position='header_widget' ORDER BY sort");
        $tmp = mysqli_fetch_assoc(safe_query("SELECT count(id) as cnt FROM plugins_" . $dw['modulname'] . "_settings_widgets"));
        $anzheader = $tmp['cnt'];
        $i = 1;
        while ($xheader = mysqli_fetch_array($xheader_ergebnis)) {
            $id = $xheader['id'];
            $widgetname = $xheader['widgetname'];
            $activated = $xheader['activated'];
            $position = $xheader['position'];
            echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="hidden" name="header_on[' . $widgetname . ']" id="' . $id . '[]" value="deactivated"/>
                        <input class="form-check-input test" type="checkbox" name="header_on[' . $widgetname . ']" id="' . $id . '[]" value="header_widget" ';
            if ($activated == '1') {
                echo 'checked';
            }
            echo '><select name="sort[]">';
            for ($j = 1; $j <= $anzheader; $j++) {
                if ($xheader['sort'] == $j) {
                    echo '<option value="' . $xheader['id'] . '-' . $j . '" selected="selected">' . $j . '</option>';
                } else {
                    echo '<option value="' . $xheader['id'] . '-' . $j . '">' . $j . '</option>';
                }
            }
            echo '</select>';
            $i++;
            echo '<br>
                        <input type="hidden" name="captcha_hash" value="' . $hash . '">';
        }
        echo '<button class="btn btn-danger" style="font-size: 10px;margin-top:10px;" type="submit" name="header_deactivated"><i class="bi bi-trash3"></i> ' . $languageService->get('widget_on_setting') . '</>
                    </div></div>
                </td>
            </tr>
            <tr>
                <td><span class="badge border border-success text-black bg-info" style="width: 100%">' . $languageService->get('navigation') . '</span><br>';
        $navigation_plugins_widget = safe_query("SELECT * FROM settings_plugins_widget WHERE area = '2' ORDER BY widgetname ASC");
        $i = 1;
        while ($navigation_off = mysqli_fetch_array($navigation_plugins_widget)) {
            $modul_name = $navigation_off['modulname'];
            $widgetname = $navigation_off['widgetname'];
            $widgetdatei = $navigation_off['widgetdatei'];
            $id = $navigation_off['id'];
            $ynavigation_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND widgetname='" . $widgetname . "'");
            $ynavigation = mysqli_fetch_array($ynavigation_ergebnis);
            if (@$ynavigation['activated'] == '1') {
            } else {
                echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="checkbox" name="navigation_off_1[' . $widgetname . ']" id="' . $id . '[]" value="navigation_widget" >
                        <input type="hidden" name="navigation_off_2[' . $widgetname . ']" id="' . $id . '[]" value="' . $widgetdatei . '" >
                        <input type="hidden" name="navigation_off_3[' . $widgetname . ']" id="' . $id . '[]" value="' . $modul_name . '" >';
            }
            $i++;
        }
        echo '</br><button class="btn btn-success" style="font-size: 10px;margin-top:10px;" type="submit" name="navigation_activ"><i class="bi bi-plus-circle"></i> ' . $languageService->get('widget_off_setting') . '</button>
                </td>
                <td class=" text-center"  style="align-content: center;">
                    ' . $languageService->get('navigation') . '
                    <div class="alert alert-success text-center" style="padding: 5px"><div class="border border-danger container text-center mt-3 alert alert-secondary">';
        $xnavigation_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND  position='navigation_widget' ORDER BY sort");
        $tmp = mysqli_fetch_assoc(safe_query("SELECT count(id) as cnt FROM plugins_" . $dw['modulname'] . "_settings_widgets"));
        $anznavigation = $tmp['cnt'];
        $i = 1;
        while ($xnavigation = mysqli_fetch_array($xnavigation_ergebnis)) {
            $id = $xnavigation['id'];
            $widgetname = $xnavigation['widgetname'];
            $activated = $xnavigation['activated'];
            $position = $xnavigation['position'];
            echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="hidden" name="navigation_on[' . $widgetname . ']" id="' . $id . '[]" value="deactivated"/>
                        <input class="form-check-input test" type="checkbox" name="navigation_on[' . $widgetname . ']" id="' . $id . '[]" value="navigation_widget" ';
            if ($activated == '1') {
                echo 'checked';
            }
            echo '><select name="sort[]">';
            for ($j = 1; $j <= $anznavigation; $j++) {
                if ($xnavigation['sort'] == $j) {
                    echo '<option value="' . $xnavigation['id'] . '-' . $j . '" selected="selected">' . $j . '</option>';
                } else {
                    echo '<option value="' . $xnavigation['id'] . '-' . $j . '">' . $j . '</option>';
                }
            }
            echo '</select>';
            $i++;
            echo '<br>
                        <input type="hidden" name="captcha_hash" value="' . $hash . '">';
        }
        echo '<button class="btn btn-danger" style="font-size: 10px;margin-top:10px;" type="submit" name="navigation_deactivated"><i class="bi bi-trash3"></i> ' . $languageService->get('widget_on_setting') . '</button>
                    </div></div>
                </td>
            </tr>
            <tr>
                <td><span class="badge border border-success text-black bg-info" style="width: 100%">' . $languageService->get('content_head1') . '</span><br>';
        $content_head_plugins_widget = safe_query("SELECT * FROM settings_plugins_widget WHERE area = '3' ORDER BY widgetname ASC");
        $i = 1;
        while ($content_head_off = mysqli_fetch_array($content_head_plugins_widget)) {
            $modul_name = $content_head_off['modulname'];
            $widgetname = $content_head_off['widgetname'];
            $widgetdatei = $content_head_off['widgetdatei'];
            $id = $content_head_off['id'];
            $ycontent_head_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND widgetname='" . $widgetname . "'");
            $ycontent_head = mysqli_fetch_array($ycontent_head_ergebnis);
            if (@$ycontent_head['activated'] == '1') {
            } else {
                echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="checkbox" name="content_head_off_1[' . $widgetname . ']" id="' . $id . '[]" value="content_head_widget" >
                        <input type="hidden" name="content_head_off_2[' . $widgetname . ']" id="' . $id . '[]" value="' . $widgetdatei . '" >
                        <input type="hidden" name="content_head_off_3[' . $widgetname . ']" id="' . $id . '[]" value="' . $modul_name . '" >';
            }
            $i++;
        }
        echo '</br><button class="btn btn-success" style="font-size: 10px;margin-top:10px;" type="submit" name="content_head_activ"><i class="bi bi-plus-circle"></i> ' . $languageService->get('widget_off_setting') . '</button>
                </td>
                <td class=" text-center" style="align-content: center;">
                    ' . $languageService->get('content_head') . '
                    <div class="alert alert-success text-center" style="padding: 5px"><div class="border border-danger container text-center mt-3 alert alert-secondary">';
        $xcontent_head_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND  position='content_head_widget' ORDER BY sort");
        $tmp = mysqli_fetch_assoc(safe_query("SELECT count(id) as cnt FROM plugins_" . $dw['modulname'] . "_settings_widgets"));
        $anzcontent_head = $tmp['cnt'];
        $i = 1;
        while ($xcontent_head = mysqli_fetch_array($xcontent_head_ergebnis)) {
            $id = $xcontent_head['id'];
            $widgetname = $xcontent_head['widgetname'];
            $activated = $xcontent_head['activated'];
            $position = $xcontent_head['position'];
            echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="hidden" name="content_head_on[' . $widgetname . ']" id="' . $id . '[]" value="deactivated"/>
                        <input class="form-check-input test" type="checkbox" name="content_head_on[' . $widgetname . ']" id="' . $id . '[]" value="content_head_widget" ';
            if ($activated == '1') {
                echo 'checked';
            }
            echo '><select name="sort[]">';
            for ($j = 1; $j <= $anzcontent_head; $j++) {
                if ($xcontent_head['sort'] == $j) {
                    echo '<option value="' . $xcontent_head['id'] . '-' . $j . '" selected="selected">' . $j . '</option>';
                } else {
                    echo '<option value="' . $xcontent_head['id'] . '-' . $j . '">' . $j . '</option>';
                }
            }
            echo '</select>';
            $i++;
            echo '<br>
                        <input type="hidden" name="captcha_hash" value="' . $hash . '">';
        }
        echo '<button class="btn btn-danger" style="font-size: 10px;margin-top:10px;" type="submit" name="content_head_deactivated"><i class="bi bi-trash3"></i> ' . $languageService->get('widget_on_setting') . '</button></div></div>
                </td>
            </tr>
			<!-- ################################### Inizio testa alta -->
			<tr>
                <td><span class="badge border border-success text-black bg-info" style="width: 100%">' . $languageService->get('content_up1') . '</span><br>';
        $content_up_plugins_widget = safe_query("SELECT * FROM settings_plugins_widget WHERE area = '3' ORDER BY widgetname ASC");
        $i = 1;
        while ($content_up_off = mysqli_fetch_array($content_up_plugins_widget)) {
            $modul_name = $content_up_off['modulname'];
            $widgetname = $content_up_off['widgetname'];
            $widgetdatei = $content_up_off['widgetdatei'];
            $id = $content_up_off['id'];
            $ycontent_up_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND widgetname='" . $widgetname . "'");
            $ycontent_up = mysqli_fetch_array($ycontent_up_ergebnis);
            if (@$ycontent_up['activated'] == '1') {
            } else {
                echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="checkbox" name="content_up_off_1[' . $widgetname . ']" id="' . $id . '[]" value="content_up_widget" >
                        <input type="hidden" name="content_up_off_2[' . $widgetname . ']" id="' . $id . '[]" value="' . $widgetdatei . '" >
                        <input type="hidden" name="content_up_off_3[' . $widgetname . ']" id="' . $id . '[]" value="' . $modul_name . '" >';
            }
            $i++;
        }
        echo '</br><button class="btn btn-success" style="font-size: 10px;margin-top:10px;" type="submit" name="content_up_activ"><i class="bi bi-plus-circle"></i> ' . $languageService->get('widget_off_setting') . '</button>
                </td>
                <td class=" text-center" style="align-content: end;">
				<div class="container text-center" style="padding-top: 10px">' . $languageService->get('content_up') . '
    <div class="row">
        <div class="col-3 text-start" style="padding: 5px">
            <div class="border border-danger alert alert-success text-center" style="height: 90%; padding: 5px"></div>
			</div>
        <div class="col-6 text-start" style="padding: 5px">
		<div class="border border-danger col-12 text-center border-end alert alert-secondary" style="height: 90%;">';





        $xcontent_up_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND  position='content_up_widget' ORDER BY sort");
        $tmp = mysqli_fetch_assoc(safe_query("SELECT count(id) as cnt FROM plugins_" . $dw['modulname'] . "_settings_widgets"));
        $anzcontent_up = $tmp['cnt'];
        $i = 1;
        while ($xcontent_up = mysqli_fetch_array($xcontent_up_ergebnis)) {
            $id = $xcontent_up['id'];
            $widgetname = $xcontent_up['widgetname'];
            $activated = $xcontent_up['activated'];
            $position = $xcontent_up['position'];
            echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="hidden" name="content_up_on[' . $widgetname . ']" id="' . $id . '[]" value="deactivated"/>
                        <input class="form-check-input test" type="checkbox" name="content_up_on[' . $widgetname . ']" id="' . $id . '[]" value="content_up_widget" ';
            if ($activated == '1') {
                echo 'checked';
            }
            echo '><select name="sort[]">';
            for ($j = 1; $j <= $anzcontent_up; $j++) {
                if ($xcontent_up['sort'] == $j) {
                    echo '<option value="' . $xcontent_up['id'] . '-' . $j . '" selected="selected">' . $j . '</option>';
                } else {
                    echo '<option value="' . $xcontent_up['id'] . '-' . $j . '">' . $j . '</option>';
                }
            }
            echo '</select>';
            $i++;
            echo '<br>
                        <input type="hidden" name="captcha_hash" value="' . $hash . '">';
        }
        echo '<button class="btn btn-danger" style="font-size: 10px;margin-top:10px;" type="submit" name="content_up_deactivated"><i class="bi bi-trash3"></i> ' . $languageService->get('widget_on_setting') . '</button></div></div>
        <div class="col-3 text-end" style="padding: 5px">
            <div class="border border-danger alert alert-success text-center" style="height: 90%; padding: 5px"></div>
        </div>

                </td>
            </tr>
			<!-- ################################### fine testa alta -->
            <tr>
                <td>
                    <span class="badge border border-success text-black bg-info" style="width: 100%">' . $languageService->get('left') . '</span><br>';
        $sidebar_left_plugins_widget = safe_query("SELECT * FROM settings_plugins_widget WHERE area = '4' ORDER BY widgetname ASC");
        $i = 1;
        while ($sidebar_left_off = mysqli_fetch_array($sidebar_left_plugins_widget)) {
            $modul_name = $sidebar_left_off['modulname'];
            $widgetname = $sidebar_left_off['widgetname'];
            $widgetdatei = $sidebar_left_off['widgetdatei'];
            $id = $sidebar_left_off['id'];
            $ysidebar_left_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND widgetname='" . $widgetname . "'");
            $ysidebar_left = mysqli_fetch_array($ysidebar_left_ergebnis);
            if (@$ysidebar_left['activated'] == '1') {
            } else {
                echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="checkbox" name="sidebar_left_off_1[' . $widgetname . ']" id="' . $id . '[]" value="left_side_widget" >
                        <input type="hidden" name="sidebar_left_off_2[' . $widgetname . ']" id="' . $id . '[]" value="' . $widgetdatei . '" >
                        <input type="hidden" name="sidebar_left_off_3[' . $widgetname . ']" id="' . $id . '[]" value="' . $modul_name . '" >';
            }
            $i++;
        }
        echo '</br><button class="btn btn-success" style="font-size: 10px;margin-top:10px;" type="submit" name="sidebar_left_activ"><i class="bi bi-plus-circle"></i> ' . $languageService->get('widget_off_setting') . '</button>
                    <hr>
                    <span class="badge border border-success text-black bg-info" style="width: 100%">' . $languageService->get('right') . '</span><br>';
        $sidebar_right_plugins_widget = safe_query("SELECT * FROM settings_plugins_widget WHERE area = '4' ORDER BY widgetname ASC");
        $i = 1;
        while ($sidebar_right_off = mysqli_fetch_array($sidebar_right_plugins_widget)) {
            $modul_name = $sidebar_right_off['modulname'];
            $widgetname = $sidebar_right_off['widgetname'];
            $widgetdatei = $sidebar_right_off['widgetdatei'];
            $id = $sidebar_right_off['id'];
            $ysidebar_right_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND widgetname='" . $widgetname . "'");
            $ysidebar_right = mysqli_fetch_array($ysidebar_right_ergebnis);
            if (@$ysidebar_right['activated'] == '1') {
                continue;
            } else {
                echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="checkbox" name="sidebar_right_off_1[' . $widgetname . ']" id="' . $id . '[]" value="right_side_widget" >
                        <input type="hidden" name="sidebar_right_off_2[' . $widgetname . ']" id="' . $id . '[]" value="' . $widgetdatei . '" >
                        <input type="hidden" name="sidebar_right_off_3[' . $widgetname . ']" id="' . $id . '[]" value="' . $modul_name . '" >';
            }
            $i++;
        }
        echo '<br><button class="btn btn-success" style="font-size: 10px;margin-top:10px;" type="submit" name="sidebar_right_activ"><i class="bi bi-plus-circle"></i> ' . $languageService->get('widget_off_setting') . '</button>
                </td>
                <td>
                    <div class="col text-center">   
                        ' . $languageService->get('right_left_active') . '
                    </div>
                    <div class="container text-center" style="padding-top: 10px">
                        <div class="row">
                            <div class="col-3 text-start" style="padding: 5px">
                                ' . $languageService->get('left') . '
                                <div class="border border-danger alert alert-success text-center" style="height: 90%;padding: 5px">';
        $xsidebar_left_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND  position='left_side_widget' ORDER BY sort");
        $tmp = mysqli_fetch_assoc(safe_query("SELECT count(id) as cnt FROM plugins_" . $dw['modulname'] . "_settings_widgets"));
        $anzsidebar_left = $tmp['cnt'];
        $i = 1;
        while ($xsidebar_left = mysqli_fetch_array($xsidebar_left_ergebnis)) {
            $id = $xsidebar_left['id'];
            $widgetname = $xsidebar_left['widgetname'];
            $activated = $xsidebar_left['activated'];
            $position = $xsidebar_left['position'];
            echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                                    <input class="form-check-input" type="hidden" name="sidebar_left_on[' . $widgetname . ']" id="' . $id . '[]" value="deactivated"/>
                                    <input class="form-check-input test" type="checkbox" name="sidebar_left_on[' . $widgetname . ']" id="' . $id . '[]" value="left_side_widget" ';
            if ($activated == '1') {
                echo 'checked';
            }
            echo '><select name="sort[]">';
            for ($j = 1; $j <= $anzsidebar_left; $j++) {
                if ($xsidebar_left['sort'] == $j) {
                    echo '<option value="' . $xsidebar_left['id'] . '-' . $j . '" selected="selected">' . $j . '</option>';
                } else {
                    echo '<option value="' . $xsidebar_left['id'] . '-' . $j . '">' . $j . '</option>';
                }
            }
            echo '</select>';
            $i++;
            echo '<br>
                                    <input type="hidden" name="captcha_hash" value="' . $hash . '">';
        }
        echo '<button class="btn btn-danger mb-3" style="font-size: 10px;margin-top:10px;" type="submit" name="sidebar_left_deactivated"><i class="bi bi-trash3"></i> ' . $languageService->get('widget_on_setting') . '</button>
                                </div>
                            </div>
                            <div class="col-6 text-start" style="padding: 5px">
                                ' . $languageService->get('main_area') . '
                                <div class="border border-danger col-12 text-center border-end alert alert-secondary" style="height: 90%;"><h5>' . $languageService->get('main_area') . '</h5></div>
                            </div>
                            <div class="col-3 text-end" style="padding: 5px">
                                ' . $languageService->get('right') . '
                                <div class="border border-danger alert alert-success text-center" style="height: 90%;padding: 5px">';
        $xsidebar_right_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND  position='right_side_widget' ORDER BY sort");
        $tmp = mysqli_fetch_assoc(safe_query("SELECT count(id) as cnt FROM plugins_" . $dw['modulname'] . "_settings_widgets"));
        $anzsidebar_right = $tmp['cnt'];
        $i = 1;
        while ($xsidebar_right = mysqli_fetch_array($xsidebar_right_ergebnis)) {
            $id = $xsidebar_right['id'];
            $widgetname = $xsidebar_right['widgetname'];
            $activated = $xsidebar_right['activated'];
            $position = $xsidebar_right['position'];
            echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                                    <input class="form-check-input" type="hidden" name="sidebar_right_on[' . $widgetname . ']" id="' . $id . '[]" value="deactivated"/>
                                    <input class="form-check-input test" type="checkbox" name="sidebar_right_on[' . $widgetname . ']" id="' . $id . '[]" value="right_side_widget" ';
            if ($activated == '1') {
                echo 'checked';
            }
            echo '><select name="sort[]">';
            for ($j = 1; $j <= $anzsidebar_right; $j++) {
                if ($xsidebar_right['sort'] == $j) {
                    echo '<option value="' . $xsidebar_right['id'] . '-' . $j . '" selected="selected">' . $j . '</option>';
                } else {
                    echo '<option value="' . $xsidebar_right['id'] . '-' . $j . '">' . $j . '</option>';
                }
            }
            echo '</select>';
            $i++;
            echo '<br>
                                    <input type="hidden" name="captcha_hash" value="' . $hash . '">';
        }
        echo '<button class="btn btn-danger mb-3" style="font-size: 10px;margin-top:10px;" type="submit" name="sidebar_right_deactivated"><i class="bi bi-trash3"></i> ' . $languageService->get('widget_on_setting') . '</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            
			<!-- ################################### Inizio testa bassa -->
			<tr>
                <td><span class="badge border border-success text-black bg-info" style="width: 100%">' . $languageService->get('content_down1') . '</span><br>';
        $content_down_plugins_widget = safe_query("SELECT * FROM settings_plugins_widget WHERE area = '3' ORDER BY widgetname ASC");
        $i = 1;
        while ($content_down_off = mysqli_fetch_array($content_down_plugins_widget)) {
            $modul_name = $content_down_off['modulname'];
            $widgetname = $content_down_off['widgetname'];
            $widgetdatei = $content_down_off['widgetdatei'];
            $id = $content_down_off['id'];
            $ycontent_down_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND widgetname='" . $widgetname . "'");
            $ycontent_down = mysqli_fetch_array($ycontent_down_ergebnis);
            if (@$ycontent_down['activated'] == '1') {
            } else {
                echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="checkbox" name="content_down_off_1[' . $widgetname . ']" id="' . $id . '[]" value="content_down_widget" >
                        <input type="hidden" name="content_down_off_2[' . $widgetname . ']" id="' . $id . '[]" value="' . $widgetdatei . '" >
                        <input type="hidden" name="content_down_off_3[' . $widgetname . ']" id="' . $id . '[]" value="' . $modul_name . '" >';
            }
            $i++;
        }
        echo '</br><button class="btn btn-success" style="font-size: 10px;margin-top:10px;" type="submit" name="content_down_activ"><i class="bi bi-plus-circle"></i> ' . $languageService->get('widget_off_setting') . '</button>
                </td>
                <td class=" text-center">
				<div class="container text-center" style="padding-top: 10px">' . $languageService->get('content_down') . '
    <div class="row">
        <div class="col-3 text-start" style="padding: 5px">
            <div class="border border-danger alert alert-success text-center" style="height: 90%; padding: 5px"></div>
			</div>
        <div class="col-6 text-start" style="padding: 5px">
		<div class="border border-danger col-12 text-center border-end alert alert-secondary" style="height: 90%;">
				
				
				
                    
                    ';
        $xcontent_down_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND  position='content_down_widget' ORDER BY sort");
        $tmp = mysqli_fetch_assoc(safe_query("SELECT count(id) as cnt FROM plugins_" . $dw['modulname'] . "_settings_widgets"));
        $anzcontent_down = $tmp['cnt'];
        $i = 1;
        while ($xcontent_down = mysqli_fetch_array($xcontent_down_ergebnis)) {
            $id = $xcontent_down['id'];
            $widgetname = $xcontent_down['widgetname'];
            $activated = $xcontent_down['activated'];
            $position = $xcontent_down['position'];
            echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="hidden" name="content_down_on[' . $widgetname . ']" id="' . $id . '[]" value="deactivated"/>
                        <input class="form-check-input test" type="checkbox" name="content_down_on[' . $widgetname . ']" id="' . $id . '[]" value="content_down_widget" ';
            if ($activated == '1') {
                echo 'checked';
            }
            echo '><select name="sort[]">';
            for ($j = 1; $j <= $anzcontent_down; $j++) {
                if ($xcontent_down['sort'] == $j) {
                    echo '<option value="' . $xcontent_down['id'] . '-' . $j . '" selected="selected">' . $j . '</option>';
                } else {
                    echo '<option value="' . $xcontent_down['id'] . '-' . $j . '">' . $j . '</option>';
                }
            }
            echo '</select>';
            $i++;
            echo '<br>
                        <input type="hidden" name="captcha_hash" value="' . $hash . '">';
        }
        echo '<button class="btn btn-danger" style="font-size: 10px;margin-top:10px;" type="submit" name="content_down_deactivated"><i class="bi bi-trash3"></i> ' . $languageService->get('widget_on_setting') . '</button></div></div>
        <div class="col-3 text-end" style="padding: 5px">
            <div class="border border-danger alert alert-success text-center" style="height: 90%; padding: 5px"></div>
        </div>

                </td>
            </tr><tr>
			<!-- ################################### fine testa bassa -->
                <td><span class="badge border border-success text-black bg-info" style="width: 100%">' . $languageService->get('content_foot1') . '</span><br>';
        $content_foot_plugins_widget = safe_query("SELECT * FROM settings_plugins_widget WHERE area = '3' ORDER BY widgetname ASC");
        $i = 1;
        while ($content_foot_off = mysqli_fetch_array($content_foot_plugins_widget)) {
            $modul_name = $content_foot_off['modulname'];
            $widgetname = $content_foot_off['widgetname'];
            $widgetdatei = $content_foot_off['widgetdatei'];
            $id = $content_foot_off['id'];
            $ycontent_foot_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND widgetname='" . $widgetname . "'");
            $ycontent_foot = mysqli_fetch_array($ycontent_foot_ergebnis);
            if (@$ycontent_foot['activated'] == '1') {
            } else {
                echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="checkbox" name="content_foot_off_1[' . $widgetname . ']" id="' . $id . '[]" value="content_foot_widget" >
                        <input type="hidden" name="content_foot_off_2[' . $widgetname . ']" id="' . $id . '[]" value="' . $widgetdatei . '" >
                        <input type="hidden" name="content_foot_off_3[' . $widgetname . ']" id="' . $id . '[]" value="' . $modul_name . '" >';
            }
            $i++;
        }
        echo '</br><button class="btn btn-success" style="font-size: 10px;margin-top:10px;" type="submit" name="content_foot_activ"><i class="bi bi-plus-circle"></i> ' . $languageService->get('widget_off_setting') . '</button>
                </td>
                <td class=" text-center"  style="align-content: center;">
                    ' . $languageService->get('content_foot') . '
                    <div class="alert alert-success text-center" style="padding: 5px"><div class="border border-danger container text-center mt-3 alert alert-secondary">';
        $xcontent_foot_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND  position='content_foot_widget' ORDER BY sort");
        $tmp = mysqli_fetch_assoc(safe_query("SELECT count(id) as cnt FROM plugins_" . $dw['modulname'] . "_settings_widgets"));
        $anzcontent_foot = $tmp['cnt'];
        $i = 1;
        while ($xcontent_foot = mysqli_fetch_array($xcontent_foot_ergebnis)) {
            $id = $xcontent_foot['id'];
            $widgetname = $xcontent_foot['widgetname'];
            $activated = $xcontent_foot['activated'];
            $position = $xcontent_foot['position'];
            echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="hidden" name="content_foot_on[' . $widgetname . ']" id="' . $id . '[]" value="deactivated"/>
                        <input class="form-check-input test" type="checkbox" name="content_foot_on[' . $widgetname . ']" id="' . $id . '[]" value="content_foot_widget" ';
            if ($activated == '1') {
                echo 'checked';
            }
            echo '><select name="sort[]">';
            for ($j = 1; $j <= $anzcontent_foot; $j++) {
                if ($xcontent_foot['sort'] == $j) {
                    echo '<option value="' . $xcontent_foot['id'] . '-' . $j . '" selected="selected">' . $j . '</option>';
                } else {
                    echo '<option value="' . $xcontent_foot['id'] . '-' . $j . '">' . $j . '</option>';
                }
            }
            echo '</select>';
            $i++;
            echo '<br>
                        <input type="hidden" name="captcha_hash" value="' . $hash . '">';
        }
        echo '<button class="btn btn-danger" style="font-size: 10px;margin-top:10px;" type="submit" name="content_foot_deactivated"><i class="bi bi-trash3"></i> ' . $languageService->get('widget_on_setting') . '</button>
                    </div></div>
                </td>
            </tr>
            <tr>
                <td><span class="badge border border-success text-black bg-info" style="width: 100%">' . $languageService->get('footer') . '</span><br>';
        $footer_plugins_widget = safe_query("SELECT * FROM settings_plugins_widget WHERE area = '6' ORDER BY widgetname ASC");
        $i = 1;
        while ($footer_off = mysqli_fetch_array($footer_plugins_widget)) {
            $modul_name = $footer_off['modulname'];
            $widgetname = $footer_off['widgetname'];
            $widgetdatei = $footer_off['widgetdatei'];
            $id = $footer_off['id'];
            $yfooter_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND widgetname='" . $widgetname . "'");
            $yfooter = mysqli_fetch_array($yfooter_ergebnis);
            if (@$yfooter['activated'] == '1') {
            } else {
                echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="checkbox" name="footer_off_1[' . $widgetname . ']" id="' . $id . '[]" value="footer_widget" >
                        <input type="hidden" name="footer_off_2[' . $widgetname . ']" id="' . $id . '[]" value="' . $widgetdatei . '" >
                        <input type="hidden" name="footer_off_3[' . $widgetname . ']" id="' . $id . '[]" value="' . $modul_name . '" >';
            }
            $i++;
        }
        echo '</br><button class="btn btn-success" style="font-size: 10px;margin-top:10px;" type="submit" name="footer_activ"><i class="bi bi-plus-circle"></i> ' . $languageService->get('widget_off_setting') . '</button>
                </td>
                <td class=" text-center" style="align-content: center;">     
                    ' . $languageService->get('footer') . '
                    <div class="alert alert-success text-center" style="padding: 5px"><div class="border border-danger container text-center mt-3 alert alert-secondary">';
        $xfooter_ergebnis = safe_query("SELECT * FROM plugins_" . $dw['modulname'] . "_settings_widgets WHERE themes_modulname= '" . $dx['modulname'] . "' AND  position='footer_widget' ORDER BY sort");
        $tmp = mysqli_fetch_assoc(safe_query("SELECT count(id) as cnt FROM plugins_" . $dw['modulname'] . "_settings_widgets"));
        $anzfooter = $tmp['cnt'];
        $i = 1;
        while ($xfooter = mysqli_fetch_array($xfooter_ergebnis)) {
            $id = $xfooter['id'];
            $widgetname = $xfooter['widgetname'];
            $activated = $xfooter['activated'];
            $position = $xfooter['position'];
            echo '<span class="badge border border-success text-black" style="width: 150px">' . $widgetname . '</span>&nbsp;&nbsp;&nbsp;
                        <input class="form-check-input" type="hidden" name="footer_on[' . $widgetname . ']" id="' . $id . '[]" value="deactivated"/>
                        <input class="form-check-input" type="checkbox" name="footer_on[' . $widgetname . ']" id="' . $id . '[]" value="footer_widget" ';
            if ($activated == '1') {
                echo 'checked';
            }
            echo '><select name="sort[]">';
            for ($j = 1; $j <= $anzfooter; $j++) {
                if ($xfooter['sort'] == $j) {
                    echo '<option value="' . $xfooter['id'] . '-' . $j . '" selected="selected">' . $j . '</option>';
                } else {
                    echo '<option value="' . $xfooter['id'] . '-' . $j . '">' . $j . '</option>';
                }
            }
            echo '</select>';
            $i++;
            echo '<br>
                        <input type="hidden" name="captcha_hash" value="' . $hash . '">';
        }

        echo '<button class="btn btn-danger" style="font-size: 10px;margin-top:10px;" type="submit" name="footer_deactivated"><i class="bi bi-trash3"></i> ' . $languageService->get('widget_on_setting') . '</button>
                    </div></div>
                </td>
            </tr>';

        echo '
            <tr style="background-color: white!important;">
            <td style="background-color: white!important;">            
                <input type="hidden" name="captcha_hash" value="' . $hash . '">
                <input type="hidden" name="themes_modulname" value="' . $dx['modulname'] . '">
                <input type="hidden" name="modulname" value="' . $dw['modulname'] . '">
                <input type="hidden" name="id" value="' . $_GET['id'] . '">
                <input type="hidden" name="captcha_hash" value="' . $hash_2 . '" /><button class="btn btn-primary" type="submit" name="sortieren" /><i class="bi bi-sort-numeric-up-alt"></i> ' . $languageService->get('to_sort') . '</button>
            </td><td style="background-color: white!important;"style="background-color: white!important;"></td></tr>
            </tbody></table>
            </div></form>';
        echo '</div></div>';
        ###############################################

    } elseif ($action == "edit") {
        $id = $_GET['id'];

        $CAPCLASS = new \webspell\Captcha;
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

        #$themeergebnis = safe_query("SELECT * FROM settings_themes WHERE active = '1'");
        #$db = mysqli_fetch_array($themeergebnis);

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

        $widgetsergebnis = safe_query("SELECT * FROM settings_plugins_widget WHERE modulname = '" . $ds['modulname'] . "'");
        $widget = '';
        while ($df = mysqli_fetch_array($widgetsergebnis)) {
            $modulname = $df['modulname'];
            $widget .= '<div class="col-sm-12">
                                <div class="mb-3 row">
                                    <div class="col-sm-5 text-end">
                                    <button type="button" class="btn btn-info" data-toggle="popover" data-bs-placement="left" data-img="../includes/plugins/' . $ds['modulname'] . '/images/' . $df['widgetdatei'] . '.jpg" title="Widget" ><i class="bi bi-image"></i> ' . $languageService->get('preview_widget') . '</button>
                                    </div>                    
                                    <div class="col-sm-4">
                                        <div class="form-control">' . $df['widgetname'] . '</div>
                                    </div>
                                    <div class="col-sm-3"><a href="admincenter.php?site=plugin_manager&action=edit_widget&id=' . $id . '&widgetname=' . $df['widgetname'] . '" class="btn btn-warning" type="button"><i class="bi bi-pencil-square"></i> ' . $languageService->get('edit_widget') . '</a>
                                   

            <!-- Button trigger modal -->
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirm-delete" data-toggle="tooltip" data-html="true" title="' . $languageService->get('tooltip_6') . ' " data-href="admincenter.php?site=plugin_manager&amp;delete=true&amp;widgetname=' . $df['widgetname'] . '&amp;id=' . $id . '&amp;captcha_hash=' . $hash . '"><i class="bi bi-trash3"></i>  
            ' . $languageService->get('widget_delete') . '
            </button>
            <!-- Button trigger modal END-->
            <!-- Modal -->
            <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">' . $languageService->get('name') . ': ' . $df['widgetname'] . '</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="' . $languageService->get('close') . '"></button>
                  </div>
                  <div class="modal-body"><p>' . $languageService->get('really_delete') . '</p>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-square"></i> ' . $languageService->get('close') . '</button>
                    <a class="btn btn-danger btn-ok"><i class="bi bi-trash3"></i> ' . $languageService->get('widget_delete') . '</a>
                  </div>
                </div>
              </div>
            </div>
            <!-- Modal END -->

                                    </div>
                                </div>
                            </div>';
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
                <input type="hidden" name="themes_modulname" value="' . $dx['modulname'] . '">
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

        $CAPCLASS = new \webspell\Captcha;
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

        $CAPCLASS = new \webspell\Captcha;
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

                    $CAPCLASS = new \webspell\Captcha;
                    $CAPCLASS->createTransaction();
                    $hash = $CAPCLASS->getHash();

                    echo '<table id="plugini" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <th><strong>' . $languageService->get('id') . '</strong></th>
            <th width="10%"><strong>' . $languageService->get('plugin') . ' ' . $languageService->get('name') . '</strong></th>
            <th><strong>' . $languageService->get('plugin') . ' ' . $languageService->get('description') . '</strong></th>
            <th class="text-center" width="12%"><strong>' . $languageService->get('plugin_status') . '</strong></th>
            <th class="text-center" width="12%"><strong>' . $languageService->get('plugin_setting') . '</strong></th>
            <th class="text-center" width="12%"><strong>' . $languageService->get('widget_side_assignment') . '</strong></th>
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


                        if ($dx['widget_display'] == "1") {
                            echo '<td class="text-center">
                            <div class="d-grid gap-2">
                            <a href="admincenter.php?site=plugin_manager&action=widget_edit&id=' . $ds['pluginID'] . '&do=edit" class="btn btn-success" data-toggle="tooltip" data-html="true" title="' . $languageService->get('tooltip_3') . '" type="button"><i class="bi bi-plus-circle"></i> ' . $languageService->get('widget_side') . '</a></div></td>';
                        } else {

                            echo '<td class="text-center">
                            <div class="d-grid gap-2">
                        <button type="button" class="btn btn-danger" disabled><i class="bi bi-slash-circle"></i> ' . $languageService->get('widget_cannot_assigned') . '</button>
                        </div></td>';
                        }


                        if ($dx['delete_display'] != "1") {

                            echo '<td class="text-center">
                            <div class="d-grid gap-2">
                            <button type="button" class="btn btn-danger" disabled><i class="bi bi-slash-circle"></i> ' . $languageService->get('delete_cannot_assigned') . '</button>
                            </div></td>';
                        } else {

                            echo '<td class="text-center">
                            <div class="d-grid gap-2">
                            <a href="admincenter.php?site=plugin_manager&action=delete_plugin&id=' . $ds['pluginID'] . '&modulname=' . $ds['modulname'] . '&do=delete" class="btn btn-danger" data-toggle="tooltip" data-html="true" title="' . $languageService->get('tooltip_8') . '" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-plugin="' .  $ds['modulname'] . '" title="' . $languageService->get('tooltip_6') . '"><i class="bi bi-trash3"></i> ' . $languageService->get('delete_plugin') . '</a></div></td>
                            <!-- Bootstrap Modal for Confirm Delete -->
                            <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">' . $languageService->get('modulname') . ': <span id="modalPluginTitle"></span></h5>
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
                                    // Prende il link corretto del pulsante "Delete"
                                    var deleteBtn = document.getElementById("confirmDeleteBtn");
                                    var deleteUrl = this.getAttribute("href");
                            
                                    // Estrai il nome del plugin dall\'URL
                                    var urlParams = new URLSearchParams(deleteUrl.split("?")[1]);
                                    var pluginName = urlParams.get("modulname");
                            
                                    // Funzione per formattare il nome del plugin
                                    function formatPluginName(name) {
                                        // 1️⃣ Separa le parole con "_"
                                        name = name.replace(/_/g, " ");
                            
                                        // 2️⃣ Se il nome è in camelCase (es. "clanWar" → "Clan War")
                                        name = name.replace(/([a-z])([A-Z])/g, "$1 $2");
                            
                                        // 3️⃣ Rende la prima lettera di ogni parola maiuscola
                                        return name.replace(/\b\w/g, char => char.toUpperCase());
                                    }
                            
                                    var formattedName = formatPluginName(pluginName);
                            
                                    // Debug in console (solo per verifica)
                                    console.log("Original Plugin Name:", pluginName);
                                    console.log("Formatted Plugin Name:", formattedName);
                            
                                    // Aggiorna il titolo del modale con il nome corretto del plugin
                                    document.getElementById("modalPluginTitle").innerText = formattedName;
                            
                                    // Aggiorna il pulsante "Elimina" con l\'URL corretto
                                    deleteBtn.setAttribute("href", deleteUrl);
                                });
                            });
                            </script>';
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