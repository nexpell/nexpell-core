<?php

use webspell\PluginSettings;
use webspell\LanguageService;

global $languageService;

global $modRewrite;
if ($modRewrite && !empty($GLOBALS['site']))
	$_SERVER['QUERY_STRING'] = 'site=' . $GLOBALS['site'];
elseif ($modRewrite && empty($GLOBALS['site']))
	$_SERVER['QUERY_STRING'] = 'site=startpage';


class plugin_manager
{
	
private $_debug = DEBUG;

	public function set_debug($value = "OFF")
	{
	    $this->_debug = strtoupper($value) === "ON" ? "ON" : "OFF";
	}

    public function plugin_data($var, $id = 0, $admin = false)
	{
	    if ($id > 0) {
	        $query = safe_query("SELECT * FROM settings_plugins WHERE `activate`='1' AND `pluginID`=" . intval($id));
	        return mysqli_fetch_array($query);
	    }

	    $field = $admin ? 'admin_file' : 'index_link';
	    $result = safe_query("SELECT * FROM settings_plugins WHERE `activate`='1'");

	    while ($row = mysqli_fetch_array($result)) {
	        $files = explode(",", $row[$field]);
	        if (in_array($var, $files)) {
	            return $row;
	        }
	    }

	    return false;
	}


    public function is_plugin($site)
    {
        $result = safe_query("SELECT * FROM settings_plugins WHERE activate='1'");
        while ($row = mysqli_fetch_array($result)) {
            $index_links = explode(",", $row['index_link']);
            if (in_array($site, $index_links)) {
                return 1;
            }
        }
        return 0;
    }

    public function plugin_check($data, $site)
    {
        $return = [];

        if ($data['activate'] == 1) {
            if ($site) {
                $tfiles = explode(",", $data['index_link']);
                if (in_array($site, $tfiles)) {
                    if (file_exists($data['path'] . $site . ".php")) {
                        $return['status'] = 1;
                        $return['data'] = $data['path'] . $site . ".php";
                        return $return;
                    } else {
                        if (!file_exists(MODULE . $site . ".php")) {
                            $site = "404";
                        }
                        $return['status'] = 1;
                        $return['data'] = MODULE . $site . ".php";
                        return $return;
                    }
                }
            }
        }

        if (!file_exists(MODULE . $site . ".php")) {
            $site = "404";
        }
        $return['status'] = 1;
        $return['data'] = MODULE . $site . ".php";
        return $return;
    }

    public function plugin_updatetitle($site)
    {
        try {
            if ($this->is_plugin($site) == 1) {
                $arr = $this->plugin_data($site);
                if (isset($arr['name'])) {
                    return settitle($arr['name']);
                }
            }
        } catch (Exception $x) {
            if ($this->_debug === "ON") {
                return '<span class="label label-danger">' . $x->getMessage() . '</span>';
            }
        }

        return null;
    }



	####################################
	function plugin_widget_data($var, $id = 0, $admin = false)
	{
		if ($id > 0) {
			parse_str($_SERVER['QUERY_STRING'], $qs_arr);
			$getsite = 'startpage'; #Wird auf der Startseite angezeigt index.php
			if (isset($qs_arr['site'])) {
				$getsite = $qs_arr['site'];
			}

			$id = isset($id) ? intval($id) : 0;

			if (PluginSettings::load_widget_settings($getsite)) {
			    $query = safe_query("SELECT * FROM settings_plugins_widget_settings WHERE id='$id'");

			} elseif ($getsite === 'forum_topic') {
			    $query = safe_query("SELECT * FROM plugins_forum_settings_widgets WHERE id='$id'");

			} elseif (tableExists("plugins_" . $getsite . "_settings_widgets")) {
			    $query = safe_query("SELECT * FROM plugins_" . $getsite . "_settings_widgets WHERE id='$id'");

			} else {
			    header("Location: ./index.php?site=error_404");
			    exit;
			}
			
		} else {
			echo 'leer';
		}

		if (!isset($query)) {
			return false;
		}
		try {
			if (mysqli_num_rows($query)) {
				$row = mysqli_fetch_array($query);
				return $row;
			}
		} catch (EXCEPTION $e) {
			return $e->message();
		}
	}


	//@info 		check if the plugin is activated and exists. 
	//				True = include the sc_file from plugin directory
	//				False = dont load this plugin

	function plugin_widget($id, $name = false, $css = false)
	{
		$pid = intval($id);
		#$_language = new \webspell\Language;
		#$_language->readModule('plugin');
		if (!empty($pid)) {
			$manager = new plugin_manager();
			$row = $manager->plugin_widget_data("", $pid);

			$query = safe_query("SELECT *
			FROM  settings_plugins
			Where modulname = '" . $row['modulname'] . "'
			");
			$ds = mysqli_fetch_array($query);

			if (@$ds['activate'] != "1") {
				if ($this->_debug === "ON") {
					return ('');
				}
				return false;
			}

			if (file_exists($ds['path'] . $row['widgetdatei'] . ".php")) {
				$plugin_path = $ds['path'];
				require($ds['path'] . $row['widgetdatei'] . ".php");
				return false;
			} else {
				if ($this->_debug === "ON") {
					return ('<span class="label label-danger">plugin_not_found</span>');
				}
			}
		}
	}


	#################################################	
	//@info		search a plugin by name and return the ID
	function pluginID_by_name($name)
	{
		$request = safe_query("SELECT * FROM `settings_plugins` WHERE `activate`='1' AND `name` LIKE '%" . $name . "%'");
		if (mysqli_num_rows($request)) {
			$tmp = mysqli_fetch_array($request);
			return $tmp['pluginID'];
		}
		return 0;
	}

	//@info		include a file which saved in hiddenfiles
	function plugin_hf($id, $name)
	{
		$pid = intval($id);
		
		if (!empty($pid) and !empty($name)) {
			$manager = new plugin_manager();
			$row = $manager->plugin_data("", $pid);
			$hfiles = $row['hiddenfiles'];
			$tfiles = explode(",", $hfiles);
			if (in_array($name, $tfiles)) {
				if (file_exists($row['path'] . $name . ".php")) {
					$plugin_path = $row['path'];
					require_once($row['path'] . $name . ".php");
					return false;
				} else {
					if ($this->_debug === "ON") {
						return ('<span class="label label-danger">plugin_not_found</span>');
					}
				}
			}
		}
	}

	//@info 		get the plugin directories from database and check 
	//				if in any plugin (direct) or in the subfolders (css & js)
	//				are file which must load into the <head> Tag
	function plugin_loadheadfile_css($pluginadmin = false)
	{

		$settings = safe_query("SELECT * FROM settings");
		$ds = mysqli_fetch_array($settings);




		parse_str($_SERVER['QUERY_STRING'], $qs_arr);
		$getsite = $ds['startpage'];
		if (isset($qs_arr['site'])) {
			$getsite = $qs_arr['site'];
		}

		$ds = mysqli_fetch_array(safe_query("SELECT * FROM `settings_plugins` WHERE index_link LIKE '%$getsite%' AND `activate`='1'"));
		@$modulname = $ds['modulname'];

		$css = "\n";
		$query = safe_query("SELECT * FROM `settings_plugins` WHERE `activate`='1' AND modulname = '" . $modulname . "'");
		if ($pluginadmin) {
			$pluginpath = "../";
		} else {
			$pluginpath = "";
		}

		while ($res = mysqli_fetch_array($query)) {
			if ($res['modulname'] == $modulname || $res == 1) {
				if (is_dir($pluginpath . $res['path'] . "css/")) {
					$subf1 = "css/";
				} else {
					$subf1 = "";
				}
				$f = array();
				$f = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $pluginpath . $res['path'] . $subf1) . '*.css');
				$fc = count((array($f)), COUNT_RECURSIVE);
				if ($fc > 0) {
					global $loaded_css_files;
					if (!isset($loaded_css_files)) {
						$loaded_css_files = array();
					}

					for ($b = 0; $b < count($f); $b++) {
						if (!in_array($f[$b], $loaded_css_files)) { // Controllo per evitare duplicati
							$css .= '<link type="text/css" rel="stylesheet" href="./' . $f[$b] . '" />' . chr(0x0D) . chr(0x0A);
							$loaded_css_files[] = $f[$b]; // Aggiunge il file alla lista dei caricati
						}
					}
				}
			}
		}
		return $css;
	}

	function plugin_loadheadfile_js($pluginadmin = false)
	{
		parse_str($_SERVER['QUERY_STRING'], $qs_arr);
		$getsite = '';
		if (isset($qs_arr['site'])) {
			$getsite = $qs_arr['site'];
		}

		$dk = mysqli_fetch_array(safe_query("SELECT * FROM `settings_plugins` WHERE index_link LIKE '%$getsite%' AND `activate`='1'"));
		@$modulname = $dk['modulname'];

		$js = "\n";
		$query = safe_query("SELECT * FROM `settings_plugins` WHERE `activate`='1' AND modulname = '" . $modulname . "'");
		if ($pluginadmin) {
			$pluginpath = "../";
		} else {
			$pluginpath = "";
		}
		while ($res = mysqli_fetch_array($query)) {
			if ($res['modulname'] == $modulname || $res == 1) {
				if (is_dir($pluginpath . $res['path'] . "js/")) {
					$subf2 = "js/";
				} else {
					$subf2 = "";
				}
				$f = array();
				$f = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $pluginpath . $res['path'] . $subf2) . '*.js');
				$fc = count((array($f)), COUNT_RECURSIVE);
				if ($fc > 0) {
					global $loaded_js_files;
					if (!isset($loaded_js_files)) {
						$loaded_js_files = array();
					}

					for ($b = 0; $b < count($f); $b++) {
						if (!in_array($f[$b], $loaded_js_files)) { // Controllo per evitare duplicati
							$js .= '<script defer src="./' . $f[$b] . '"></script>' . chr(0x0D) . chr(0x0A);
							$loaded_js_files[] = $f[$b]; // Aggiunge il file alla lista dei caricati
						}
					}
				}
			}
		}
		return $js;
	}



	################################################################################


	function plugin_loadheadfile_widget_css()
	{
		parse_str($_SERVER['QUERY_STRING'], $qs_arr);
		$getsite = 'startpage';
		if (isset($qs_arr['site'])) {
			$getsite = $qs_arr['site'];
		}
		$pluginpath = "includes/plugins/";
		$css = "\n";

		if (PluginSettings::load_widget_settings_css($getsite)) {
		    $query = safe_query("SELECT * FROM settings_plugins_widget_settings");
		} elseif ($getsite == 'forum_topic') {
		    $query = safe_query("SELECT * FROM plugins_forum_settings_widgets");
		} elseif (tableExists("plugins_" . $getsite . "_settings_widgets")) {
		    $query = safe_query("SELECT * FROM plugins_" . $getsite . "_settings_widgets");
		} else {
		    header("Location: ./index.php?site=error_404");
		    exit;
		}

		while ($res = mysqli_fetch_array($query)) {

			#Agency Header Navigation .css wird extra geladen
			if ($res['widgetdatei'] == 'widget_agency_header') {
				echo '<link type="text/css" rel="stylesheet" href="./includes/plugins/carousel/css/style/agency_header.css" />';
			}

			if (is_dir($pluginpath . $res['modulname'] . "/css/")) {
				$subf1 = "/css/";
			} else {
				$subf1 = "";
			}
			$f = array();
			$f = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $pluginpath . $res['modulname'] . $subf1) . '*.css');
			$fc = count((array($f)), COUNT_RECURSIVE);
			if ($fc > 0) {
				global $loaded_css_files;
				if (!isset($loaded_css_files)) {
					$loaded_css_files = array();
				}

				for ($b = 0; $b < count($f); $b++) {
					if (!in_array($f[$b], $loaded_css_files)) { // Controllo per evitare duplicati
						$css .= '<link type="text/css" rel="stylesheet" href="./' . $f[$b] . '" />' . chr(0x0D) . chr(0x0A);
						$loaded_css_files[] = $f[$b]; // Aggiunge il file alla lista dei caricati
					}
				}
			}
		}
		return $css;
	}


	function plugin_loadheadfile_widget_js()
	{
		parse_str($_SERVER['QUERY_STRING'], $qs_arr);
		$getsite = 'startpage';
		if (isset($qs_arr['site'])) {
			$getsite = $qs_arr['site'];
		}
		$pluginpath = "includes/plugins/";

		$js = "\n";

		if (PluginSettings::load_widget_settings_css($getsite)) {
		    $query = safe_query("SELECT * FROM settings_plugins_widget_settings");
		} elseif ($getsite == 'forum_topic') {
		    $query = safe_query("SELECT * FROM plugins_forum_settings_widgets");
		} elseif (tableExists("plugins_" . $getsite . "_settings_widgets")) {
		    $query = safe_query("SELECT * FROM plugins_" . $getsite . "_settings_widgets");
		} else {
		    header("Location: ./index.php?site=error_404");
		    exit;
		}

		while ($res = mysqli_fetch_array($query)) {
			if (is_dir($pluginpath . $res['modulname'] . "/css/")) {
				$subf1 = "/js/";
			} else {
				$subf1 = "";
			}
			$f = array();
			$f = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $pluginpath . $res['modulname'] . $subf1) . '*.js');
			$fc = count((array($f)), COUNT_RECURSIVE);
			if ($fc > 0) {
				global $loaded_js_files;
				if (!isset($loaded_js_files)) {
					$loaded_js_files = array();
				}

				for ($b = 0; $b < count($f); $b++) {
					if (!in_array($f[$b], $loaded_js_files)) { // Controllo per evitare duplicati
						$js .= '<script defer src="./' . $f[$b] . '"></script>' . chr(0x0D) . chr(0x0A);
						$loaded_js_files[] = $f[$b]; // Aggiunge il file alla lista dei caricati
					}
				}
			}
		}
		return $js;
	}

	
}



