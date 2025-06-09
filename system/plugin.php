<?php

/**
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 *                  Webspell-RM      /                        /   /                                          *
 *                  -----------__---/__---__------__----__---/---/-----__---- _  _ -                         *
 *                   | /| /  /___) /   ) (_ `   /   ) /___) /   / __  /     /  /  /                          *
 *                  _|/_|/__(___ _(___/_(__)___/___/_(___ _/___/_____/_____/__/__/_                          *
 *                               Free Content / Management System                                            *
 *                                           /                                                               *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @version         webspell-rm                                                                              *
 *                                                                                                           *
 * @copyright       2018-2023 by webspell-rm.de                                                              *
 * @support         For Support, Plugins, Templates and the Full Script visit webspell-rm.de                 *
 * @website         <https://www.webspell-rm.de>                                                             *
 * @forum           <https://www.webspell-rm.de/forum.html>                                                  *
 * @wiki            <https://www.webspell-rm.de/wiki.html>                                                   *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @license         Script runs under the GNU GENERAL PUBLIC LICENCE                                         *
 *                  It's NOT allowed to remove this copyright-tag                                            *
 *                  <http://www.fsf.org/licensing/licenses/gpl.html>                                         *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @author          Code based on WebSPELL Clanpackage (Michael Gruber - webspell.at)                        *
 * @copyright       2005-2011 by webspell.org / webspell.info                                                *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 */

use webspell\PluginSettings;
use webspell\LanguageService;

global $languageService;

#$lang = $languageService->detectLanguage();
#$languageService->readModule('plugin');

global $modRewrite;
if ($modRewrite && !empty($GLOBALS['site']))
	$_SERVER['QUERY_STRING'] = 'site=' . $GLOBALS['site'];
elseif ($modRewrite && empty($GLOBALS['site']))
	$_SERVER['QUERY_STRING'] = 'site=startpage';


class plugin_manager
{
	var $_debug;

	//@debug 		if debug mode ON show failure messages otherwise hide this
	function set_debug($var)
	{
		$this->_debug = $var;
	}

	//@info 		check if a plugin index-link file exists that i can called by
	//				index.php?site=xxx
	function is_plugin($var)
	{
		try {
			$query = safe_query("SELECT * FROM settings_plugins WHERE `activate`='1' AND `index_link` LIKE '%" . $var . "%'");
			if (mysqli_num_rows($query)) {
				return 1;
			} else {
				return 0;
			}
		} catch (EXCPETION $e) {
			return $e->message();
		}
	}

	//@info 		get the plugin data from database
	function plugin_data($var, $id = 0, $admin = false)
	{
		if ($id > 0) {
			$where = " WHERE `activate`='1' AND `pluginID`='" . intval($id) . "'";
			$query = safe_query("SELECT * FROM settings_plugins " . $where);
		} else {
			if ($admin) {
				$where = " WHERE `activate`='1' AND `admin_file` LIKE '%" . $var . "%'";
			} else {
				$where = " WHERE `activate`='1' AND `index_link` LIKE '%" . $var . "%'";
			}
			$q = safe_query("SELECT * FROM settings_plugins " . $where);
			if (mysqli_num_rows($q)) {
				$tmp = mysqli_fetch_array($q);
				$ifiles = $tmp['index_link'];
				$tfiles = explode(",", $ifiles);
				if (in_array($var, $tfiles)) {
					$where = " WHERE `pluginID`='" . $tmp['pluginID'] . "'";
					$query = safe_query("SELECT * FROM settings_plugins " . $where);
				}
			}
			$w = safe_query("SELECT * FROM settings_plugins " . $where);
			if (mysqli_num_rows($w)) {
				$xtmp = mysqli_fetch_array($w);
				$afiles = $xtmp['admin_file'];
				$bfiles = explode(",", $afiles);
				if (in_array($var, $bfiles)) {
					$where = " WHERE `pluginID`='" . $xtmp['pluginID'] . "'";
					$query = safe_query("SELECT * FROM settings_plugins " . $where);
				}
			}
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

	function plugin_check($data, $site)
	{
		
		$return = array();
		#whouseronline();
		if (isset($data['activate']) == 1) {
			if (isset($site)) {
				$ifiles = $data['index_link'];
				$tfiles = explode(",", $ifiles);
				if (in_array($site, $tfiles)) {
					if (file_exists($data['path'] . $site . ".php")) {
						$plugin_path = $data['path'];
						$return['status'] = 1;
						$return['data'] = $data['path'] . $site . ".php";
						return $return;
					} else {
						if (DEBUG === "ON") {
							#echo '<!-- <br /><span class="label label-danger">' . $languageService->module['plugin_not_found'] . '</span> -->';
						}
						if (!file_exists(MODULE . $site . ".php")) {
							$site = "404";
						}
						$return['status'] = 1;
						$return['data'] = MODULE . $site . ".php";
						return $return;
					}
				}
			} else {
				if (file_exists($data['path'] . $data['index_link'] . ".php")) {
					$plugin_path = $data['path'];
					$return['status'] = 1;
					$return['data'] = $data['path'] . $data['index_link'] . ".php";
					return $return;
				} else {
					if (DEBUG === "ON") {
						#return '<!-- <br /><span class="label label-danger">' . $languageService->module['plugin_not_found'] . '</span> -->';
						return ;
					}
					if (!file_exists(MODULE . $site . ".php")) {
						$site = "404";
					}
					$return['status'] = 1;
					$return['data'] = MODULE . $site . ".php";
					return $return;
				}
			}
		} else {
			if (DEBUG === "ON") {
				echo ('<!-- <br /><span class="label label-warning">' . $languageService->module['plugin_deactivated'] . '</span> -->');
			}
			if (!file_exists(MODULE . $site . ".php")) {
				$site = "404";
			}
			$return['status'] = 1;
			$return['data'] = MODULE . $site . ".php";
			return $return;
		}
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
					return ('<span class="label label-danger">' . $languageService->module['plugin_not_found'] . '</span>');
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
						return ('<span class="label label-danger">' . $languageService->module['plugin_not_found'] . '</span>');
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




	//@info		get the page default language and check if the user / guests
	//			change into his own language otherwise set default language to EN
	//@name		set the name of the language file to load
	/* CALL IT 
				/!\ NEVER use the variable $_language (conflict with the main module)
	
		$pm = new plugin_manager(); 
		$_lang = $pm->plugin_language("my-plugin", $plugin_path);
	
	*/
	/*function plugin_language($name, $plugin_path)
	{
		$res = safe_query("SELECT `default_language` FROM `settings` WHERE 1");
		$row = mysqli_fetch_array($res);
		if (isset($_SESSION['language'])) {
			$lng = $_SESSION['language'];
		} elseif (isset($_SESSION['language'])) {
			$lng = $_SESSION['language'];
		} else {
			if (isset($row['default_language'])) {
				$lng = $row['default_language'];
			} else {
				$lng = "en";
			}
		}
		$_lang = new webspell\Language();
		$_lang->setLanguage($lng, false);
		$_lang->readModule($name, true, false, $plugin_path);
		return $_lang->module;
	}
	function plugin_adminLanguage($plugin, $file, $admin = false)
	{
		try {
			$res = safe_query("SELECT `default_language` FROM `settings` WHERE 1");
			$row = mysqli_fetch_array($res);
			if (isset($_SESSION['language'])) {
				$lng = $_SESSION['language'];
			} elseif (isset($_SESSION['language'])) {
				$lng = $_SESSION['language'];
			} else {
				if (isset($row['default_language'])) {
					$lng = $row['default_language'];
				} else {
					$lng = "en";
				}
			}
			$p = "./" . $file . "";
			if (isset($admin)) {
				$admin = "admin";
			} else {
				$admin = "";
			}
			$arr = array();
			include("$p/languages/$lng/$admin/$plugin.php");
			foreach ($language_array as $key => $val) {
				$arr[$key] = $val;
			}
			return $arr;
		} catch (EXCEPTION $ex) {
			return $ex->message();
		}
	}*/

	//@info		update website title for SEO
	function plugin_updatetitle($site)
	{
		try {
			$pm = new plugin_manager();
			if ($pm->is_plugin($_GET['site']) == 1) {
				$arr = $pm->plugin_data($_GET['site']);
				if (isset($arr['name'])) {
					return settitle($arr['name']);
				}
			}
		} catch (EXCEPTION $x) {
			if ($this->_debug === "ON") {
				return ('<span class="label label-danger">' . $x->message() . '</span>');
			}
		}
	}
}


/*Plugins manuell einbinden 
get_widget('modulname','widgetdatei'); 
*/
function get_widget($modulname, $widgetdatei)
{

	$query = safe_query("SELECT * FROM  settings_plugins WHERE modulname = '" . $modulname . "'");
	$ds = mysqli_fetch_array($query);

	if (@file_exists($ds['path'] . $widgetdatei . ".php" ?? '')) {
		$plugin_path = $ds['path'];
		require($ds['path'] . $widgetdatei . ".php");
		return false;
	} else {
		echo '';
	}
}
