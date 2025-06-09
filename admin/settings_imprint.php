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
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('imprint', true);

use webspell\AccessControl;

// Admin-Zugriff überprüfen
AccessControl::checkAdminAccess('ac_imprint');

$CAPCLASS = new \webspell\Captcha;
$tpl = new Template();


// Prüfen, ob das Formular abgeschickt wurde
if (isset($_POST['submit'])) {
    // CAPTCHA-Überprüfung
    if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {

        // Formularfelder auslesen
        $type = $_POST['type'];
        $email = $_POST['email'];
        $website = $_POST['website'];
        $phone = $_POST['phone'];
        $disclaimer = $_POST['disclaimer'];

        // Typabhängige Felder vorbereiten
        $company_name = '';
        $represented_by = '';
        $tax_id = '';

        // Werte je nach Auswahl des "type"-Feldes
        if ($type == 'private') {
            $company_name = $_POST['company_name_private'];
        } elseif ($type == 'association') {
            $company_name = $_POST['company_name_association'];
            $represented_by = $_POST['represented_by_association'];
        } elseif ($type == 'small_business') {
            $company_name = $_POST['company_name_small_business'];
            $tax_id = $_POST['tax_id_small_business'];
        } elseif ($type == 'company') {
            $company_name = $_POST['company_name_company'];
            $represented_by = $_POST['represented_by_company'];
            $tax_id = $_POST['tax_id_company'];
        }

        // Überprüfen, ob bereits ein Eintrag in der Tabelle existiert
        $result = safe_query("SELECT * FROM settings_imprint");

        if (mysqli_num_rows($result)) {
            // Wenn ein Eintrag existiert, UPDATE durchführen
            $update_query = "
                UPDATE settings_imprint
                SET 
                    type = '$type', 
                    company_name = '$company_name', 
                    represented_by = '$represented_by', 
                    tax_id = '$tax_id', 
                    email = '$email', 
                    website = '$website', 
                    phone = '$phone', 
                    disclaimer = '$disclaimer'
                WHERE id = 1
            ";
            safe_query($update_query);
        } else {
            // Wenn kein Eintrag existiert, INSERT durchführen
            $insert_query = "
                INSERT INTO settings_imprint 
                    (type, company_name, represented_by, tax_id, email, website, phone, disclaimer)
                VALUES ('$type', '$company_name', '$represented_by', '$tax_id', '$email', '$website', '$phone', '$disclaimer')
            ";
            safe_query($insert_query);
        }

        echo '<div class="alert alert-success" role="alert">' . $languageService->module['changes_successful'] . '</div>';
        echo '<script type="text/javascript">
                setTimeout(function() {
                    window.location.href = "admincenter.php?site=settings_imprint";
                }, 3000); // 3 Sekunden warten
            </script>';
    } else {
        echo '<div class="alert alert-success" role="alert">' . $languageService->module['transaction_invalid'] . '</div>';
        echo '<script type="text/javascript">
                setTimeout(function() {
                    window.location.href = "admincenter.php?site=settings_imprint";
                }, 3000); // 3 Sekunden warten
            </script>';
    }
}





// Daten aus der settings_imprint-Tabelle holen
$ergebnis = safe_query("SELECT * FROM settings_imprint");
$ds = mysqli_fetch_array($ergebnis);

$CAPCLASS->createTransaction();
$hash = $CAPCLASS->getHash();

// Werte aus der Datenbank zuweisen
$company_name_private_value = $ds['company_name'];
$company_name_association_value = $ds['company_name'];
$represented_by_association_value = $ds['represented_by'];
$company_name_small_business_value = $ds['company_name'];
$tax_id_small_business_value = $ds['tax_id'];
$company_name_company_value = $ds['company_name'];
$represented_by_company_value = $ds['represented_by'];
$tax_id_company_value = $ds['tax_id'];
$email_value = $ds['email'];
$website_value = $ds['website'];
$phone_value = $ds['phone'];
$disclaimer_value = $ds['disclaimer'];
$type_value = $ds['type'];

// Daten für das Template übergeben
$data_array = [
    'private_selected'       => ($type_value === 'private') ? 'selected' : '',
    'association_selected'   => ($type_value === 'association') ? 'selected' : '',
    'small_business_selected'=> ($type_value === 'small_business') ? 'selected' : '',
    'company_selected'       => ($type_value === 'company') ? 'selected' : '',

    'company_name_private_value'       => $company_name_private_value,
    'company_name_association_value'   => $company_name_association_value,
    'represented_by_association_value' => $represented_by_association_value,
    'company_name_small_business_value'=> $company_name_small_business_value,
    'tax_id_small_business_value'      => $tax_id_small_business_value,
    'company_name_company_value'       => $company_name_company_value,
    'represented_by_company_value'     => $represented_by_company_value,
    'tax_id_company_value'             => $tax_id_company_value,
    'email_value'                      => $email_value,
    'website_value'                    => $website_value,
    'phone_value'                      => $phone_value,
    'disclaimer_value'                 => $disclaimer_value,
    'hash'                             => $hash,
    'imprint'                          => $languageService->module['imprint'],
    'private_option'                   => $languageService->module['private_option'],
    'association_option'               => $languageService->module['association_option'],
    'small_business_option'            => $languageService->module['small_business_option'],
    'company_option'                   => $languageService->module['company_option'],
    'impressum_type_label'             => $languageService->module['impressum_type_label'],
    'name_label'                       => $languageService->module['name_label'],
    'name_placeholder'                 => $languageService->module['name_placeholder'],
    'association_name_label'           => $languageService->module['association_name_label'],
    'association_name_placeholder'     => $languageService->module['association_name_placeholder'],
    'represented_by_label'             => $languageService->module['represented_by_label'],
    'represented_by_placeholder'       => $languageService->module['represented_by_placeholder'],
    'small_business_name_label'        => $languageService->module['small_business_name_label'],
    'small_business_name_placeholder'  => $languageService->module['small_business_name_placeholder'],
    'tax_id_label'                     => $languageService->module['tax_id_label'],
    'tax_id_placeholder'               => $languageService->module['tax_id_placeholder'],
    'company_name_label'               => $languageService->module['company_name_label'],
    'company_name_placeholder'         => $languageService->module['company_name_placeholder'],
    'represented_by_company_label'     => $languageService->module['represented_by_company_label'],
    'represented_by_company_placeholder'=> $languageService->module['represented_by_company_placeholder'],
    'tax_id_company_label'             => $languageService->module['tax_id_company_label'],
    'tax_id_company_placeholder'       => $languageService->module['tax_id_company_placeholder'],
    'email_label'                      => $languageService->module['email_label'],
    'email_placeholder'                => $languageService->module['email_placeholder'],
    'website_label'                    => $languageService->module['website_label'],
    'website_placeholder'              => $languageService->module['website_placeholder'],
    'phone_label'                      => $languageService->module['phone_label'],
    'phone_placeholder'                => $languageService->module['phone_placeholder'],
    'disclaimer_label'                 => $languageService->module['disclaimer_label'],
    'disclaimer_placeholder'           => $languageService->module['disclaimer_placeholder'],
    'save_button'                      => $languageService->module['save_button'],
];

// Template laden und anzeigen
echo $tpl->loadTemplate("imprint", "content", $data_array, 'admin');

?>
