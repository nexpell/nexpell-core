<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use webspell\LanguageService;

global $_database,$languageService,$hp_title;

$lang = $languageService->detectLanguage();
$languageService->readModule('imprint');

$config = mysqli_fetch_array(safe_query("SELECT selected_style FROM settings_headstyle_config WHERE id=1"));
$class = htmlspecialchars($config['selected_style']);

// Header-Daten
$data_array = [
    'class'    => $class,
    'title' => $languageService->module['title'],
    'subtitle' => 'Imprint'
];

echo $tpl->loadTemplate("imprint", "head", $data_array, 'theme');


// Impressum-Daten auslesen
$stmt = $_database->prepare("SELECT * FROM settings_imprint LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$imprint_data = $result->fetch_assoc();

// Typ-Bezeichnungen
$type_labels = [
    'private' => $languageService->module['private_option'] ?? 'Privat',
    'association' => $languageService->module['association_option'] ?? 'Verein',
    'small_business' => $languageService->module['small_business_option'] ?? 'Kleinunternehmer',
    'company' => $languageService->module['company_option'] ?? 'Unternehmen',
    'unknown' => 'Unbekannt'
];


$translate = new multiLanguage($lang);
$translate->detectLanguages($imprint_data['disclaimer']);
// Basis-Labels (immer vorhanden)
$data_array = [
    'impressum_type_label' => $languageService->module['impressum_type_label'] ?? 'Typ',
    'represented_by_label' => $languageService->module['represented_by_company_label'] ?? $languageService->module['represented_by_label'] ?? 'Vertreten durch',
    'tax_id_label' => $languageService->module['tax_id_company_label'] ?? $languageService->module['tax_id_label'] ?? 'Steuernummer',
    'email_label' => $languageService->module['email_label'] ?? 'E-Mail',
    'website_label' => $languageService->module['website_label'] ?? 'Webseite',
    'phone_label' => $languageService->module['phone_label'] ?? 'Telefon',
    'disclaimer_label' => $languageService->module['disclaimer_label'] ?? 'Haftungsausschluss',
    'association_label' => $languageService->module['association_label'] ?? 'Vereinsname',
    'imprint_info' => $languageService->module['imprint_info'] ?? '',
];

// Dynamisches name_label je nach Typ setzen
$type = $imprint_data['type'] ?? 'unknown';
switch ($type) {
    case 'association':
        $data_array['name_label'] = $languageService->module['association_label'] ?? 'Vereinsname';
        break;
    case 'company':
    case 'small_business':
        $data_array['name_label'] = $languageService->module['company_name_label'] ?? 'Firmenname';
        break;
    default:
        $data_array['name_label'] = $languageService->module['name_label'] ?? 'Name';
        break;
}

// Werte zuweisen (mit sicheren Fallbacks)
$data_array += [
    'impressum_hp_name' => $hp_title,
    'impressum_type' => $type_labels[$type] ?? $type_labels['unknown'],
    'company_name' => $imprint_data['company_name'] ?? '',
    'represented_by' => $imprint_data['represented_by'] ?? '',
    'tax_id' => $imprint_data['tax_id'] ?? '',
    'email' => $imprint_data['email'] ?? '',
    'website' => $imprint_data['website'] ?? '',
    'phone' => $imprint_data['phone'] ?? '',
    #'disclaimer' => $imprint_data['disclaimer'] ?? ''
    'disclaimer' => $translate->getTextByLanguage($imprint_data['disclaimer'])
];



// Template für das Frontend laden
echo $tpl->loadTemplate("imprint", "content", $data_array, 'theme');

