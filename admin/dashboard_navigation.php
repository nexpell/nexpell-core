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
$languageService->readModule('dashnavi', true);

use nexpell\AccessControl;
// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_dashboard_navigation');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$ergebnis = safe_query("SELECT * FROM navigation_dashboard_links WHERE modulname='ac_dashnavi'");
    while ($db=mysqli_fetch_array($ergebnis)) {
      $accesslevel = 'is'.$db['accesslevel'].'admin';

if (!$accesslevel($userID) || mb_substr(basename($_SERVER[ 'REQUEST_URI' ]), 0, 15) != "admincenter.php") {
    die($languageService->get('access_denied'));
}
}

if (isset($_POST[ 'sortieren' ])) {
    if(isset($_POST[ 'sortcat' ])) { $sortcat = $_POST[ 'sortcat' ]; } else { $sortcat="";}
    $sortlinks = $_POST[ 'sortlinks' ];

    if (is_array($sortcat) AND !empty($sortcat)) {
        foreach ($sortcat as $sortstring) {
            $sorter = explode("-", $sortstring);
            safe_query("UPDATE navigation_dashboard_categories SET sort='$sorter[1]' WHERE catID='$sorter[0]' ");
        }
    }
    if (is_array($sortlinks)) {
        foreach ($sortlinks as $sortstring) {
            $sorter = explode("-", $sortstring);
            safe_query("UPDATE navigation_dashboard_links SET sort='$sorter[1]' WHERE linkID='$sorter[0]' ");
        }
    }
}

if (isset($_GET[ 'action' ])) {
    $action = $_GET[ 'action' ];
} else {
    $action = '';
}



if (isset($_POST['saveedit'])) {
    if ($_POST['captcha_hash'] != $_SESSION['captcha_hash']) {
        die('<div class="alert alert-danger" role="alert">Fehler: Ungültiges Captcha.</div>');        
        redirect("admincenter.php?site=dashboard_navigation", "", 3);
    }

    $catID = (int)$_POST['catID'];
    $name = mysqli_real_escape_string($_database, $_POST['name']);
    $url = mysqli_real_escape_string($_database, $_POST['url']);
    $modulname = mysqli_real_escape_string($_database, $_POST['modulname']);
    $linkID = (int)$_POST['linkID'];

    // Link updaten
    $query = "UPDATE navigation_dashboard_links SET catID = ?, name = ?, url = ?, modulname = ? WHERE linkID = ?";
    $stmt = $_database->prepare($query);
    $stmt->bind_param("isssi", $catID, $name, $url, $modulname, $linkID);
    
    if ($stmt->execute()) {
        // Rechte-Tabelle updaten oder einfügen
        $type = 'link';
        $roleID = 1; // Admin-Rolle

        $access_query = "
            INSERT INTO user_role_admin_navi_rights (roleID, type, modulname, accessID) 
            VALUES (?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE accessID = VALUES(accessID)
        ";
        $stmt_access = $_database->prepare($access_query);
        $stmt_access->bind_param("isss", $roleID, $type, $modulname, $linkID);

        if ($stmt_access->execute()) {
            echo '<div class="alert alert-success" role="alert">Link und Zugriffsrechte erfolgreich aktualisiert!</div>';
            redirect("admincenter.php?site=dashboard_navigation", "", 3);
        } else {
            echo '<div class="alert alert-danger" role="alert">Fehler beim Aktualisieren der Zugriffsrechte: ' . $_database->error . '</div>';
            redirect("admincenter.php?site=dashboard_navigation", "", 3);
        }

        $stmt_access->close();
    } else {
        echo '<div class="alert alert-warning" role="alert">Fehler beim Speichern des Links oder keine Änderungen.</div>';
        redirect("admincenter.php?site=dashboard_navigation", "", 3);
    }

    $stmt->close();
}


if (isset($_POST[ 'save' ])) {


    // Überprüfen des Captchas
    if ($_POST['captcha_hash'] != $_SESSION['captcha_hash']) {
        die('<div class="alert alert-danger" role="alert">Fehler: Ungültiges Captcha.</div>');        
        redirect("admincenter.php?site=dashboard_navigation", "", 3);
    }  

    // Eingabewerte validieren und schützen
    $catID = isset($_POST['catID']) ? mysqli_real_escape_string($_database, $_POST['catID']) : '';
    $name = isset($_POST['name']) ? mysqli_real_escape_string($_database, $_POST['name']) : '';
    $url = isset($_POST['url']) ? mysqli_real_escape_string($_database, $_POST['url']) : '';
    $modulname = isset($_POST['modulname']) ? mysqli_real_escape_string($_database, $_POST['modulname']) : '';

    // Überprüfen, ob alle Felder ausgefüllt sind
    if (empty($catID) || empty($name) || empty($url) || empty($modulname)) {
        echo '<div class="alert alert-danger" role="alert">Bitte füllen Sie alle Felder aus.</div>';
    } else {
        // SQL-Injection verhindern und prepared statements verwenden
        $stmt = $_database->prepare("INSERT INTO navigation_dashboard_links (catID, name, url, modulname, sort) VALUES (?, ?, ?, ?, ?)");
        $sort = 1;
       
        $stmt->bind_param("ssssi", $catID, $name, $url, $modulname, $sort);
        
        if ($stmt->execute()) {
            
            $catID = mysqli_insert_id($_database);

            $type = 'link';

            $roleID = 1; // Admin-Rolle

            $access_query = "INSERT INTO user_role_admin_navi_rights (roleID, type, modulname, accessID) VALUES (?, ?, ?, ?)";

            $stmt_access = $_database->prepare($access_query);

            $stmt_access->bind_param("isss", $roleID, $type, $modulname, $catID);

            if ($stmt_access->execute()) {
                echo '<div class="alert alert-success" role="alert">Kategorie erfolgreich hinzugefügt und Zugriffsrechte gesetzt!</div>';
            redirect("admincenter.php?site=dashboard_navigation", "", 3);
            return false;
            } else {
                echo '<div class="alert alert-danger" role="alert">Fehler beim Hinzufügen der Zugriffsrechte: ' . mysqli_error($_database) . '</div>';
            redirect("admincenter.php?site=dashboard_navigation", "", 3);
            return false;
            }

            $stmt_access->close();
        } else {
            // Fehler bei der SQL-Abfrage
            echo '<div class="alert alert-danger" role="alert">Fehler beim Hinzufügen der Kategorie: ' . mysqli_error($_database) . '</div>';
            redirect("admincenter.php?site=dashboard_navigation", "", 3);
            return false;
        }

        $stmt->close();
    }

}    




if ($action == "add") {
   

    $ergebnis = safe_query("SELECT * FROM navigation_dashboard_categories ORDER BY sort");
    $cats = '<select class="form-select" name="catID">';
    while ($ds = mysqli_fetch_array($ergebnis)) {
         $name = $ds['name'];
    $translate = new multiLanguage($lang);
    $translate->detectLanguages($name);
    $name = $translate->getTextByLanguage($name);
    
    $data_array = array();
    $data_array['$name'] = $ds['name'];


        
        $cats .= '<option value="' . $ds[ 'catID' ] . '">' . $name . '</option>';
    }
    $cats .= '</select>';

    

    // Captcha erstellen
$CAPCLASS = new \nexpell\Captcha;
$CAPCLASS->createTransaction();
$_SESSION['captcha_hash'] = $CAPCLASS->getHash();
$hash = $_SESSION['captcha_hash'];


     echo '<div class="card">
        <div class="card-header"><i class="bi bi-menu-app"></i> 
            ' . $languageService->get('dashnavi') . '
        </div>
            
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="admincenter.php?site=dashboard_navigation">' . $languageService->get('dashnavi') . '</a></li>
    <li class="breadcrumb-item active" aria-current="page">' . $languageService->get('add_link') . '</li>
  </ol>
</nav>
     <div class="card-body">';

    echo '<form class="form-horizontal" method="post">
    <div class="mb-3 row">
    <label class="col-md-2 control-label">'.$languageService->get('category').':</label>
    <div class="col-md-8"><span class="text-muted small"><em>
      ' . $cats . '</em></span>
    </div>
    </div>
 <div class="mb-3 row">
    <label class="col-md-2 control-label"></label>
    <div class="col-md-8">'.$languageService->get('info').'</div>
  </div> 


    <div class="mb-3 row">
    <label class="col-md-2 control-label">'.$languageService->get('name').':</label>
    <div class="col-md-8"><span class="text-muted small"><em>
        <input class="form-control" type="text" name="name" size="60"></em></span>
    </div>
  </div>
  <div class="mb-3 row">
    <label class="col-md-2 control-label">'.$languageService->get('url').':</label>
    <div class="col-md-8"><span class="text-muted small"><em>
        <input class="form-control" type="text" name="url" size="60"></em></span>
    </div>
  </div>
  <div class="mb-3 row">
    <label class="col-md-2 control-label">' . $languageService->get('modulname') . ':</label>
    <div class="col-md-8"><span class="text-muted small"><em>
        <input class="form-control" type="text" name="modulname" size="60"></em></span>
    </div>
  </div>
  <div class="mb-3 row">
    <div class="col-md-offset-2 col-md-10">
      <input type="hidden" name="captcha_hash" value="' . $hash . '"><button class="btn btn-success btn-sm" type="submit" name="save"><i class="bi bi-box-arrow-down"></i> ' . $languageService->get( 'add_link') . '</button>
    </div>
  </div>
   
          </form></div></div>';











} elseif ($action == "edit") {







// Holen der Link-Daten aus der URL
$linkID = $_GET['linkID'];
$ergebnis = safe_query("SELECT * FROM navigation_dashboard_links WHERE linkID='$linkID'");
$ds = mysqli_fetch_array($ergebnis);

// Holen der Kategorien aus der DB
$category = safe_query("SELECT * FROM navigation_dashboard_categories ORDER BY sort");
$cats = '<select class="form-select" name="catID">';
while ($dc = mysqli_fetch_array($category)) {
    // Übersetzen des Kategoriebeschreibung
    $name = $dc['name'];
    $translate = new multiLanguage($lang);
    $translate->detectLanguages($name);
    $name = $translate->getTextByLanguage($name);

    // Überprüfen, ob die Kategorie ausgewählt ist
    $selected = ($ds['catID'] == $dc['catID']) ? " selected=\"selected\"" : "";

    // Hinzufügen der Option zur Select-Liste
    $cats .= '<option value="' . $dc['catID'] . '"' . $selected . '>' . $name . '</option>';
}
$cats .= '</select>';

// Captcha erstellen
$CAPCLASS = new \nexpell\Captcha;
$CAPCLASS->createTransaction();
$_SESSION['captcha_hash'] = $CAPCLASS->getHash();
$hash = $_SESSION['captcha_hash'];

// Ausgabe des Formulars
echo '<div class="card">
        <div class="card-header"><i class="bi bi-menu-app"></i> ' . $languageService->get('dashnavi') . '</div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admincenter.php?site=dashboard_navigation">' . $languageService->get('dashnavi') . '</a></li>
            <li class="breadcrumb-item active" aria-current="page">' . $languageService->get('edit_link') . '</li>
          </ol>
        </nav>
        <div class="card-body">
        <form class="form-horizontal" method="post">
            <div class="mb-3 row">
                <label class="col-md-2 control-label">' . $languageService->get('category') . ':</label>
                <div class="col-md-8"><span class="text-muted small"><em>' . $cats . '</em></span></div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-2 control-label">' . $languageService->get('name') . ':</label>
                <div class="col-md-8">' . $languageService->get('info') . ' <span class="text-muted small"><em>
                    <input class="form-control" type="text" name="name" value="' . htmlspecialchars($ds['name']) . '" size="60"></em></span>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-2 control-label">' . $languageService->get('url') . ':</label>
                <div class="col-md-8"><span class="text-muted small"><em>
                    <input class="form-control" type="text" name="url" value="' . htmlspecialchars($ds['url']) . '" size="60"></em></span>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-2 control-label">' . $languageService->get('modulname') . ':</label>
                <div class="col-md-8"><span class="text-muted small"><em>
                    <input class="form-control" type="text" name="modulname" value="' . htmlspecialchars($ds['modulname']) . '" size="60"></em></span>
                </div>
            </div>

            <div class="mb-3 row">
                <div class="col-md-offset-2 col-md-10">
                    <input type="hidden" name="captcha_hash" value="' . $hash . '" />
                    <input type="hidden" name="linkID" value="' . $linkID . '">
                    <button class="btn btn-warning btn-sm" type="submit" name="saveedit">
                        <i class="bi bi-box-arrow-down"></i> ' . $languageService->get('edit_link') . '
                    </button>
                </div>
            </div>
        </form>
        </div>
    </div>';















} elseif ($action == "addcat") {


if (isset($_POST['savecat'])) {
    if ($_POST['captcha_hash'] != $_SESSION['captcha_hash']) {
        echo '<div class="alert alert-danger" role="alert">Fehler: Ungültiges Captcha.</div>';
        redirect("admincenter.php?site=dashboard_navigation", "", 3);
        die();
    }

    // Eingabewerte validieren und schützen
    $fa_name = isset($_POST['fa_name']) ? mysqli_real_escape_string($_database, $_POST['fa_name']) : '';
    $name = isset($_POST['name']) ? mysqli_real_escape_string($_database, $_POST['name']) : '';
    $modulname = isset($_POST['modulname']) ? mysqli_real_escape_string($_database, $_POST['modulname']) : '';

    // Überprüfen, ob alle Felder ausgefüllt sind
    if (empty($fa_name) || empty($name) || empty($modulname)) {
        echo '<div class="alert alert-danger" role="alert">Bitte füllen Sie alle Felder aus.</div>';
    } else {
        // SQL-Injection verhindern und prepared statements verwenden
        $stmt = $_database->prepare("INSERT INTO navigation_dashboard_categories (fa_name, name, modulname, sort_art, sort) VALUES (?, ?, ?, ?, ?)");
        $sort_art = 0;
        $sort = 1;
       
        $stmt->bind_param("ssssi", $fa_name, $name, $modulname, $sort_art, $sort);
        
        if ($stmt->execute()) {
            
            $catID = mysqli_insert_id($_database);

            $type = 'category';

            $roleID = 1; // Admin-Rolle

            $access_query = "INSERT INTO user_role_admin_navi_rights (roleID, type, modulname, accessID) VALUES (?, ?, ?, ?)";

            $stmt_access = $_database->prepare($access_query);

            $stmt_access->bind_param("isss", $roleID, $type, $modulname, $catID);

            if ($stmt_access->execute()) {
                echo '<div class="alert alert-success" role="alert">Kategorie erfolgreich hinzugefügt und Zugriffsrechte gesetzt!</div>';
            redirect("admincenter.php?site=dashboard_navigation", "", 3);
            return false;
            } else {
                echo '<div class="alert alert-danger" role="alert">Fehler beim Hinzufügen der Zugriffsrechte: ' . mysqli_error($_database) . '</div>';
            redirect("admincenter.php?site=dashboard_navigation", "", 3);
            return false;
            }

            $stmt_access->close();
        } else {
            // Fehler bei der SQL-Abfrage
            echo '<div class="alert alert-danger" role="alert">Fehler beim Hinzufügen der Kategorie: ' . mysqli_error($_database) . '</div>';
            redirect("admincenter.php?site=dashboard_navigation", "", 3);
            return false;
        }

        $stmt->close();
    }
}


// Captcha-Instanz erstellen
$CAPCLASS = new \nexpell\Captcha;
$CAPCLASS->createTransaction();
$_SESSION['captcha_hash'] = $CAPCLASS->getHash();
$hash = $_SESSION['captcha_hash'];

echo '<div class="card">
        <div class="card-header"><i class="bi bi-menu-app"></i> 
            ' . $languageService->get('dashnavi') . '
        </div>
            
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="admincenter.php?site=dashboard_navigation">' . $languageService->get('dashnavi') . '</a></li>
    <li class="breadcrumb-item active" aria-current="page">' . $languageService->get('add_category') . '</li>
  </ol>
</nav>
     <div class="card-body">';

echo '<form class="form-horizontal" method="post">
    <div class="mb-3 row">
    <label class="col-md-2 control-label">'.$languageService->get('fa_name').':</label>
    <div class="col-md-8"><span class="text-muted small"><em>
      <input class="form-control" type="text" name="fa_name" size="60"></em></span>
    </div>
  </div>
  <div class="mb-3 row">
    <label class="col-md-2 control-label">'.$languageService->get('name').':</label>
    <div class="col-md-8"><span class="text-muted small"><em>
      <input class="form-control" type="text" name="name" size="60"></em></span>
    </div>
  </div>
  <div class="mb-3 row">
    <label class="col-md-2 control-label">modulname:</label>
    <div class="col-md-8"><span class="text-muted small"><em>
      <input class="form-control" type="text" name="modulname" size="60"></em></span>
    </div>
  </div>
  
  <div class="mb-3 row">
    <div class="col-md-offset-2 col-md-10">
      <input type="hidden" name="captcha_hash" value="' . $hash . '" />
      <button class="btn btn-success btn-sm" type="submit" name="savecat"><i class="bi bi-box-arrow-down"></i> ' . $languageService->get('add_category') . '</button>
    </div>
  </div>

</form>
</div></div>';






} elseif ($action == "editcat") {


if (isset($_POST['savecat'])) {

    if ($_POST['captcha_hash'] != $_SESSION['captcha_hash']) {
        echo '<div class="alert alert-danger" role="alert">Fehler: Ungültiges Captcha.</div>';
        redirect("admincenter.php?site=dashboard_navigation", "", 3);
        die();
    }

    // Eingabewerte validieren und schützen
    $fa_name = isset($_POST['fa_name']) ? $_POST['fa_name'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : '';

    if (empty($fa_name) || empty($name)) {
        echo '<div class="alert alert-danger" role="alert">Bitte füllen Sie alle Felder aus.</div>';
    } else {
        // Sichere Eingabevalidierung und prepared statements verwenden
        if (isset($_database) && $_database) {
            $fa_name = mysqli_real_escape_string($_database, $fa_name);
            $name = mysqli_real_escape_string($_database, $name);

            // UPDATE-Query vorbereiten
            $updateQuery = "UPDATE navigation_dashboard_categories 
                            SET fa_name = ?, name = ? 
                            WHERE catID = ?";

            // Prepared statement verwenden
            if ($stmt = $_database->prepare($updateQuery)) {
                $stmt->bind_param("ssi", $fa_name, $name, $_POST['catID']);

                // Ausführen und prüfen, ob das Update erfolgreich war
                if ($stmt->execute()) {
                    echo '<div class="alert alert-success" role="alert">Kategorie erfolgreich bearbeitet!</div>';
                    redirect("admincenter.php?site=dashboard_navigation", "", 3);
                } else {
                    echo '<div class="alert alert-danger" role="alert">Fehler beim Bearbeiten der Kategorie.</div>';
                }
                $stmt->close();
            } else {
                echo '<div class="alert alert-danger" role="alert">Fehler bei der Vorbereitung der SQL-Abfrage.</div>';
            }
        } else {
            die('Fehler: Datenbankverbindung nicht verfügbar!');
        }
    }
}

// Abrufen der aktuellen Daten zur Bearbeitung
$catID = isset($_GET['catID']) ? $_GET['catID'] : 0;
$ergebnis = safe_query("SELECT * FROM navigation_dashboard_categories WHERE catID = '$catID'");
$ds = mysqli_fetch_array($ergebnis);

// Sicherstellen, dass Daten gefunden wurden
if (!$ds) {
    die('Fehler: Kategorie nicht gefunden!');
}

// Captcha-Instanz erstellen
$CAPCLASS = new \nexpell\Captcha;
$CAPCLASS->createTransaction();
$_SESSION['captcha_hash'] = $CAPCLASS->getHash();
$hash = $_SESSION['captcha_hash'];

echo '<div class="card">
        <div class="card-header"><i class="bi bi-menu-app"></i> 
            ' . $languageService->get('dashnavi') . '
        </div>
            
<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="admincenter.php?site=dashboard_navigation">' . $languageService->get('dashnavi') . '</a></li>
    <li class="breadcrumb-item active" aria-current="page">' . $languageService->get('edit_category') . '</li>
  </ol>
</nav>
     <div class="card-body">';
// Formular zur Bearbeitung der Kategorie
echo '<form class="form-horizontal" method="post">
        <div class="mb-3 row">
          <label class="col-md-2 control-label">' . $languageService->get('fa_name') . ':</label>
          <div class="col-md-8"><span class="text-muted small"><em>
            <input class="form-control" type="text" name="fa_name" value="' . htmlspecialchars($ds['fa_name']) . '" size="60"></em></span>
          </div>
        </div>

        <div class="mb-3 row">
          <label class="col-md-2 control-label">' . $languageService->get('name') . ':</label>
          <div class="col-md-8"><span class="text-muted small"><em>
            <input class="form-control" type="text" name="name" value="' . htmlspecialchars($ds['name']) . '" size="60"></em></span>
          </div>
        </div>

        <div class="mb-3 row">
          <div class="col-md-offset-2 col-md-10">
            <input type="hidden" name="captcha_hash" value="' . $hash . '" />
            <input type="hidden" name="catID" value="' . $catID . '">
            <button class="btn btn-warning btn-sm" type="submit" name="savecat">
              <i class="bi bi-box-arrow-down"></i> ' . $languageService->get('edit_category') . '
            </button>
          </div>
        </div>
      </form>';
echo '</div></div>';













} else {






if (isset($_GET['delete'])) {

    // Überprüfen des Captchas
    #if ($_POST['captcha_hash'] != $_SESSION['captcha_hash']) {
    #    echo '<div class="alert alert-danger" role="alert">Fehler: Ungültiges Captcha.</div>';
    #    redirect("admincenter.php?site=dashboard_navigation", "", 3);
    #    die();
    #}

    // Validierung der linkID (nur ganze Zahlen erlauben)
    $linkID = isset($_GET['linkID']) ? (int) $_GET['linkID'] : 0;
    if ($linkID <= 0) {
        echo '<div class="alert alert-danger" role="alert">Ungültige Link-ID.</div>';
        redirect("admincenter.php?site=dashboard_navigation", "", 3);
        die();
    }

    // Löschen des Links und der zugehörigen Rechte mit Prepared Statements
    $stmt = $_database->prepare("DELETE FROM navigation_dashboard_links WHERE linkID = ?");
    $stmt->bind_param("i", $linkID); // "i" für Integer
    $stmt->execute();
    $stmt->close();

    $stmt = $_database->prepare("DELETE FROM user_role_admin_navi_rights WHERE accessID = ?");
    $stmt->bind_param("i", $linkID); // "i" für Integer
    $stmt->execute();
    $stmt->close();

    // Erfolgreiche Nachricht
    echo '<div class="alert alert-success" role="alert">Link erfolgreich gelöscht!</div>';
    redirect("admincenter.php?site=dashboard_navigation", "", 3);





} elseif (isset($_GET['delcat'])) {

    // Überprüfen des Captchas
    #if ($_POST['captcha_hash'] != $_SESSION['captcha_hash']) {
    #    echo '<div class="alert alert-danger" role="alert">Fehler: Ungültiges Captcha.</div>';
    #    redirect("admincenter.php?site=dashboard_navigation", "", 3);
    #    die();
    #}

    // Validierung der catID (nur ganze Zahlen erlauben)
    $catID = isset($_GET['catID']) ? (int) $_GET['catID'] : 0;
    if ($catID <= 0) {
        echo '<div class="alert alert-danger" role="alert">Ungültige Kategoriedaten.</div>';
        redirect("admincenter.php?site=dashboard_navigation", "", 3);
        die();
    }

    // Update und Löschen von Einträgen mit Prepared Statements
    // Zuerst die catID in der navigation_dashboard_links auf 0 setzen
    $stmt = $_database->prepare("UPDATE navigation_dashboard_links SET catID = ? WHERE catID = ?");
    $newCatID = 0;
    $stmt->bind_param("ii", $newCatID, $catID);
    $stmt->execute();
    $stmt->close();

    // Löschen der Kategorie aus navigation_dashboard_categories
    $stmt = $_database->prepare("DELETE FROM navigation_dashboard_categories WHERE catID = ?");
    $stmt->bind_param("i", $catID);
    $stmt->execute();
    $stmt->close();

    // Löschen der zugehörigen Rechte aus user_role_admin_navi_rights
    $stmt = $_database->prepare("DELETE FROM user_role_admin_navi_rights WHERE accessID = ?");
    $stmt->bind_param("i", $catID);
    $stmt->execute();
    $stmt->close();

    // Erfolgreiche Nachricht
    echo '<div class="alert alert-success" role="alert">Kategorie erfolgreich gelöscht!</div>';
    redirect("admincenter.php?site=dashboard_navigation", "", 3);
}









echo '<div class="card">
    <div class="card-header"><i class="bi bi-menu-app"></i>
        ' . $languageService->get('dashnavi') . '
    </div>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">' . $languageService->get('dashnavi') . '</li>
        </ol>
    </nav>

    <div class="card-body">
        <div class="mb-3 row">
            <label class="col-md-1 control-label">' . $languageService->get('options') . ':</label>
            <div class="col-md-8">
                <a class="btn btn-primary btn-sm" href="admincenter.php?site=dashboard_navigation&amp;action=addcat" class="input"><i class="bi bi-plus-circle"></i> ' .
                    $languageService->get('new_category') . '</a>
                <a class="btn btn-primary btn-sm" href="admincenter.php?site=dashboard_navigation&amp;action=add" class="input"><i class="bi bi-plus-circle"></i> ' .
                    $languageService->get('new_link') . '</a>
            </div>
        </div>';

echo '<form method="post" action="admincenter.php?site=dashboard_navigation">
    <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
            <tr>
                <th width="25%"><b>' . $languageService->get('name') . '</b></th>
                <th width="25%"><b>Link</b></th>
                <th width="17%" align="center"><b>' . $languageService->get('modulname') . '</b></th>
                <th width="17%" align="center"><b>' . $languageService->get('actions') . '</b></th>
                <th width="8%"><b>' . $languageService->get('sort') . '</b></th>
            </tr>
        </thead>';

$ergebnis = safe_query("SELECT * FROM navigation_dashboard_categories ORDER BY sort");
$tmp = mysqli_fetch_assoc(safe_query("SELECT count(catID) as cnt FROM navigation_dashboard_categories"));
$anz = $tmp[ 'cnt' ];

// Captcha-Instanz erstellen
$CAPCLASS = new \nexpell\Captcha;
$CAPCLASS->createTransaction();
$_SESSION['captcha_hash'] = $CAPCLASS->getHash();
$hash = $_SESSION['captcha_hash'];

while ($ds = mysqli_fetch_array($ergebnis)) {

    $list = '<select name="sortcat[]">';
    for ($n = 1; $n <= $anz; $n++) {
        $list .= '<option value="' . $ds[ 'catID' ] . '-' . $n . '">' . $n . '</option>';
    }
    $list .= '</select>';
    $list = str_replace(
        'value="' . $ds[ 'catID' ] . '-' . $ds[ 'sort' ] . '"',
        'value="' . $ds[ 'catID' ] . '-' . $ds[ 'sort' ] . '" selected="selected"',
        $list
    );

    if ($ds[ 'sort_art' ] == 1) {
        $sort = '<b>' . $ds[ 'sort' ] . '</b>';
        $catactions = '';
        @$name = htmlspecialchars($ds[ 'name' ]);
    } else {
        $sort = $list;
        $catactions = '
            <a class="btn btn-warning btn-sm" href="admincenter.php?site=dashboard_navigation&amp;action=editcat&amp;catID=' . $ds[ 'catID' ] .
            '" class="input"><i class="bi bi-pencil-square"></i> ' . $languageService->get('edit') . '</a>

            

            <!-- Button trigger modal -->
<button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirm-delete-link" 
        data-href="admincenter.php?site=dashboard_navigation&delcat=true&catID=' .$ds['catID'] . '&captcha_hash=' .$hash . '">
    <i class="bi bi-trash3"></i> 
    ' . $languageService->get('delete') . '
</button>


<!-- Modal -->
<div class="modal fade" id="confirm-delete-link" tabindex="-1" aria-labelledby="confirm-delete-linkLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirm-delete-linkLabel"><i class="bi bi-menu-app"></i> ' . $languageService->get('dashnavi') . '</h5>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="' . $languageService->get('close') . '"></button>
            </div>
            <div class="modal-body">
                <p><i class="bi bi-trash3"></i> ' . $languageService->get('really_delete_category') . '</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> ' . $languageService->get('close') . '</button>
                <a class="btn btn-danger btn-ok btn-sm"><i class="bi bi-trash3"></i> ' . $languageService->get('delete') . '</a>
            </div>
        </div>
    </div>
</div>
<!-- Modal END -->



        ';

    ?><script>
    const deleteLinkButton = document.querySelector('.btn-ok');
    deleteLinkButton.addEventListener('click', function () {
        const href = document.querySelector('[data-bs-target="#confirm-delete-link"]').getAttribute('data-href');
        window.location.href = href;  // Leitet den Benutzer zur URL weiter, die die Löschanfrage enthält
    });
</script><?php
        $name = $ds['name'];
        $translate = new multiLanguage($lang);
        $translate->detectLanguages($name);
        $name = $translate->getTextByLanguage($name);
    }

    echo '<tr class="table-info">
        <td width="25%" class="td_head admin-nav-modal"><b>' . $name . '</b></td>
        <td width="25%" class="td_head admin-nav-modal"></td>
        <td width="20%" class="td_head"></td>
        <td width="20%" class="td_head">' . $catactions . '</td>        
        <td width="8%" class="td_head">' . $sort . '</td>
    </tr>';

    $links = safe_query("SELECT * FROM navigation_dashboard_links WHERE catID='" . $ds[ 'catID' ] . "' ORDER BY sort");
    $tmp = mysqli_fetch_assoc(safe_query("SELECT count(linkID) as cnt FROM navigation_dashboard_links WHERE catID='" . $ds[ 'catID' ] . "'"));
    $anzlinks = $tmp[ 'cnt' ];

    $i = 1;
    

    if (mysqli_num_rows($links)) {
        while ($db = mysqli_fetch_array($links)) {
            if ($i % 2) {
                $td = 'td1';
            } else {
                $td = 'td2';
            }

            $name = $db['name'];
            $translate = new multiLanguage($lang);
            $translate->detectLanguages($name);
            $name = $translate->getTextByLanguage($name);

            $linklist = '<select name="sortlinks[]">';
            for ($n = 1; $n <= $anzlinks; $n++) {
                $linklist .= '<option value="' . $db[ 'linkID' ] . '-' . $n . '">' . $n . '</option>';
            }
            $linklist .= '</select>';
            $linklist = str_replace(
                'value="' . $db[ 'linkID' ] . '-' . $db[ 'sort' ] . '"',
                'value="' . $db[ 'linkID' ] . '-' . $db[ 'sort' ] . '" selected="selected"',
                $linklist
            );

            echo '<tr>
                <td class="' . $td . '">&nbsp;-&nbsp;<b>' . $name . '</b></td>
                <td class="' . $td . '"><small>' . $db[ 'url' ] . '</small></td>
                <td class="' . $td . '"><small>' . $db[ 'modulname' ] . '</small></td>
                <td class="' . $td . '">
                    <a href="admincenter.php?site=dashboard_navigation&amp;action=edit&amp;linkID=' . $db[ 'linkID' ] .'" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i> ' . $languageService->get('edit') . '</a>

                   <!-- Button trigger modal -->
<button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirm-delete-link" data-href="admincenter.php?site=dashboard_navigation&delete=true&linkID=' . $db['linkID'] . '&captcha_hash=' . $hash . '"><i class="bi bi-trash3"></i> 
  ' . $languageService->get('delete') . '
</button>

<!-- Modal -->
<div class="modal fade" id="confirm-delete-link" tabindex="-1" aria-labelledby="confirm-delete-linkLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirm-delete-linkLabel"><i class="bi bi-menu-app"></i> ' . $languageService->get('dashnavi') . '</h5>
                <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal" aria-label="' . $languageService->get('close') . '"></button>
            </div>
            <div class="modal-body">
                <p><i class="bi bi-trash3"></i> ' . $languageService->get('really_delete_link') . '</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> ' . $languageService->get('close') . '</button>
                <a class="btn btn-danger btn-ok btn-sm"><i class="bi bi-trash3"></i> ' . $languageService->get('delete') . '</a>
            </div>
        </div>
    </div>
</div>
<!-- Modal END -->
';?>
<script>
    // Verwende Vanilla JS, um den Link für das Löschen zu setzen
    document.addEventListener('DOMContentLoaded', function () {
        var deleteModal = document.getElementById('confirm-delete-link');
        
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Der Button, der das Modal geöffnet hat
            var href = button.getAttribute('data-href'); // Die URL, die im data-href Attribut gespeichert ist
            var deleteButton = deleteModal.querySelector('.btn-ok');
            deleteButton.setAttribute('href', href); // Setze den href des Löschen-Buttons auf die URL
        });
    });
</script>
<?php

               echo' </td>
                <td class="' . $td . '">' . $linklist . '</td>
            </tr>';
            $i++;
        }
    } else {
        echo '<tr>
                <td class="td1" colspan="5">' . $languageService->get('no_additional_links_available') . '</td>
             </tr>';
    }
}

echo '  <tr>
            <td class="td_head" colspan="6" align="right">
                <button class="btn btn-primary btn-sm" type="submit" name="sortieren"><i class="bi bi-sort-numeric-up"></i>  ' . $languageService->get('to_sort') . '</button>
            </td>
        </tr>
    </table>
</form>
</div>
</div>';

}
