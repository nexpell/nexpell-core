<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use webspell\LanguageService;

global $_database, $languageService, $hp_title;

$lang = $languageService->detectLanguage();
$languageService->readModule('imprint');

$config = mysqli_fetch_array(safe_query("SELECT selected_style FROM settings_headstyle_config WHERE id=1"));
$class = htmlspecialchars($config['selected_style']);

$data_array = [
    'class' => $class,
    'title' => $languageService->module['title'],
    'subtitle' => 'Imprint'
];

echo $tpl->loadTemplate("imprint", "head", $data_array, 'theme');

// Impressum-Daten auslesen
$stmt = $_database->prepare("SELECT * FROM settings_imprint LIMIT 1");
$stmt->execute();
$result = $stmt->get_result();
$imprint_data = $result->fetch_assoc();

$type_labels = [
    'private' => $languageService->module['private_option'] ?? 'Privat',
    'association' => $languageService->module['association_option'] ?? 'Verein',
    'small_business' => $languageService->module['small_business_option'] ?? 'Kleinunternehmer',
    'company' => $languageService->module['company_option'] ?? 'Unternehmen',
    'unknown' => 'Unbekannt'
];

$translate = new multiLanguage($lang);
$translate->detectLanguages($imprint_data['disclaimer']);

$type = $imprint_data['type'] ?? 'unknown';

// Dynamisches Name-Label vorab definieren
if ($type === 'association') {
    $name_label = $languageService->module['association_label'] ?? 'Vereinsname';
} elseif ($type === 'company' || $type === 'small_business') {
    $name_label = $languageService->module['company_name_label'] ?? 'Firmenname';
} else {
    $name_label = $languageService->module['name_label'] ?? 'Name';
}

$data_array = [

    'impressum_type_label' => $languageService->module['impressum_type_label'] ?? 'Typ',
    'represented_by_label' => $languageService->module['represented_by_company_label'] ?? $languageService->module['represented_by_label'] ?? 'Vertreten durch',
    'tax_id_label' => $languageService->module['tax_id_company_label'] ?? $languageService->module['tax_id_label'] ?? 'Steuernummer',
    'vat_id_label' => $languageService->module['vat_id_label'] ?? 'USt-ID',
    'register_office_label' => $languageService->module['register_office_label'] ?? 'Registergericht',
    'register_number_label' => $languageService->module['register_number_label'] ?? 'Handelsregister-Nr.',
    'supervisory_authority_label' => $languageService->module['supervisory_authority_label'] ?? 'Aufsichtsbehörde',
    'address_label' => $languageService->module['address_label'] ?? 'Adresse',
    'postal_code_label' => $languageService->module['postal_code_label'] ?? 'PLZ',
    'city_label' => $languageService->module['city_label'] ?? 'Ort',
    'email_label' => $languageService->module['email_label'] ?? 'E-Mail',
    'website_label' => $languageService->module['website_label'] ?? 'Webseite',
    'phone_label' => $languageService->module['phone_label'] ?? 'Telefon',
    'disclaimer_label' => $languageService->module['disclaimer_label'] ?? 'Haftungsausschluss',
    'association_label' => $languageService->module['association_label'] ?? 'Vereinsname',
    'imprint_info' => $languageService->module['imprint_info'] ?? '',

    'name_label' => $name_label,

    // Werte
    'impressum_hp_name' => $hp_title,
    'impressum_type' => $type_labels[$type] ?? $type_labels['unknown'],
    'company_name' => $imprint_data['company_name'] ?? '',
    'represented_by' => $imprint_data['represented_by'] ?? '',
    'tax_id' => $imprint_data['tax_id'] ?? '',
    'vat_id' => $imprint_data['vat_id'] ?? '',
    'register_office' => $imprint_data['register_office'] ?? '',
    'register_number' => $imprint_data['register_number'] ?? '',
    'supervisory_authority' => $imprint_data['supervisory_authority'] ?? '',
    'address' => $imprint_data['address'] ?? '',
    'postal_code' => $imprint_data['postal_code'] ?? '',
    'city' => $imprint_data['city'] ?? '',
    'email' => $imprint_data['email'] ?? '',
    'website' => $imprint_data['website'] ?? '',
    'phone' => $imprint_data['phone'] ?? '',
    'disclaimer' => $translate->getTextByLanguage($imprint_data['disclaimer'] ?? '')
];

// Template rendern
echo $tpl->loadTemplate("imprint", "content", $data_array, 'theme');