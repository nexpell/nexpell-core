<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\LanguageService;
use nexpell\AccessControl;
use nexpell\NavigationUpdater;

// Standard setzen, wenn nicht vorhanden
$_SESSION['language'] = $_SESSION['language'] ?? 'de';
$language = $_SESSION['language'];

// Initialisieren
global $languageService;
$lang = $languageService->detectLanguage();
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('seo', true);

AccessControl::checkAdminAccess('ac_seo_meta');

// Verfügbare Seiten (aus der Navigation)
$availableSites = [];

$result = $_database->query("SELECT url, name FROM navigation_website_sub WHERE indropdown = 1 ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $url = $row['url'];
        $site = null;
        if (preg_match('/site=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $site = $matches[1];
        } else {
            $site = trim($url, '/');
        }
        if ($site) {
            $availableSites[$site] = $row['name'] ?: $site;
        }
    }
    $result->free();
}

// Gewählte Seite
$site = $_POST['site'] ?? $_GET['site'] ?? 'seo';

// SEO-Daten speichern
if (isset($_POST['save'])) {
    $stmt = $_database->prepare("
        REPLACE INTO settings_seo_meta (site, language, title, description)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssss", $site, $language, $_POST['title'], $_POST['description']);
    $stmt->execute();

    NavigationUpdater::updateFromAdminFile($site);

    echo '<div class="alert alert-success">SEO-Daten wurden gespeichert.</div>';
}

// Aktuelle Daten laden
$stmt = $_database->prepare("
    SELECT title, description FROM settings_seo_meta
    WHERE site = ? AND language = ?
");
$stmt->bind_param("ss", $site, $language);
$stmt->execute();
$result = $stmt->get_result();
$seo = $result->fetch_assoc() ?: ['title' => '', 'description' => ''];

// Alle Einträge (Sprache beachten)
$allSeo = [];
$resAll = $_database->prepare("
    SELECT site, title, description FROM settings_seo_meta
    WHERE language = ?
    ORDER BY site ASC
");
$resAll->bind_param("s", $language);
$resAll->execute();
$resAllResult = $resAll->get_result();
while ($row = $resAllResult->fetch_assoc()) {
    $allSeo[] = $row;
}

?>
<div class="card">
    <div class="card-header">
        <i class="bi bi-paragraph"></i> <?= $languageService->get('edit_seo_metadata') ?>
    </div>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb t-5 p-2 bg-light">
            <li class="breadcrumb-item"><a href="admincenter.php?site=user_roles"><?= $languageService->get('edit_seo_metadata') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $languageService->get('new_edit') ?></li>
        </ol>
    </nav>

    <div class="card-body">

        <div class="container py-5">
<h2><?= $languageService->get('edit_seo_metadata') ?></h2>

<form method="post" id="languageForm" class="form-inline mb-3">
    <label for="language" class="mr-2"><?= $languageService->get('select_language') ?>:</label>
    <select name="language" id="language" class="form-select" onchange="document.getElementById('languageForm').submit();">
        <option value="de" <?= $language === 'de' ? 'selected' : '' ?>><?= $languageService->get('german') ?></option>
        <option value="en" <?= $language === 'en' ? 'selected' : '' ?>><?= $languageService->get('english') ?></option>
        <option value="it" <?= $language === 'it' ? 'selected' : '' ?>><?= $languageService->get('italian') ?></option>
    </select>
    <input type="hidden" name="site" value="<?= htmlspecialchars($site) ?>">
</form>

<form method="post" class="form">
    <div class="form-group">
        <label for="site"><?= $languageService->get('select_site') ?></label>
        <select id="site" name="site" class="form-select" onchange="this.form.submit()">
            <option value="" <?= empty($site) ? 'selected' : '' ?>><?= $languageService->get('please_select') ?></option>
            <?php foreach ($availableSites as $key => $label):
                $translate = new multiLanguage($language);
                $translate->detectLanguages($label); ?>
                <option value="<?= htmlspecialchars($key) ?>" <?= ($key === $site) ? 'selected' : '' ?>>
                    <?= $translate->getTextByLanguage($label) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="title"><?= $languageService->get('meta_title') ?></label>
        <input type="text" id="title" name="title" class="form-control" maxlength="255" value="<?= htmlspecialchars($seo['title']) ?>" required>
    </div>

    <div class="form-group">
        <label for="description"><?= $languageService->get('meta_description') ?></label>
        <textarea id="description" name="description" class="form-control" rows="4" required><?= htmlspecialchars($seo['description']) ?></textarea>
    </div>

    <button type="submit" name="save" class="btn btn-primary mt-3 mb-3"><?= $languageService->get('save') ?></button>
</form>

<hr>

<h3><?= $languageService->get('all_seo_entries') ?> (<?= $languageService->get('lang_language') ?>: <?= htmlspecialchars($language) ?>)</h3>

<table class="table table-bordered table-striped bg-white shadow-sm">
    <thead class="table-light">
        <tr>
            <th><?= $languageService->get('site') ?></th>
            <th><?= $languageService->get('meta_title') ?></th>
            <th><?= $languageService->get('meta_description') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($allSeo as $entry): ?>
            <tr>
                <td>
                    <?php
                    $siteKey = $availableSites[$entry['site']] ?? $entry['site'];
                    $translate = new multiLanguage($language);
                    $translate->detectLanguages($siteKey);
                    echo $translate->getTextByLanguage($siteKey);
                    ?>
                </td>
                <td><?= htmlspecialchars($entry['title']) ?></td>
                <td><?= htmlspecialchars($entry['description']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($allSeo)): ?>
            <tr><td colspan="3"><?= $languageService->get('no_seo_data_found') ?></td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div></div></div>