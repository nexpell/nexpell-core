<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\AccessControl;
use nexpell\LanguageService;

// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_stylesheet');

// Initialisieren
global $languageService;
$lang = $languageService->detectLanguage();
$languageService = new LanguageService($_database);

// Admin-Modul laden
$languageService->readModule('stylesheet', true);

$cssFile = __DIR__ . '/../includes/themes/default/css/stylesheet.css';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: Sicherheitsprüfungen (CSRF-Token, Benutzerrechte) ergänzen

    $newCss = $_POST['css_content'] ?? '';

    // Falls \r\n als Text vorhanden sind, in echte Zeilenumbrüche umwandeln
    $newCss = str_replace(['\\r\\n', '\\n', '\\r'], ["\r\n", "\n", "\r"], $newCss);

    // Backup anlegen
    copy($cssFile, $cssFile . '.bak_' . date('Ymd_His'));

    // Datei speichern
    if (file_put_contents($cssFile, $newCss) !== false) {
        $message = $languageService->get('file_saved_success');
        $messageType = 'success';
    } else {
        $message = $languageService->get('file_saved_error');
        $messageType = 'danger';
    }
}

// Dateiinhalt laden
$cssContent = file_exists($cssFile) ? file_get_contents($cssFile) : '';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/theme/dracula.css" />

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><i class="bi bi-journal-text"></i> <?= $languageService->get('edit_stylesheet') ?></div>
    </div>

    <nav aria-label="breadcrumb" class="bg-light p-2 mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="admincenter.php?site=admin_edit_stylesheet"><?= $languageService->get('edit_stylesheet') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $languageService->get('new_edit') ?></li>
        </ol>
    </nav>   

    <div class="card-body">
        <div class="container py-3">

            <h4 class="mb-4">
                <?= $languageService->get('edit_stylesheet') ?><br>
                <small class="text-muted"><?= $languageService->get('stylesheet_path') ?></small>
            </h4>
            <p class="text-secondary small mb-4">
                <?= $languageService->get('stylesheet_description') ?>
            </p>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= $languageService->get('close') ?>"></button>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="mb-3">
                    <textarea 
                        id="css-editor"
                        class="form-control" 
                        name="css_content" 
                        spellcheck="false" 
                        aria-label="<?= $languageService->get('stylesheet_content') ?>" 
                        style="height:600px; font-family: monospace; font-size: 1rem; resize: vertical;"
                    ><?= htmlspecialchars($cssContent) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><?= $languageService->get('save') ?></button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.js"></script>
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/mode/css/css.js"></script>

<script>
  // CodeMirror Editor initialisieren
  var editor = CodeMirror.fromTextArea(document.getElementById('css-editor'), {
    mode: 'css',
    theme: 'dracula',          // Optional: anderes Theme möglich, z.B. 'default'
    lineNumbers: true,
    lineWrapping: true,
    styleActiveLine: true,
    matchBrackets: true
  });
</script>