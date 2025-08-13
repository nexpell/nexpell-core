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


if (isset($_GET[ 'action' ])) {
    $action = $_GET[ 'action' ];
} else {
    $action = '';
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

if (isset($_GET['delete'])) {
    // Validierung der linkID
    $linkID = isset($_GET['linkID']) ? (int) $_GET['linkID'] : 0;
    if ($linkID <= 0) {
        echo '<div class="alert alert-danger" role="alert">Ungültige Link-ID.</div>';
        redirect("admincenter.php?site=dashboard_navigation", "", 3);
        die();
    }

    // Link und Rechte löschen
    $stmt = $_database->prepare("DELETE FROM navigation_dashboard_links WHERE linkID = ?");
    $stmt->bind_param("i", $linkID);
    $stmt->execute();
    $stmt->close();

    $stmt = $_database->prepare("DELETE FROM user_role_admin_navi_rights WHERE accessID = ?");
    $stmt->bind_param("i", $linkID);
    $stmt->execute();
    $stmt->close();

    echo '<div class="alert alert-success" role="alert">Link erfolgreich gelöscht!</div>';
    redirect("admincenter.php?site=dashboard_navigation", "", 3);

} elseif (isset($_GET['delcat'])) {
    // Validierung der catID
    $catID = isset($_GET['catID']) ? (int) $_GET['catID'] : 0;
    if ($catID <= 0) {
        echo '<div class="alert alert-danger" role="alert">Ungültige Kategoriedaten.</div>';
        redirect("admincenter.php?site=dashboard_navigation", "", 3);
        die();
    }

    // Links der Kategorie zuordnen (catID=0)
    $stmt = $_database->prepare("UPDATE navigation_dashboard_links SET catID = ? WHERE catID = ?");
    $newCatID = 0;
    $stmt->bind_param("ii", $newCatID, $catID);
    $stmt->execute();
    $stmt->close();

    // Kategorie löschen
    $stmt = $_database->prepare("DELETE FROM navigation_dashboard_categories WHERE catID = ?");
    $stmt->bind_param("i", $catID);
    $stmt->execute();
    $stmt->close();

    // Rechte für die Kategorie löschen
    $stmt = $_database->prepare("DELETE FROM user_role_admin_navi_rights WHERE accessID = ?");
    $stmt->bind_param("i", $catID);
    $stmt->execute();
    $stmt->close();

    echo '<div class="alert alert-success" role="alert">Kategorie erfolgreich gelöscht!</div>';
    redirect("admincenter.php?site=dashboard_navigation", "", 3);
}


if (isset($_POST['saveedit'])) {
    $CAPCLASS = new \nexpell\Captcha;

    // Captcha prüfen
    if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

        // Eingaben holen und validieren
        $catID      = (int)$_POST['catID'];
        $linkID     = (int)$_POST['linkID'];
        $nameArray  = $_POST['name'] ?? [];
        $url        = mysqli_real_escape_string($_database, $_POST['url'] ?? '');
        $modulname  = mysqli_real_escape_string($_database, $_POST['modulname'] ?? '');

        // Mehrsprachigen Text zusammenbauen
        $name = '';
        foreach (['de', 'en', 'it'] as $lang) {
            $text = $nameArray[$lang] ?? '';
            $name .= "[[lang:$lang]]" . $text;
        }

        // Haupt-Linkdaten aktualisieren
        $query = "UPDATE navigation_dashboard_links 
                  SET catID = ?, name = ?, url = ?, modulname = ? 
                  WHERE linkID = ?";

        if ($stmt = $_database->prepare($query)) {
            $stmt->bind_param("isssi", $catID, $name, $url, $modulname, $linkID);

            if ($stmt->execute()) {
                // Zugriffsrechte für Admin-Rolle (roleID = 1) setzen
                $roleID = 1;
                $type = 'link';

                $access_query = "
                    INSERT INTO user_role_admin_navi_rights (roleID, type, modulname)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE modulname = VALUES(modulname)
                ";

                if ($stmt_access = $_database->prepare($access_query)) {
                    $stmt_access->bind_param("iss", $roleID, $type, $modulname);
                    if ($stmt_access->execute()) {
                        echo '<div class="alert alert-success" role="alert">Link und Zugriffsrechte erfolgreich aktualisiert!</div>';
                    } else {
                        echo '<div class="alert alert-danger" role="alert">Fehler beim Aktualisieren der Zugriffsrechte: ' . $stmt_access->error . '</div>';
                    }
                    $stmt_access->close();
                } else {
                    echo '<div class="alert alert-danger" role="alert">Fehler beim Vorbereiten der Rechte-Abfrage: ' . $_database->error . '</div>';
                }

                redirect("admincenter.php?site=dashboard_navigation", "", 3);

            } else {
                echo '<div class="alert alert-warning" role="alert">Fehler beim Speichern des Links oder keine Änderungen vorgenommen.</div>';
                redirect("admincenter.php?site=dashboard_navigation", "", 3);
            }

            $stmt->close();

        } else {
            echo '<div class="alert alert-danger" role="alert">Fehler bei der SQL-Vorbereitung: ' . $_database->error . '</div>';
        }

    } else {
        echo '<div class="alert alert-danger" role="alert">Fehler: Ungültiges Captcha.</div>';
    }
}




if (isset($_POST['save'])) {

    $CAPCLASS = new \nexpell\Captcha;

    // Captcha prüfen
    if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

        // Eingabewerte holen & absichern
        $nameArray  = $_POST['name'] ?? [];
        $url        = mysqli_real_escape_string($_database, $_POST['url'] ?? '');
        $modulname  = mysqli_real_escape_string($_database, $_POST['modulname'] ?? '');
        $catID      = (int)$_POST['catID'];

        // Mehrsprachigen Namen zusammenbauen
        $name = '';
        foreach (['de', 'en', 'it'] as $lang) {
            $text = $nameArray[$lang] ?? '';
            $name .= "[[lang:$lang]]" . $text;
        }

        // Standard-Sortierung
        $sort = 1;

        // Insert vorbereiten
        $stmt = $_database->prepare("
            INSERT INTO navigation_dashboard_links (catID, name, url, modulname, sort)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssi", $catID, $name, $url, $modulname, $sort);

        if ($stmt->execute()) {
            $linkID = mysqli_insert_id($_database);  // zuletzt eingefügter Link

            // Rechte für Admin-Rolle setzen
            $roleID = 1;
            $type   = 'link';

            $access_query = "
                INSERT INTO user_role_admin_navi_rights (roleID, type, modulname, accessID)
                VALUES (?, ?, ?, ?)
            ";

            $stmt_access = $_database->prepare($access_query);
            $stmt_access->bind_param("isss", $roleID, $type, $modulname, $linkID);

            if ($stmt_access->execute()) {
                echo '<div class="alert alert-success" role="alert">Link erfolgreich hinzugefügt und Rechte gesetzt.</div>';
            } else {
                echo '<div class="alert alert-danger" role="alert">Fehler beim Setzen der Rechte: ' . $stmt_access->error . '</div>';
            }

            $stmt_access->close();
            redirect("admincenter.php?site=dashboard_navigation", "", 3);
        } else {
            echo '<div class="alert alert-danger" role="alert">Fehler beim Einfügen des Links: ' . $stmt->error . '</div>';
        }

        $stmt->close();

    } else {
        echo '<div class="alert alert-danger" role="alert">Fehler: Ungültiges Captcha.</div>';
    }
}


###################

if (isset($_POST['saveaddcat'])) {
    $CAPCLASS = new \nexpell\Captcha;

    // Captcha prüfen
    if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

        // Eingabewerte holen und escapen
        $fa_name    = trim($_POST['fa_name'] ?? '');
        $nameArray  = $_POST['name'] ?? [];
        $modulname  = trim($_POST['modulname'] ?? ''); // optional
        $sort_art   = 0;
        $sort       = 1;

        // Prüfung auf Pflichtfelder (z. B. fa_name, mindestens ein Sprachfeld)
        if (empty($fa_name) || empty($nameArray['de'])) {
            echo '<div class="alert alert-warning" role="alert">Bitte mindestens einsprachigen Kategorienamen und ein Icon angeben.</div>';
            return;
        }

        // Mehrsprachigen Namen aufbauen
        $name = '';
        foreach (['de', 'en', 'it'] as $lang) {
            $text = trim($nameArray[$lang] ?? '');
            $name .= "[[lang:$lang]]" . $text;
        }

        // SQL vorbereiten und ausführen
        $stmt = $_database->prepare("
            INSERT INTO navigation_dashboard_categories (fa_name, name, modulname, sort_art, sort)
            VALUES (?, ?, ?, ?, ?)
        ");

        if ($stmt) {
            $stmt->bind_param("ssssi", $fa_name, $name, $modulname, $sort_art, $sort);

            if ($stmt->execute()) {
                echo '<div class="alert alert-success" role="alert">Kategorie erfolgreich hinzugefügt.</div>';
                redirect("admincenter.php?site=dashboard_navigation", "", 2);
            } else {
                echo '<div class="alert alert-danger" role="alert">Fehler beim Speichern: ' . $stmt->error . '</div>';
            }

            $stmt->close();
        } else {
            echo '<div class="alert alert-danger" role="alert">Fehler bei der SQL-Vorbereitung: ' . $_database->error . '</div>';
        }

    } else {
        echo '<div class="alert alert-danger" role="alert">Fehler: Ungültiges Captcha.</div>';
    }
}



if (isset($_POST['savecat'])) {
    $CAPCLASS = new \nexpell\Captcha;

    // Captcha prüfen
    if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

        // Eingaben sichern und validieren
        $fa_name    = trim($_POST['fa_name'] ?? '');
        $catID      = (int)($_POST['catID'] ?? 0);
        $nameArray  = $_POST['name'] ?? [];

        // Validierung: Pflichtfelder prüfen
        if (empty($catID) || empty($fa_name) || empty($nameArray['de'])) {
            echo '<div class="alert alert-warning" role="alert">Bitte alle Pflichtfelder ausfüllen (Icon und deutscher Name).</div>';
            return;
        }

        // Mehrsprachigen Namen aufbauen
        $name = '';
        foreach (['de', 'en', 'it'] as $lang) {
            $text = trim($nameArray[$lang] ?? '');
            $name .= "[[lang:$lang]]" . $text;
        }

        // SQL-Update vorbereiten
        $updateQuery = "
            UPDATE navigation_dashboard_categories 
            SET fa_name = ?, name = ? 
            WHERE catID = ?
        ";

        if ($stmt = $_database->prepare($updateQuery)) {
            $stmt->bind_param("ssi", $fa_name, $name, $catID);

            if ($stmt->execute()) {
                echo '<div class="alert alert-success" role="alert">Kategorie erfolgreich bearbeitet!</div>';
                redirect("admincenter.php?site=dashboard_navigation", "", 3);
            } else {
                echo '<div class="alert alert-danger" role="alert">Fehler beim Ausführen der SQL-Abfrage: ' . htmlspecialchars($stmt->error) . '</div>';
            }

            $stmt->close();
        } else {
            echo '<div class="alert alert-danger" role="alert">Fehler bei der Vorbereitung der SQL-Abfrage: ' . htmlspecialchars($_database->error) . '</div>';
        }

    } else {
        echo '<div class="alert alert-warning" role="alert">' . htmlspecialchars($languageService->get('transaction_invalid')) . '</div>';
    }
}



if ($action == "add") {

    $result = mysqli_query($_database, "SELECT * FROM navigation_dashboard_categories ORDER BY sort");
    $cats = '<select class="form-select" name="catID">';
    while ($ds = mysqli_fetch_assoc($result)) {
        $name = $ds['name'];
        $translate = new multiLanguage($lang);
        $translate->detectLanguages($name);
        $name = $translate->getTextByLanguage($name);

        $cats .= '<option value="' . $ds['catID'] . '">' . htmlspecialchars($name) . '</option>';
    }
    $cats .= '</select>';

    function extractLangText(?string $multiLangText, string $lang): string {
        if (!$multiLangText) return '';
        if (preg_match('/\[\[lang:' . preg_quote($lang, '/') . '\]\](.*?)(?=\[\[lang:|$)/s', $multiLangText, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    // Sprachen laden
    $languages = [];
    $query = "SELECT iso_639_1, name_de FROM settings_languages WHERE active = 1 ORDER BY id ASC";
    $res = mysqli_query($_database, $query);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $languages[$row['iso_639_1']] = $row['name_de'];
        }
    } else {
        $languages = ['de' => 'Deutsch', 'en' => 'English', 'it' => 'Italiano'];
    }

    // Captcha erzeugen
    $CAPCLASS = new \nexpell\Captcha;
    $CAPCLASS->createTransaction();
    $_SESSION['captcha_hash'] = $CAPCLASS->getHash();
    $hash = $_SESSION['captcha_hash'];

    echo '<div class="card">
        <div class="card-header"><i class="bi bi-menu-app"></i> ' . $languageService->get('dashnavi') . '</div>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="admincenter.php?site=dashboard_navigation">' . $languageService->get('dashnavi') . '</a></li>
            <li class="breadcrumb-item active" aria-current="page">' . $languageService->get('add_link') . '</li>
          </ol>
        </nav>
        <div class="card-body">
            <form class="form-horizontal" method="post">
                <div class="mb-3 row">
                    <label class="col-md-2 control-label">' . $languageService->get('category') . ':</label>
                    <div class="col-md-8"><span class="text-muted small"><em>' . $cats . '</em></span></div>
                </div>

                <div class="alert alert-info" role="alert">
                    <label class="form-label"><h4>' . $languageService->get('text') . '</h4></label>';

                foreach ($languages as $code => $label) {
                    echo '
                    <div class="mb-3 row">
                        <label class="col-sm-2 col-form-label">' . htmlspecialchars($label) . ':</label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" id="text_' . htmlspecialchars($code) . '" name="name[' . htmlspecialchars($code) . ']" value="">
                        </div>
                    </div>';
                }

    echo '</div>

                <div class="mb-3 row">
                    <label class="col-md-2 control-label">' . $languageService->get('url') . ':</label>
                    <div class="col-md-8"><input class="form-control" type="text" name="url" size="60"></div>
                </div>

                <div class="mb-3 row">
                    <label class="col-md-2 control-label">' . $languageService->get('modulname') . ':</label>
                    <div class="col-md-8"><input class="form-control" type="text" name="modulname" size="60"></div>
                </div>

                <div class="mb-3 row">
                    <div class="col-md-offset-2 col-md-10">
                        <input type="hidden" name="captcha_hash" value="' . htmlspecialchars($hash) . '">
                        <button class="btn btn-success btn-sm" type="submit" name="save"><i class="bi bi-box-arrow-down"></i> ' . $languageService->get('add_link') . '</button>
                    </div>
                </div>
            </form>
        </div>
    </div>';
}
 elseif ($action === "edit") {

    // --- POST-Handler zum Speichern ---
    if (isset($_POST['saveedit'])) {
        $CAPCLASS = new \nexpell\Captcha;

        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {
            // Eingaben sicher holen
            $linkID   = (int)($_POST['linkID'] ?? 0);
            $fa_name  = $_POST['fa_name'] ?? '';        // falls benötigt
            $catID    = (int)($_POST['catID'] ?? 0);
            $url      = $_POST['url'] ?? '';
            $modulname= $_POST['modulname'] ?? '';
            $nameArray= $_POST['name'] ?? [];

            // Mehrsprachigen Text zusammenbauen
            $name = '';
            foreach (['de', 'en', 'it'] as $l) {
                $txt = $nameArray[$l] ?? '';
                $name .= "[[lang:$l]]" . $txt;
            }

            // Update Query vorbereiten
            $stmt = $_database->prepare("UPDATE navigation_dashboard_links SET catID = ?, name = ?, url = ?, modulname = ? WHERE linkID = ?");
            if ($stmt) {
                $stmt->bind_param("isssi", $catID, $name, $url, $modulname, $linkID);
                if ($stmt->execute()) {
                    echo '<div class="alert alert-success" role="alert">' . $languageService->get('edit_success') . '</div>';
                    redirect("admincenter.php?site=dashboard_navigation", "", 3);
                } else {
                    echo '<div class="alert alert-danger" role="alert">' . $languageService->get('edit_error') . '</div>';
                }
                $stmt->close();
            } else {
                echo '<div class="alert alert-danger" role="alert">Fehler bei der SQL-Vorbereitung.</div>';
            }
        } else {
            echo '<div class="alert alert-warning" role="alert">' . htmlspecialchars($languageService->get('transaction_invalid')) . '</div>';
        }
    }

    // --- Daten für Formular laden ---
    $linkID = isset($_GET['linkID']) ? (int)$_GET['linkID'] : 0;
    if ($linkID <= 0) {
        echo '<div class="alert alert-danger">Ungültige Link-ID.</div>';
        return;
    }

    $stmt = $_database->prepare("SELECT * FROM navigation_dashboard_links WHERE linkID = ?");
    $stmt->bind_param("i", $linkID);
    $stmt->execute();
    $result = $stmt->get_result();
    $ds = $result->fetch_assoc();
    $stmt->close();

    if (!$ds) {
        echo '<div class="alert alert-danger">Link nicht gefunden.</div>';
        return;
    }

    // Kategorien laden
    $category = safe_query("SELECT * FROM navigation_dashboard_categories ORDER BY sort");
    $cats = '<select class="form-select" name="catID">';
    while ($dc = mysqli_fetch_array($category)) {
        $name = $dc['name'];
        $translate = new multiLanguage($lang);
        $translate->detectLanguages($name);
        $name = $translate->getTextByLanguage($name);

        $selected = ($ds['catID'] == $dc['catID']) ? ' selected="selected"' : '';
        $cats .= '<option value="' . $dc['catID'] . '"' . $selected . '>' . htmlspecialchars($name) . '</option>';
    }
    $cats .= '</select>';

    // Hilfsfunktion zur Sprach-Extraktion
    function extractLangText(?string $multiLangText, string $lang): string {
        if (!$multiLangText) return '';
        if (preg_match('/\[\[lang:' . preg_quote($lang, '/') . '\]\](.*?)(?=\[\[lang:|$)/s', $multiLangText, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    // Sprachen aus DB laden
    $languages = [];
    $query = "SELECT iso_639_1, name_de FROM settings_languages WHERE active = 1 ORDER BY id ASC";
    $result = mysqli_query($_database, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $languages[$row['iso_639_1']] = $row['name_de'];
        }
    } else {
        $languages = ['de' => 'Deutsch', 'en' => 'English', 'it' => 'Italiano'];
    }

    // Captcha erstellen
    $CAPCLASS = new \nexpell\Captcha;
    $CAPCLASS->createTransaction();
    $_SESSION['captcha_hash'] = $CAPCLASS->getHash();
    $hash = $_SESSION['captcha_hash'];

    // Formular ausgeben
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

                <div class="alert alert-info" role="alert">
                    <label class="form-label"><h4>' . $languageService->get('text') . '</h4></label>';

                    foreach ($languages as $code => $label) {
                        echo '
                        <div class="mb-3 row">
                            <label class="col-sm-2 col-form-label">' . htmlspecialchars($label) . ':</label>
                            <div class="col-sm-8"><input class="form-control" type="text" id="text_' . htmlspecialchars($code) . '" name="name[' . htmlspecialchars($code) . ']" value="'
                                . htmlspecialchars(extractLangText($ds['name'] ?? '', $code)) .
                                '">
                            </div>
                        </div>';
                    }

            echo '</div>

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
}
 elseif ($action == "addcat") {





// Captcha-Instanz erstellen
$CAPCLASS = new \nexpell\Captcha;
$CAPCLASS->createTransaction();
$_SESSION['captcha_hash'] = $CAPCLASS->getHash();
$hash = $_SESSION['captcha_hash'];


// Hilfsfunktion zur Sprach-Extraktion
    function extractLangText(?string $multiLangText, string $lang): string {
        if (!$multiLangText) return '';
        if (preg_match('/\[\[lang:' . preg_quote($lang, '/') . '\]\](.*?)(?=\[\[lang:|$)/s', $multiLangText, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    // Sprachen aus DB
    $languages = [];
    $query = "SELECT iso_639_1, name_de FROM settings_languages WHERE active = 1 ORDER BY id ASC";
    $result = mysqli_query($_database, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $languages[$row['iso_639_1']] = $row['name_de'];
        }
    } else {
        $languages = ['de' => 'Deutsch', 'en' => 'English', 'it' => 'Italiano'];
    }

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
  <div class="alert alert-info" role="alert">
                <label class="form-label"><h4>' . $languageService->get('text') . '</h4></label>';

                foreach ($languages as $code => $label) {
                    echo '
                    <div class="mb-3 row">
                        <label class="col-sm-2 col-form-label">' . $label . ':</label>
                        <div class="col-sm-8"><input class="form-control" type="text" id="text_' . $code . '" name="name[' . $code . ']" value="'
                            . htmlspecialchars(extractLangText($ds['name'] ?? '', $code)) .
                            '">
                        </div>
                    </div>';
                }

        echo '</div>
  <div class="mb-3 row">
    <label class="col-md-2 control-label">modulname:</label>
    <div class="col-md-8"><span class="text-muted small"><em>
      <input class="form-control" type="text" name="modulname" size="60"></em></span>
    </div>
  </div>
  
  <div class="mb-3 row">
    <div class="col-md-offset-2 col-md-10">
      <input type="hidden" name="captcha_hash" value="' . $hash . '" />
      <button class="btn btn-success btn-sm" type="submit" name="saveaddcat"><i class="bi bi-box-arrow-down"></i> ' . $languageService->get('add_category') . '</button>
    </div>
  </div>

</form>
</div></div>';






} elseif ($action == "editcat") {

    // POST-Handler zum Speichern
    if (isset($_POST['savecat'])) {
        $CAPCLASS = new \nexpell\Captcha;

        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {
            // Eingaben holen und sichern
            $catID   = (int)($_POST['catID'] ?? 0);
            $fa_name = $_POST['fa_name'] ?? '';
            $modulname = $_POST['modulname'] ?? ''; // wird nicht bearbeitet, nur zur Vollständigkeit
            $nameArray = $_POST['name'] ?? [];

            // Mehrsprachigen Namen zusammensetzen
            $name = '';
            foreach (['de','en','it'] as $lang) {
                $txt = $nameArray[$lang] ?? '';
                $name .= "[[lang:$lang]]" . $txt;
            }

            // Update vorbereiten (Prepared Statement empfohlen)
            $stmt = $_database->prepare("UPDATE navigation_dashboard_categories SET fa_name = ?, name = ? WHERE catID = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $fa_name, $name, $catID);
                if ($stmt->execute()) {
                    echo '<div class="alert alert-success" role="alert">' . $languageService->get('edit_success') . '</div>';
                    redirect("admincenter.php?site=dashboard_navigation", "", 3);
                } else {
                    echo '<div class="alert alert-danger" role="alert">' . $languageService->get('edit_error') . '</div>';
                }
                $stmt->close();
            } else {
                echo '<div class="alert alert-danger" role="alert">Fehler bei der SQL-Vorbereitung.</div>';
            }

        } else {
            echo '<div class="alert alert-warning" role="alert">' . htmlspecialchars($languageService->get('transaction_invalid')) . '</div>';
        }
    }

    // --- Formularanzeige ---

    // Daten laden
    $catID = isset($_GET['catID']) ? (int)$_GET['catID'] : 0;
    $ergebnis = safe_query("SELECT * FROM navigation_dashboard_categories WHERE catID = '$catID'");
    $ds = mysqli_fetch_array($ergebnis);

    if (!$ds) {
        die('Fehler: Kategorie nicht gefunden!');
    }

    // Captcha erzeugen
    $CAPCLASS = new \nexpell\Captcha;
    $CAPCLASS->createTransaction();
    $hash = $CAPCLASS->getHash();

    // Hilfsfunktion zur Sprach-Extraktion
    function extractLangText(?string $multiLangText, string $lang): string {
        if (!$multiLangText) return '';
        if (preg_match('/\[\[lang:' . preg_quote($lang, '/') . '\]\](.*?)(?=\[\[lang:|$)/s', $multiLangText, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    // Sprachen aus DB laden
    $languages = [];
    $query = "SELECT iso_639_1, name_de FROM settings_languages WHERE active = 1 ORDER BY id ASC";
    $result = mysqli_query($_database, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $languages[$row['iso_639_1']] = $row['name_de'];
        }
    } else {
        $languages = ['de' => 'Deutsch', 'en' => 'English', 'it' => 'Italiano'];
    }

    // Formular ausgeben
    echo '<div class="card">
        <div class="card-header"><i class="bi bi-menu-app"></i> ' . $languageService->get('dashnavi') . '</div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="admincenter.php?site=dashboard_navigation">' . $languageService->get('dashnavi') . '</a></li>
                <li class="breadcrumb-item active" aria-current="page">' . $languageService->get('edit_category') . '</li>
            </ol>
        </nav>
        <div class="card-body">

        <form class="form-horizontal" method="post">
            <div class="mb-3 row">
                <label class="col-md-2 control-label">' . $languageService->get('fa_name') . ':</label>
                <div class="col-md-8">
                    <input class="form-control" type="text" name="fa_name" value="' . htmlspecialchars($ds['fa_name']) . '" size="60">
                </div>
            </div>

            <div class="alert alert-info" role="alert">
                <label class="form-label"><h4>' . $languageService->get('text') . '</h4></label>';

                foreach ($languages as $code => $label) {
                    echo '
                    <div class="mb-3 row">
                        <label class="col-sm-2 col-form-label">' . htmlspecialchars($label) . ':</label>
                        <div class="col-sm-8"><input class="form-control" type="text" id="text_' . htmlspecialchars($code) . '" name="name[' . htmlspecialchars($code) . ']" value="'
                            . htmlspecialchars(extractLangText($ds['name'] ?? '', $code)) .
                            '">
                        </div>
                    </div>';
                }

        echo '</div>
            <div class="mb-3 row">
                <label class="col-md-2 control-label">' . $languageService->get('modulname') . ':</label>
                <div class="col-md-8"><span class="text-muted small"><em>
                    <input class="form-control" type="text" name="modulname" value="' . htmlspecialchars($ds['modulname']) . '" size="60" disabled></em></span>
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
        </form>
    </div>
</div>';
}

 else {


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
        $sort = '<b>' . $ds[ 'sort_art' ] . '</b>';
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
