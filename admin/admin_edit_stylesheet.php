<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use nexpell\AccessControl;
// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_stylesheet');

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
        $message = 'Datei erfolgreich gespeichert.';
        $messageType = 'success';
    } else {
        $message = 'Fehler beim Speichern der Datei.';
        $messageType = 'danger';
    }
}

// Dateiinhalt laden
$cssContent = file_exists($cssFile) ? file_get_contents($cssFile) : '';
?>

<!-- CodeMirror CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.css" />
<!-- Optional: Theme -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/theme/dracula.css" />

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div><i class="bi bi-journal-text"></i> Stylesheet bearbeiten</div>
    </div>

    <nav aria-label="breadcrumb" class="bg-light p-2 mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="admincenter.php?site=admin_edit_stylesheet">Stylesheet bearbeiten</a></li>
            <li class="breadcrumb-item active" aria-current="page">New / Edit</li>
        </ol>
    </nav>  

    <div class="card-body">
        <div class="container py-3">

            <h4 class="mb-4">
                Stylesheet bearbeiten<br>
                <small class="text-muted">/includes/themes/default/css/stylesheet.css</small>
            </h4>
            <p class="text-secondary small mb-4">
                Hier kannst du das Standard-CSS des Themes direkt bearbeiten und Änderungen speichern.
            </p>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="mb-3">
                    <textarea 
                        id="css-editor"
                        class="form-control" 
                        name="css_content" 
                        spellcheck="false" 
                        aria-label="Stylesheet Inhalt" 
                        style="height:600px; font-family: monospace; font-size: 1rem; resize: vertical;"
                    ><?= htmlspecialchars($cssContent) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </form>
        </div>
    </div>
</div>

<!-- CodeMirror JS -->
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/lib/codemirror.js"></script>
<!-- CSS Mode -->
<script src="https://cdn.jsdelivr.net/npm/codemirror@5.65.13/mode/css/css.js"></script>

<script>
  // CodeMirror Editor initialisieren
  var editor = CodeMirror.fromTextArea(document.getElementById('css-editor'), {
    mode: 'css',
    theme: 'dracula',           // Optional: anderes Theme möglich, z.B. 'default'
    lineNumbers: true,
    lineWrapping: true,
    styleActiveLine: true,
    matchBrackets: true
  });
</script>
