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


######################################################################
// Löscht in der Mysqli Datenbank eine Definierte Tabelle
function table_exist($table){ 
  safe_query("DROP TABLE IF EXISTS`$table`");   // Tabelle Löschen
} 


// Loescht in der Mysqli Datenbank eine Definierte Spalte
function DeleteData($name,$where,$data) {
  if (mysqli_num_rows(safe_query("SELECT * FROM `$name` WHERE $where='".$data."'")) >= 1 ) { 
    safe_query("DELETE FROM `$name` WHERE $where = '$data'");    // Tabelle Loeschen
  } else {
    #echo "Keine Spalte vorhanden mit den Namen $name."; // Meldung soll nicht angezeigt werden
    echo "";
  }
}

// Loescht in der Mysqli Datenbank eine Definierte Spalte
function DeleteThemeData($name,$where,$data,$theme,$themedate) {
  if (mysqli_num_rows(safe_query("SELECT * FROM `$name` WHERE $where='".$data."' AND $theme='".$themedate."'")) >= 1 ) { 
    safe_query("DELETE FROM `$name` WHERE $where = '$data' AND $theme='$themedate'");    // Tabelle Loeschen
  } else {
    #echo "Keine Spalte vorhanden mit den Namen $name."; // Meldung soll nicht angezeigt werden
    echo "";
  }
}

// Loescht die Mysqli Datenbank xyz
function DeleteTable($table) {
  global $_database;
	if (safe_query("DROP TABLE IF EXISTS`$table`")) {
	  //echo "<div class='alert alert-success'>String ausgef&uuml;hrt! <br />";
	  //return true;
	} else {
	  echo "<div class='alert alert-danger'>String failed <br />";
	  echo "String ausf&uuml;hren fehlgeschlagen!<br /></div>";
	  return "<pre>DROP TABLE IF EXISTS `".$table."</pre>";
	  //
	}
}
#######################################################################################################################################

	#### addfield #####################

	#$transaction .= addfield('settings_themes', 'agency', 'int(1)', 'NOT NULL DEFAULT 0 AFTER `headlines`'); nach headlines

function checknewfield($table,$newfield) {
  $res = safe_query("SHOW COLUMNS from `$table`");
  $_tablespecs = array();   
  $_record = array();
  $existfield = '1'; 
  while($_record = mysqli_fetch_assoc($res)) {
      $_tablespecs[$_record['Field']] = $_record;
      $newfields = $_record['Field'];
      if($_record['Field'] === $newfield) { $existfield = '0';}
  }
  return $existfield; 
}

function addfield($table,$newfield,$typ,$standart) {
  global $_database;

  $checked = checknewfield($table,$newfield);
  if($checked == '1') {
    mysqli_query($_database,"ALTER TABLE `$table` ADD `".$newfield."` ".$typ." ".$standart."");
  }
}

#### addtable #####################

function addtable($table) {
  global $_database,$modulname,$version,$str;

    if(mysqli_num_rows(safe_query("SELECT * FROM `settings_plugins` WHERE modulname ='".$modulname."' AND version = '".$version."'"))>0) {
      echo "<div class='alert alert-warning'><b>Database ".$str.":</b><br>".$str." Database entry already exists <br />";
      echo "".$str." Datenbank Eintrag schon vorhanden <br />";
      echo "".$str." La voce del database esiste già <br /></div>";
      echo "<hr>";
    } else {
      try {
        if(safe_query("".$table."")) {
          echo "<div class='alert alert-success'><b>Database ".$str.":</b><br>All database entries for the plugin ".$str." have been successfully installed <br />";
          echo "Alle Datenbankeinträge für das Plugin ".$str." wurden  erfolgreich installiert <br />";
          echo "Tutte le voci del database per il plugin ".$str." sono state installate con successo <br /></div>";
          echo "<hr>";
        } else {
          echo "<div class='alert alert-warning'><b>Database ".$str.":</b><br>Database ".$str." entry already exists <br />";
          echo "Datenbank ".$str." Eintrag schon vorhanden <br />";
          echo "Database ".$str." La voce esiste già <br /></div>";
          echo "<hr>";
        }   
      } CATCH (EXCEPTION $x) {
        echo "<div class='alert alert-danger'><b>Database ".$str.":</b><br>Database ".$str." installation failed <br />";
        echo "Send the following line to the support team:<br />";
        echo "Invia la seguente riga al team di supporto:<br /><br /><br />";
        echo "<pre>".$x->getMessage()."</pre>";     
        echo"</div>";
      }
    }
}

#### add_insert_table #####################

function add_insert_table($table) {
  global $_database,$modulname,$version,$str;

    if(mysqli_num_rows(safe_query("SELECT * FROM `settings_plugins` WHERE modulname ='".$modulname."' AND version = '".$version."'"))>0) {
      echo "<div class='alert alert-warning'><b>Database ".$str.":</b><br>".$str." Database entry already exists <br />";
      echo "".$str." Datenbank Eintrag schon vorhanden <br />";
      echo "".$str." La voce del database esiste già <br /></div>";
      echo "<hr>";
    } else {
      try {
        if(safe_query("".$table."")) {
          echo "<div class='alert alert-success'><b>Database ".$str.":</b><br>All database entries for the plugin ".$str." have been successfully installed <br />";
          echo "Alle Datenbankeinträge für das Plugin ".$str." wurden  erfolgreich installiert <br />";
          echo "Tutte le voci del database per il plugin ".$str." sono state installate con successo <br /></div>";
          echo "<hr>";
        } else {
          echo "<div class='alert alert-warning'><b>Database ".$str.":</b><br>Database ".$str." entry already exists <br />";
          echo "Datenbank ".$str." Eintrag schon vorhanden <br />";
          echo "Database ".$str." La voce esiste già <br /></div>";
          echo "<hr>";
        }   
      } CATCH (EXCEPTION $x) {
        echo "<div class='alert alert-danger'><b>Database ".$str.":</b><br>Database ".$str." installation failed <br />";
        echo "Send the following line to the support team:<br />";
        echo "Invia la seguente riga al team di supporto:<br /><br /><br />";
        echo "<pre>".$x->getMessage()."</pre>";     
        echo"</div>";
      }
    }
}

#### add_insert_plugin #####################

function add_insert_plugin($table) {
  global $_database,$modulname,$version,$str;

    if(mysqli_num_rows(safe_query("SELECT * FROM `settings_plugins` WHERE modulname ='".$modulname."'"))>0) {
      echo "<div class='alert alert-warning'><b>Plugineinträge:</b><br>".$str." Database entry already exists <br />";
      echo "".$str." Datenbank Eintrag schon vorhanden <br />";
      echo "".$str." La voce del database esiste già <br /></div>";
      echo "<hr>";
    } else {
      try {
        if(safe_query("".$table."")) {
          echo "<div class='alert alert-success'><b>Plugineinträge:</b><br>Entries for ".$str." have been successfully added to the <b>settings_plugins</b> database <br />";
          echo "Einträge für ".$str." wurden der <b>settings_plugins</b> Datenbank erfolgreich hinzugef&uuml;gt<br />";
          echo "Le voci per ".$str." sono stati aggiunti con successo al database <b>settings_plugins</b><br /></div>";
          echo "<hr>";
        } else {
          echo "<div class='alert alert-warning'>Database ".$str." entry already exists <br />";
          echo "Datenbank ".$str." Eintrag schon vorhanden <br />";
          echo "Database ".$str." La voce esiste già <br /></div>";
          echo "<hr>";
        }   
      } CATCH (EXCEPTION $x) {
        echo "<div class='alert alert-danger'><b>Plugineinträge:</b><br>Database ".$str." installation failed <br />";
        echo "Send the following line to the support team:<br />";
        echo "Invia la seguente riga al team di supporto:<br /><br /><br />";
        echo "<pre>".$x->getMessage()."</pre>";     
        echo"</div>";
      }
    }
}

function add_insert_plugin_2($table) {
  global $_database,$modulname_2,$version,$str;

    if(mysqli_num_rows(safe_query("SELECT * FROM `settings_plugins` WHERE modulname ='".$modulname_2."'"))>0) {
      echo "<div class='alert alert-warning'><b>Plugineinträge:</b><br>".$str." Database entry already exists <br />";
      echo "".$str." Datenbank Eintrag schon vorhanden <br />";
      echo "".$str." La voce del database esiste già <br /></div>";
      echo "<hr>";
    } else {
      try {
        if(safe_query("".$table."")) {
          echo "<div class='alert alert-success'><b>Plugineinträge:</b><br>Entries for ".$str." have been successfully added to the <b>settings_plugins</b> database <br />";
          echo "Einträge für ".$str." wurden der <b>settings_plugins</b> Datenbank erfolgreich hinzugef&uuml;gt<br />";
          echo "Le voci per ".$str." sono stati aggiunti con successo al database <b>settings_plugins</b><br /></div>";
          echo "<hr>";
        } else {
          echo "<div class='alert alert-warning'>Database ".$str." entry already exists <br />";
          echo "Datenbank ".$str." Eintrag schon vorhanden <br />";
          echo "Database ".$str." La voce esiste già <br /></div>";
          echo "<hr>";
        }   
      } CATCH (EXCEPTION $x) {
        echo "<div class='alert alert-danger'><b>Plugineinträge:</b><br>Database ".$str." installation failed <br />";
        echo "Send the following line to the support team:<br />";
        echo "Invia la seguente riga al team di supporto:<br /><br /><br />";
        echo "<pre>".$x->getMessage()."</pre>";     
        echo"</div>";
      }
    }
}

function add_insert_plugin_3($table) {
  global $_database,$modulname_2,$version,$str;

    if(mysqli_num_rows(safe_query("SELECT * FROM `settings_plugins` WHERE modulname ='".$modulname_3."'"))>0) {
      echo "<div class='alert alert-warning'><b>Plugineinträge:</b><br>".$str." Database entry already exists <br />";
      echo "".$str." Datenbank Eintrag schon vorhanden <br />";
      echo "".$str." La voce del database esiste già <br /></div>";
      echo "<hr>";
    } else {
      try {
        if(safe_query("".$table."")) {
          echo "<div class='alert alert-success'><b>Plugineinträge:</b><br>Entries for ".$str." have been successfully added to the <b>settings_plugins</b> database <br />";
          echo "Einträge für ".$str." wurden der <b>settings_plugins</b> Datenbank erfolgreich hinzugef&uuml;gt<br />";
          echo "Le voci per ".$str." sono stati aggiunti con successo al database <b>settings_plugins</b><br /></div>";
          echo "<hr>";
        } else {
          echo "<div class='alert alert-warning'>Database ".$str." entry already exists <br />";
          echo "Datenbank ".$str." Eintrag schon vorhanden <br />";
          echo "Database ".$str." La voce esiste già <br /></div>";
          echo "<hr>";
        }   
      } CATCH (EXCEPTION $x) {
        echo "<div class='alert alert-danger'><b>Plugineinträge:</b><br>Database ".$str." installation failed <br />";
        echo "Send the following line to the support team:<br />";
        echo "Invia la seguente riga al team di supporto:<br /><br /><br />";
        echo "<pre>".$x->getMessage()."</pre>";     
        echo"</div>";
      }
    }
}

#### add_insert_plugins_widget #####################

function add_insert_plugins_widget($table) {
  global $_database,$modulname,$version,$str;

    if(mysqli_num_rows(safe_query("SELECT * FROM `settings_plugins_widget` WHERE modulname ='".$modulname."'"))>0) {
      echo "<div class='alert alert-warning'><b>Plugin Widget Einträge:</b><br>".$str." Database entry already exists <br />";
      echo "".$str." Datenbank Eintrag schon vorhanden <br />";
      echo "".$str." La voce del database esiste già <br /></div>";
      echo "<hr>";
    } else {
      try {
        if(safe_query("".$table."")) {
          echo "<div class='alert alert-success'><b>Plugin Widget Einträge:</b><br>Entries for ".$str." have been successfully added to the <b>settings_plugins_widget</b> database <br />";
          echo "Einträge für ".$str." wurden der <b>settings_plugins_widget</b> Datenbank erfolgreich hinzugef&uuml;gt<br />";
          echo "Le voci per ".$str." sono stati aggiunti con successo al database <b>settings_plugins_widget</b><br /></div>";
          echo "<hr>";
        } else {
          echo "<div class='alert alert-warning'>Database ".$str." entry already exists <br />";
          echo "Datenbank ".$str." Eintrag schon vorhanden <br />";
          echo "Database ".$str." La voce esiste già <br /></div>";
          echo "<hr>";
        }   
      } CATCH (EXCEPTION $x) {
        echo "<div class='alert alert-danger'><b>Plugin Widget Einträge:</b><br>Database ".$str." installation failed <br />";
        echo "Send the following line to the support team:<br />";
        echo "Invia la seguente riga al team di supporto:<br /><br /><br />";
        echo "<pre>".$x->getMessage()."</pre>";     
        echo"</div>";
      }
    }
}



#### aadd_insert_navi_dashboard #####################

function add_insert_navi_dashboard($table) {
  global $_database,$modulname,$version,$str;

    if(mysqli_num_rows(safe_query("SELECT * FROM `navigation_dashboard_links` WHERE modulname ='".$modulname."'"))>0) {
      echo "<div class='alert alert-warning'><b>Dashboard Navigation:</b><br>".$str." Dashboard Navigation entry already exists <br />";
      echo "".$str." Dashboard Navigationseintrag schon vorhanden <br />";
      echo "".$str." La voce di Navigazione della Dashboard esiste già <br /></div>";
    } else {
      try {
        if(safe_query("".$table."")) {
          echo "<div class='alert alert-success'><b>Dashboard Navigation:</b><br>".$str." added to the Dashboard Navigation <br />";
          echo "".$str." wurde der Dashboard Navigation hinzugef&uuml;gt <br />";
          echo "".$str." è stato aggiunto alla Navigazione della Dashboard <br />";
          echo "<a href = '/admin/admincenter.php?site=dashboard_navigation' target='_blank'><b>LINK => Dashboard Navigation</b></a></div>";
        } else {
          echo "<div class='alert alert-danger'><b>Dashboard Navigation:</b><br>Add to Dashboard Navigation failed <br />";
          echo "Zur Dashboard Navigation hinzuf&uuml;gen fehlgeschlagen<br />";
          echo "Aggiunta alla Navigazione della Dashboard non riuscita<br /></div>";
        }   
      } CATCH (EXCEPTION $x) {
        echo "<div class='alert alert-danger'><b>Dashboard Navigation:</b><br>".$str." installation failed <br />";
        echo "Send the following line to the support team:<br />";
        echo "Invia la seguente riga al team di supporto:<br /><br /><br />";
        echo "<pre>".$x->getMessage()."</pre>";     
        echo"</div>";
      }
    }
}

function add_insert_navi_dashboard_2($table) {
  global $_database,$modulname_2,$version,$str;

    if(mysqli_num_rows(safe_query("SELECT * FROM `navigation_dashboard_links` WHERE modulname ='".$modulname_2."'"))>0) {
      echo "<div class='alert alert-warning'><b>Dashboard Navigation:</b><br>".$str." Dashboard Navigation entry already exists <br />";
      echo "".$str." Dashboard Navigationseintrag schon vorhanden <br />";
      echo "".$str." La voce di Navigazione della Dashboard esiste già <br /></div>";
    } else {
      try {
        if(safe_query("".$table."")) {
          echo "<div class='alert alert-success'><b>Dashboard Navigation:</b><br>".$str." added to the Dashboard Navigation <br />";
          echo "".$str." wurde der Dashboard Navigation hinzugef&uuml;gt <br />";
          echo "".$str." è stato aggiunto alla Navigazione della Dashboard <br />";
          echo "<a href = '/admin/admincenter.php?site=dashboard_navigation' target='_blank'><b>LINK => Dashboard Navigation</b></a></div>";
        } else {
          echo "<div class='alert alert-danger'><b>Dashboard Navigation:</b><br>Add to Dashboard Navigation failed <br />";
          echo "Zur Dashboard Navigation hinzuf&uuml;gen fehlgeschlagen<br />";
          echo "Aggiunta alla Navigazione della Dashboard non riuscita<br /></div>";
        }   
      } CATCH (EXCEPTION $x) {
        echo "<div class='alert alert-danger'><b>Dashboard Navigation:</b><br>".$str." installation failed <br />";
        echo "Send the following line to the support team:<br />";
        echo "Invia la seguente riga al team di supporto:<br /><br /><br />";
        echo "<pre>".$x->getMessage()."</pre>";     
        echo"</div>";
      }
    }
}

#### add_insert_navigation #####################

function add_insert_navigation($table) {
  global $_database,$modulname,$version,$str;

    if(mysqli_num_rows(safe_query("SELECT * FROM `navigation_website_sub` WHERE modulname ='".$modulname."'"))>0) {
      echo "<div class='alert alert-warning'><b>Website Navigation:</b><br>".$str." Navigation entry already exists <br />";
      echo "".$str." Navigationseintrag schon vorhanden <br />";
      echo "".$str." La voce di navigazione esiste già <br /></div>";
    } else {
      try {
        if(safe_query("".$table."")) {
          echo "<div class='alert alert-success'><b>Website Navigation:</b><br>".$str." added to the Website Navigation <br />";
          echo "".$str." wurde der Website Navigation hinzugef&uuml;gt <br />";
          echo "".$str." è stato aggiunto alla navigazione del sito <br />";
          echo "<a href = '/admin/admincenter.php?site=webside_navigation' target='_blank'><b>LINK => Website Navigation</b></a></div>";
        } else {
          echo "<div class='alert alert-danger'><b>Website Navigation:</b><br>Add to Website Navigation failed <br />";
          echo "Zur Website Navigation hinzuf&uuml;gen fehlgeschlagen<br />";
          echo "Aggiunta alla navigazione del sito non riuscita<br /></div>";
        }   
      } CATCH (EXCEPTION $x) {
        echo "<div class='alert alert-danger'><b>Website Navigation:</b><br>".$str." installation failed <br />";
        echo "Send the following line to the support team:<br />";
        echo "Invia la seguente riga al team di supporto:<br /><br /><br />";
        echo "<pre>".$x->getMessage()."</pre>";     
        echo"</div>";
      }
    }
}

function add_insert_navigation_2($table) {
  global $_database,$modulname_2,$version,$str;

    if(mysqli_num_rows(safe_query("SELECT * FROM `navigation_website_sub` WHERE modulname ='".$modulname_2."'"))>0) {
      echo "<div class='alert alert-warning'><b>Website Navigation:</b><br>".$str." Navigation entry already exists <br />";
      echo "".$str." Navigationseintrag schon vorhanden <br />";
      echo "".$str." La voce di navigazione esiste già <br /></div>";
    } else {
      try {
        if(safe_query("".$table."")) {
          echo "<div class='alert alert-success'><b>Website Navigation:</b><br>".$str." added to the Website Navigation <br />";
          echo "".$str." wurde der Website Navigation hinzugef&uuml;gt <br />";
          echo "".$str." è stato aggiunto alla navigazione del sito <br />";
          echo "<a href = '/admin/admincenter.php?site=webside_navigation' target='_blank'><b>LINK => Website Navigation</b></a></div>";
        } else {
          echo "<div class='alert alert-danger'><b>Website Navigation:</b><br>Add to Website Navigation failed <br />";
          echo "Zur Website Navigation hinzuf&uuml;gen fehlgeschlagen<br />";
          echo "Aggiunta alla navigazione del sito non riuscita<br /></div>";
        }   
      } CATCH (EXCEPTION $x) {
        echo "<div class='alert alert-danger'><b>Website Navigation:</b><br>".$str." installation failed <br />";
        echo "Send the following line to the support team:<br />";
        echo "Invia la seguente riga al team di supporto:<br /><br /><br />";
        echo "<pre>".$x->getMessage()."</pre>";     
        echo"</div>";
      }
    }
}

