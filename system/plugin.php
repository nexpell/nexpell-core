<?php

use nexpell\PluginSettings;
use nexpell\LanguageService;

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


}


