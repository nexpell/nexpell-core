<?php

use webspell\LanguageService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Standard setzen, wenn nicht vorhanden
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

// Initialisieren
global $_database, $languageService;
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('imprint', true);

// CAPTCHA Dummy-Klasse (bitte durch dein System ersetzen)
class Captcha {
    public function checkCaptcha($id, $hash) {
        // Hier echte Prüfung einbauen
        return true;
    }
    public function createTransaction() {
        // Dummy hash
        return "dummyhash";
    }
    public function getHash() {
        return "dummyhash";
    }
}
$CAPCLASS = new Captcha();

// Fehler und Erfolgsmeldung
$errors = [];
$success = false;

// Daten aus DB holen
function loadData($_database) {
    $res = $_database->query("SELECT * FROM settings_imprint LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        return $row;
    }
    return [];
}

$data = loadData($_database);

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    if (!$CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'] ?? '')) {
        $errors[] = "Ungültige Transaktion (Captcha fehlerhaft).";
    } else {
        // Felder einlesen
        $type = trim($_POST['type'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $website = trim($_POST['website'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $disclaimer = $_POST['disclaimer'];

        $address = trim($_POST['address'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $city = trim($_POST['city'] ?? '');

        $register_office = trim($_POST['register_office'] ?? '');
        $register_number = trim($_POST['register_number'] ?? '');
        $vat_id = trim($_POST['vat_id'] ?? '');
        $supervisory_authority = trim($_POST['supervisory_authority'] ?? '');

        $company_name = '';
        $represented_by = '';
        $tax_id = '';
        $register_office = '';
        $register_number = '';
        $vat_id = '';
        $supervisory_authority = '';

        if ($type === 'private') {
            $company_name = trim($_POST['company_name_private'] ?? '');
        } elseif ($type === 'association') {
            $company_name = trim($_POST['company_name_association'] ?? '');
            $represented_by = trim($_POST['represented_by_association'] ?? '');
        } elseif ($type === 'small_business') {
            $company_name = trim($_POST['company_name_small_business'] ?? '');
            $tax_id = trim($_POST['tax_id_small_business'] ?? '');
        } elseif ($type === 'company') {
            $company_name = trim($_POST['company_name_company'] ?? '');
            $represented_by = trim($_POST['represented_by_company'] ?? '');
            $tax_id = trim($_POST['tax_id_company'] ?? '');
        }

        // Validierung
        if (empty($type)) {
            $errors[] = "Bitte geben Sie den Typ an.";
        }
        if (in_array($type, ['private', 'association', 'small_business', 'company']) && empty($company_name)) {
            $errors[] = "Name/Firma ist erforderlich.";
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Bitte eine gültige E-Mail-Adresse angeben.";
        }
        if (empty($address)) {
            $errors[] = "Adresse ist erforderlich.";
        }
        if (empty($postal_code)) {
            $errors[] = "Postleitzahl ist erforderlich.";
        }
        if (empty($city)) {
            $errors[] = "Ort ist erforderlich.";
        }

        if (empty($errors)) {
            // Escape für SQL
            $type_e = $_database->real_escape_string($type);
            $company_name_e = $_database->real_escape_string($company_name);
            $represented_by_e = $_database->real_escape_string($represented_by);
            $tax_id_e = $_database->real_escape_string($tax_id);
            $email_e = $_database->real_escape_string($email);
            $website_e = $_database->real_escape_string($website);
            $phone_e = $_database->real_escape_string($phone);
            $disclaimer_e = $disclaimer;
            $address_e = $_database->real_escape_string($address);
            $postal_code_e = $_database->real_escape_string($postal_code);
            $city_e = $_database->real_escape_string($city);
            $register_office_e = $_database->real_escape_string($register_office);
            $register_number_e = $_database->real_escape_string($register_number);
            $vat_id_e = $_database->real_escape_string($vat_id);
            $supervisory_authority_e = $_database->real_escape_string($supervisory_authority);

            // Prüfen, ob Eintrag vorhanden
            $check = $_database->query("SELECT id FROM settings_imprint LIMIT 1");

            if ($check && $check->num_rows > 0) {
                // Update
                $_database->query("
                    UPDATE settings_imprint SET
                        type = '$type_e',
                        company_name = '$company_name_e',
                        represented_by = '$represented_by_e',
                        tax_id = '$tax_id_e',
                        email = '$email_e',
                        website = '$website_e',
                        phone = '$phone_e',
                        disclaimer = '$disclaimer_e',
                        address = '$address_e',
                        postal_code = '$postal_code_e',
                        city = '$city_e',
                        register_office = '$register_office_e',
                        register_number = '$register_number_e',
                        vat_id = '$vat_id_e',
                        supervisory_authority = '$supervisory_authority_e'
                    LIMIT 1
                ");
            } else {
                // Insert
                $_database->query("
                    INSERT INTO settings_imprint 
                    (type, company_name, represented_by, tax_id, email, website, phone, disclaimer, address, postal_code, city, register_office, register_number, vat_id, supervisory_authority)
                    VALUES
                    ('$type_e', '$company_name_e', '$represented_by_e', '$tax_id_e', '$email_e', '$website_e', '$phone_e', '$disclaimer_e', '$address_e', '$postal_code_e', '$city_e', '$register_office_e', '$register_number_e', '$vat_id_e', '$supervisory_authority_e')
                ");
            }

            $success = true;
            $data = loadData($_database); // Neue Daten laden
        }
    }
}

// Formular-Ausgabe

$hash = $CAPCLASS->getHash();

function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES);
}

?>

<style>
    label { display: block; margin-top: 1em; }
    input[type=text], input[type=email], select, textarea {
        width: 100%; padding: 0.5em; margin-top: 0.3em;
    }
    .error { background: #fdd; padding: 1em; margin-bottom: 1em; border: 1px solid #f99; }
    .success { background: #dfd; padding: 1em; margin-bottom: 1em; border: 1px solid #9f9; }
    .type_block { display: none; }
</style>

<div class="card">
    <div class="card-header">
        <i class="bi bi-paragraph"></i> <?= $languageService->module['imprint'] ?>
    </div>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0 px-3 py-2 bg-light">
            <li class="breadcrumb-item"><a href="admincenter.php?site=user_roles"><?= $languageService->module['imprint'] ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $languageService->module['new_edit'] ?></li>
        </ol>
    </nav>

    <div class="card-body">
        <div class="container py-5">
            <h3 class="mb-4"><?= $languageService->module['imprint'] ?></h3>

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= h($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?= $languageService->module['save_changes'] ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <input type="hidden" name="captcha_hash" value="<?= h($hash) ?>">

                <div class="mb-3">
                    <label for="type" class="form-label"><?= $languageService->module['type_label'] ?> <small>(<?= $languageService->module['type_hint'] ?>)</small></label>
                    <select name="type" id="type" class="form-select" required>
                        <option value="">- <?= $languageService->module['select_type'] ?> -</option>
                        <option value="private" <?= ($data['type'] ?? '') === 'private' ? 'selected' : '' ?>>Privatperson</option>
                        <option value="association" <?= ($data['type'] ?? '') === 'association' ? 'selected' : '' ?>>Verein</option>
                        <option value="small_business" <?= ($data['type'] ?? '') === 'small_business' ? 'selected' : '' ?>>Kleinunternehmer</option>
                        <option value="company" <?= ($data['type'] ?? '') === 'company' ? 'selected' : '' ?>>Firma</option>
                    </select>
                </div>

            

            <!-- private -->
            <div id="private_fields" class="type_block mb-3">
                <label for="company_name_private"><?= $languageService->module['name_private'] ?></label>
                <input type="text" id="company_name_private" name="company_name_private" value="<?= h($data['company_name'] ?? '') ?>" class="form-control">
            </div>

            <!-- association -->
            <div id="association_fields" class="type_block mb-3">
                <label for="company_name_association"><?= $languageService->module['name_association'] ?></label>
                <input type="text" id="company_name_association" name="company_name_association" value="<?= h($data['company_name'] ?? '') ?>" class="form-control">
                <label for="represented_by_association"><?= $languageService->module['represented_by_association'] ?></label>
                <input type="text" id="represented_by_association" name="represented_by_association" value="<?= h($data['represented_by'] ?? '') ?>" class="form-control">
            </div>

            <!-- small_business -->
            <div id="small_business_fields" class="type_block mb-3">
                <label for="company_name_small_business"><?= $languageService->module['name_small_business'] ?></label>
                <input type="text" id="company_name_small_business" name="company_name_small_business" value="<?= h($data['company_name'] ?? '') ?>" class="form-control">
                <label for="tax_id_small_business"><?= $languageService->module['tax_id_small_business'] ?></label>
                <input type="text" id="tax_id_small_business" name="tax_id_small_business" value="<?= h($data['tax_id'] ?? '') ?>" class="form-control">
            </div>

            <!-- company -->
            <div id="company_fields" class="type_block mb-3">
                <label for="company_name_company"><?= $languageService->module['name_company'] ?></label>
                <input type="text" id="company_name_company" name="company_name_company" value="<?= h($data['company_name'] ?? '') ?>" class="form-control">
                <label for="represented_by_company"><?= $languageService->module['represented_by_company'] ?></label>
                <input type="text" id="represented_by_company" name="represented_by_company" value="<?= h($data['represented_by'] ?? '') ?>" class="form-control">
                <label for="tax_id_company"><?= $languageService->module['tax_id_company'] ?></label>
                <input type="text" id="tax_id_company" name="tax_id_company" value="<?= h($data['tax_id'] ?? '') ?>" class="form-control">
            </div>

            <div class="mb-3">
                <label for="email" class="form-label"><?= $languageService->module['email'] ?></label>
                <input type="email" class="form-control" name="email" id="email" value="<?= h($data['email'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label for="website" class="form-label"><?= $languageService->module['website'] ?></label>
                <input type="text" class="form-control" name="website" id="website" value="<?= h($data['website'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label"><?= $languageService->module['phone'] ?></label>
                <input type="text" class="form-control" name="phone" id="phone" value="<?= h($data['phone'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="address" class="form-label"><?= $languageService->module['address'] ?></label>
                <input type="text" class="form-control" name="address" id="address" value="<?= h($data['address'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="postal_code" class="form-label"><?= $languageService->module['postal_code'] ?></label>
                <input type="text" class="form-control" name="postal_code" id="postal_code" value="<?= h($data['postal_code'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label for="city" class="form-label"><?= $languageService->module['city'] ?></label>
                <input type="text" class="form-control" name="city" id="city" value="<?= h($data['city'] ?? '') ?>" required>
            </div>
            
            <!-- Optional: Rechtliches -->
            <div class="mb-3" id="registergericht_field">
                <label for="register_office" class="form-label"><?= $languageService->module['register_office'] ?></label>
                <input type="text" class="form-control" name="register_office" id="register_office" value="<?= h($data['register_office'] ?? '') ?>">
            </div>
            <div class="mb-3" id="registernummer_field">
                <label for="register_number" class="form-label"><?= $languageService->module['register_number'] ?></label>
                <input type="text" class="form-control" name="register_number" id="register_number" value="<?= h($data['register_number'] ?? '') ?>">
            </div>
            <div class="mb-3" id="umsatzsteuerid_field">
                <label for="vat_id" class="form-label"><?= $languageService->module['vat_id'] ?></label>
                <input type="text" class="form-control" name="vat_id" id="vat_id" value="<?= h($data['vat_id'] ?? '') ?>">
            </div>
            <div class="mb-3" id="aufsichtsbehoerde_field">
                <label for="supervisory_authority" class="form-label"><?= $languageService->module['supervisory_authority'] ?></label>
                <input type="text" class="form-control" name="supervisory_authority" id="supervisory_authority" value="<?= h($data['supervisory_authority'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="disclaimer" class="form-label"><?= $languageService->module['disclaimer'] ?></label>
                <textarea class="form-control ckeditor" name="disclaimer" id="disclaimer" rows="4"><?= $data['disclaimer'] ?? '' ?></textarea>
            </div>

            <button type="submit" name="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> <?= $languageService->module['save_changes'] ?>
            </button>
        </form>
    </div>
</div>

<script>
function toggleFields(type) {
    // Hauptbereiche ausblenden
    document.getElementById('private_fields').style.display = 'none';
    document.getElementById('association_fields').style.display = 'none';
    document.getElementById('small_business_fields').style.display = 'none';
    document.getElementById('company_fields').style.display = 'none';

    // Zusatzfelder ausblenden
    document.getElementById('registergericht_field').style.display = 'none';
    document.getElementById('registernummer_field').style.display = 'none';
    document.getElementById('umsatzsteuerid_field').style.display = 'none';
    document.getElementById('aufsichtsbehoerde_field').style.display = 'none';

    // Hauptbereiche anzeigen
    if (type === 'small_business' || type === 'company') {
        document.getElementById('registergericht_field').style.display = 'block';
        document.getElementById('registernummer_field').style.display = 'block';
        document.getElementById('umsatzsteuerid_field').style.display = 'block';
        document.getElementById('aufsichtsbehoerde_field').style.display = 'block';
    }

    if (type === 'private') {
        document.getElementById('private_fields').style.display = 'block';
    } else if (type === 'association') {
        document.getElementById('association_fields').style.display = 'block';
    } else if (type === 'small_business') {
        document.getElementById('small_business_fields').style.display = 'block';
    } else if (type === 'company') {
        document.getElementById('company_fields').style.display = 'block';
    }
}

// Initial bei Laden einmal aufrufen
toggleFields(document.getElementById('type').value);

// Eventlistener für Änderung
document.getElementById('type').addEventListener('change', function(){
    toggleFields(this.value);
});

</script>