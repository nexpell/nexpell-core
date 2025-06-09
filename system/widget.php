<?php

use webspell\PluginSettings;

$ergebnis = safe_query("SELECT * FROM settings_themes WHERE active= 1");
$dx = mysqli_fetch_array($ergebnis);

#Verhindert einen Fehler wenn kein Template aktiviert ist
if (@$dx['active'] != '1') {
} else {
    $themes_modulname = $dx['modulname'];
}

class widgets
{

    function safe_query($query)
    {
        include_once("settings.php");
        return safe_query($query);
    }

    private string $_modulname;
    private string $_widgetname;

    private function isComplete($plugin_folder)
    {
        $info = $this->getInfo($plugin_folder);
        if ($this->infoExists("includes/plugins/$plugin_folder")) {
            return true;
        }
        return false;
    }

    public function showWidget($name, $curr_widgetname = "", $curr_modulname = "", $curr_widgetdatei = "", $curr_id = "")
    {
        $widgetname = $this->_widgetname;
        $widgetdatei = $this->_widgetdatei;
        $modulname = $this->_modulname;
        $qs_arr = array();
        parse_str($_SERVER['QUERY_STRING'], $qs_arr);

        $getsite = 'startpage'; #Wird auf der Startseite angezeigt index.php
        if (isset($qs_arr['site'])) {
            $getsite = $qs_arr['site'];
        }        

        if (PluginSettings::load_widget_settings_css($getsite)) {

            $query = safe_query("SELECT * FROM `settings_plugins_widget_settings` WHERE widgetname='" . $widgetname . "'");
            $db = mysqli_fetch_array($query);

            if ((array)$db) {
                $plugin = new plugin_manager();
                $plugin->set_debug(DEBUG);
                echo $plugin->plugin_widget($db["id"] ?? '');
            }
        } elseif (@$getsite == 'forum_topic') {
            $query = safe_query("SELECT * FROM `plugins_forum_settings_widgets` WHERE widgetname='" . $widgetname . "'");
            $db = mysqli_fetch_array($query);

            if ((array)$db) {
                $plugin = new plugin_manager();
                $plugin->set_debug(DEBUG);
                echo $plugin->plugin_widget($db["id"] ?? '');
            }
        } else {
            $query = safe_query("SELECT * FROM `plugins_" . $getsite . "_settings_widgets` WHERE widgetname='" . $widgetname . "'");
            $db = mysqli_fetch_array($query);

            if ((array)$db) {
                $plugin = new plugin_manager();
                $plugin->set_debug(DEBUG);
                echo $plugin->plugin_widget($db["id"] ?? '');
            }
        }
    }


    public function registerWidget($position, $template_file = "")
    {
        $qs_arr = array();
        parse_str($_SERVER['QUERY_STRING'], $qs_arr);
        $getsite = 'startpage'; #Wird auf der Startseite angezeigt index.php
        if (isset($qs_arr['site'])) {
            $getsite = $qs_arr['site'];
        }

        if (PluginSettings::load_widget_settings_css($getsite)) {    
            global $themes_modulname;
            $select_all_widgets = "SELECT * FROM settings_plugins_widget_settings
            WHERE position LIKE '$position'
            AND widgetdatei IS NOT NULL 
            AND modulname IS NOT NULL 
            AND themes_modulname='$themes_modulname' 
            ORDER BY sort ASC";

            $result_all_widgets = $this->safe_query($select_all_widgets);
            $widgets_templates = "<div class='panel-body'>No Widgets added.</div>";
            $curr_widget_template = false;
            if (mysqli_num_rows($result_all_widgets) > 0) {
                $widgets_templates = "";
                while ($widget = mysqli_fetch_array($result_all_widgets)) {
                    $curr_id            = $widget['id'];
                    $curr_widgetdatei   = $widget['widgetdatei'];
                    $curr_modulname     = $widget['modulname'];
                    $curr_widgetname    = $widget['widgetname'];
                    $this->_widgetname = $curr_widgetname;
                    @$this->_widgetdatei = $curr_widgetdatei;
                    @$this->_modulname  = $curr_modulname;
                    $curr_widget_template = $this->showWidget($curr_id, $curr_modulname, $curr_widgetdatei, $curr_widgetname);
                }
            } else {
                $curr_widget_template = true;
            }
        } elseif (@$getsite == 'forum_topic') {
            global $themes_modulname;
            $select_all_widgets = "SELECT * FROM plugins_forum_settings_widgets
            WHERE position LIKE '$position'
            AND widgetdatei IS NOT NULL 
            AND modulname IS NOT NULL 
            AND themes_modulname='$themes_modulname' 
            ORDER BY sort ASC";

            $result_all_widgets = $this->safe_query($select_all_widgets);
            $widgets_templates = "<div class='panel-body'>No Widgets added.</div>";
            $curr_widget_template = false;
            if (mysqli_num_rows($result_all_widgets) > 0) {
                $widgets_templates = "";
                while ($widget = mysqli_fetch_array($result_all_widgets)) {
                    $curr_id            = $widget['id'];
                    $curr_widgetdatei   = $widget['widgetdatei'];
                    $curr_modulname     = $widget['modulname'];
                    $curr_widgetname    = $widget['widgetname'];
                    $this->_widgetname = $curr_widgetname;
                    @$this->_widgetdatei = $curr_widgetdatei;
                    @$this->_modulname  = $curr_modulname;
                    $curr_widget_template = $this->showWidget($curr_id, $curr_modulname, $curr_widgetdatei, $curr_widgetname);
                }
            } else {
                $curr_widget_template = true;
            }
        } else {

            global $themes_modulname;
            $select_all_widgets = "SELECT * FROM plugins_" . $getsite . "_settings_widgets
            WHERE position LIKE '$position'
            AND widgetdatei IS NOT NULL 
            AND modulname IS NOT NULL 
            AND themes_modulname='$themes_modulname' 
            ORDER BY sort ASC";

            $result_all_widgets = $this->safe_query($select_all_widgets);
            $widgets_templates = "<div class='panel-body'>No Widgets added.</div>";
            $curr_widget_template = false;
            if (mysqli_num_rows($result_all_widgets) > 0) {
                $widgets_templates = "";
                while ($widget = mysqli_fetch_array($result_all_widgets)) {
                    $curr_id            = $widget['id'];
                    $curr_widgetdatei   = $widget['widgetdatei'];
                    $curr_modulname     = $widget['modulname'];
                    $curr_widgetname    = $widget['widgetname'];
                    $this->_widgetname = $curr_widgetname;
                    @$this->_widgetdatei = $curr_widgetdatei;
                    @$this->_modulname  = $curr_modulname;
                    $curr_widget_template = $this->showWidget($curr_id, $curr_modulname, $curr_widgetdatei, $curr_widgetname);
                }
            } else {
                $curr_widget_template = true;
            }
        }
    }
}
