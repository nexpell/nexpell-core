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
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('startpage', true);

use nexpell\AccessControl;

// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_startpage');
$CAPCLASS = new \nexpell\Captcha;
$tpl = new Template();

// Wenn das Formular gesendet wurde
if (isset($_POST['submit'])) {
    $title = $_POST['title'];
    $startpage_text = $_POST['message'];
    $editor = isset($_POST['editor']) ? '1' : '0';

    // Umwandlung der Zeilenumbrüche in <br /> für die Speicherung
    $startpage_text = nl2br($startpage_text);

    
    $current_datetime = date("Y-m-d H:i:s");

    $CAPCLASS = new \nexpell\Captcha;
    if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {
        if (mysqli_num_rows(safe_query("SELECT * FROM settings_startpage"))) {
            safe_query("UPDATE settings_startpage SET date=CURRENT_TIMESTAMP, title='" . $title . "', startpage_text='" . $startpage_text . "', editor='" . $editor . "'");
        } else {
            safe_query("INSERT INTO settings_startpage (date, startpage_text, editor) VALUES (NOW(), '" . $startpage_text . "', '" . $editor . "')");
        }
        echo '<div class="alert alert-success" role="alert">' . $languageService->module['changes_successful'] . '</div>';
        echo '<script type="text/javascript">
                setTimeout(function() {
                    window.location.href = "admincenter.php?site=settings_startpage";
                }, 3000); // 3 Sekunden warten
            </script>';
    } else {
        echo $languageService->module['transaction_invalid'];
        echo '<script type="text/javascript">
                setTimeout(function() {
                    window.location.href = "admincenter.php?site=settings_startpage";
                }, 3000); // 3 Sekunden warten
            </script>';
    }
}

// Daten abrufen
$ergebnis = safe_query("SELECT * FROM settings_startpage");
$ds = mysqli_fetch_array($ergebnis);

// CAPTCHA vorbereiten
$CAPCLASS->createTransaction();
$hash = $CAPCLASS->getHash();

// Editor aktivieren, wenn Checkbox aktiviert war
  $editor_checked = '';
if (isset($ds['editor']) && $ds['editor'] == 1) {
    $editor_checked = 'checked'; // Wenn der Wert 1 ist, wird die Checkbox aktiviert
}

// Template laden
$data_array = [
    'startpage_label' => $languageService->module['startpage'],
    'title_head' => $languageService->module['title_head'],
    'title' => htmlspecialchars($ds['title']),
    'startpage_text' => htmlspecialchars($ds['startpage_text']),
    'editor_is_editor' => $languageService->module['editor_is_editor'], // Fügt die Label für "Editor anzeigen" hinzu
    'editor_checked' => $editor_checked, // Setzt den Wert für die Checkbox
    'captcha_hash' => $hash,
    'update_button_label' => $languageService->module['update']
];

echo $tpl->loadTemplate("startpage", "content", $data_array, 'admin');

?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('toggle-editor');
    const textarea = document.getElementById('ckeditor');

    // Funktion zum Editor aktivieren/deaktivieren
    function toggleEditor() {
        if (toggle.checked) {
            if (!CKEDITOR.instances['ckeditor']) {
                CKEDITOR.replace('ckeditor');
            }
        } else {
            if (CKEDITOR.instances['ckeditor']) {
                CKEDITOR.instances['ckeditor'].destroy(true);
            }
        }
    }

    // Initialer Zustand (z. B. bei Seiten-Reload)
    toggleEditor();

    // Reaktion auf Umschalten
    toggle.addEventListener('change', toggleEditor);
});
</script>