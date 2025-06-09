<?php

use webspell\LanguageManager;

$langManager = new LanguageManager($_database);

$message = '';
$messageClass = '';

// Aktion aus URL lesen
$action = $_GET['action'] ?? '';
$editid = ($action === 'edit' && isset($_GET['id'])) ? (int)$_GET['id'] : 0;
$editLanguage = null;

if ($editid > 0) {
    $editLanguage = $langManager->getLanguage($editid);
    if (!$editLanguage) {
        $message = 'Sprache nicht gefunden.';
        $messageClass = 'alert-danger';
        $editid = 0;
        $action = '';
    }
}

// POST-Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $iso1 = trim($_POST['iso_639_1'] ?? '');
    $nameEn = trim($_POST['name_en'] ?? '');

    if (strlen($iso1) !== 2) {
        $message = 'ISO 639-1 Code muss genau 2 Zeichen lang sein.';
        $messageClass = 'alert-danger';
    } elseif ($nameEn === '') {
        $message = 'Englischer Name ist erforderlich.';
        $messageClass = 'alert-danger';
    } else {
        $data = [
            'iso_639_1'   => $iso1,
            'iso_639_2'   => trim($_POST['iso_639_2'] ?? ''),
            'name_en'     => $nameEn,
            'name_native' => trim($_POST['name_native'] ?? ''),
            'name_de'     => trim($_POST['name_de'] ?? ''),
            'flag'        => trim($_POST['flag'] ?? ''),
            'active'      => isset($_POST['active']) ? 1 : 0,
        ];

        if (isset($_POST['id']) && (int)$_POST['id'] > 0) {
            $success = $langManager->updateLanguage((int)$_POST['id'], $data);
            if ($success) {
                $message = 'Sprache erfolgreich aktualisiert.';
                $messageClass = 'alert-success';
                $editLanguage = $langManager->getLanguage((int)$_POST['id']);
                $editid = (int)$_POST['id'];
                $action = 'edit';
            } else {
                $message = 'Fehler beim Aktualisieren der Sprache.';
                $messageClass = 'alert-danger';
            }
        } else {
            $success = $langManager->insertLanguage($data);
            if ($success) {
                $message = 'Sprache erfolgreich hinzugefügt.';
                $messageClass = 'alert-success';
                header('Location: ?');
                exit;
            } else {
                $message = 'Fehler beim Hinzufügen der Sprache.';
                $messageClass = 'alert-danger';
                $action = 'add';
            }
        }
    }
}

if ($action === '') {
    $languages = $langManager->getAllLanguages();
}
?>

<?php if ($message): ?>
    <div class="alert <?php echo $messageClass; ?>" role="alert">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <i class="bi bi-paragraph"></i> Sprachen verwalten
    </div>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb t-5 p-2 bg-light">
            <li class="breadcrumb-item active" aria-current="page">Sprachen verwalten</li>
        </ol>
    </nav>

    <div class="card-body">
        <div class="container py-5">
            <h2 class="mb-4">Sprachen verwalten</h2>

            <?php if ($action === 'add' || $action === 'edit'): ?>
                <h2><?php echo $action === 'add' ? 'Neue Sprache hinzufügen' : 'Sprache bearbeiten'; ?></h2>

                <form method="post" action="" class="mb-5">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$editid; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="iso_639_1" class="form-label">ISO 639-1 Code (2 Zeichen) *</label>
                        <input type="text" class="form-control" id="iso_639_1" name="iso_639_1" maxlength="2" required
                            value="<?php echo htmlspecialchars($_POST['iso_639_1'] ?? $editLanguage['iso_639_1'] ?? ''); ?>" />
                    </div>

                    <div class="mb-3">
                        <label for="iso_639_2" class="form-label">ISO 639-2 Code (optional)</label>
                        <input type="text" class="form-control" id="iso_639_2" name="iso_639_2" maxlength="3"
                            value="<?php echo htmlspecialchars($_POST['iso_639_2'] ?? $editLanguage['iso_639_2'] ?? ''); ?>" />
                    </div>

                    <div class="mb-3">
                        <label for="name_en" class="form-label">Englischer Name *</label>
                        <input type="text" class="form-control" id="name_en" name="name_en" required
                            value="<?php echo htmlspecialchars($_POST['name_en'] ?? $editLanguage['name_en'] ?? ''); ?>" />
                    </div>

                    <div class="mb-3">
                        <label for="name_native" class="form-label">Name in Muttersprache (optional)</label>
                        <input type="text" class="form-control" id="name_native" name="name_native"
                            value="<?php echo htmlspecialchars($_POST['name_native'] ?? $editLanguage['name_native'] ?? ''); ?>" />
                    </div>

                    <div class="mb-3">
                        <label for="name_de" class="form-label">Name auf Deutsch (optional)</label>
                        <input type="text" class="form-control" id="name_de" name="name_de"
                            value="<?php echo htmlspecialchars($_POST['name_de'] ?? $editLanguage['name_de'] ?? ''); ?>" />
                    </div>

                    <div class="mb-3">
                        <label for="flag" class="form-label">Pfad zur Flagge (optional)</label>
                        <input type="text" class="form-control" id="flag" name="flag"
                            placeholder="z. B. images/flags/de.svg"
                            value="<?php echo htmlspecialchars($_POST['flag'] ?? $editLanguage['flag'] ?? ''); ?>" />
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="active" name="active"
                            <?php
                                $checked = ($_SERVER['REQUEST_METHOD'] === 'POST') ? isset($_POST['active']) : ($editLanguage['active'] ?? 1);
                                echo $checked ? 'checked' : '';
                            ?> />
                        <label class="form-check-label" for="active">Aktiv (sichtbar)</label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?php echo $action === 'edit' ? 'Sprache speichern' : 'Sprache hinzufügen'; ?>
                    </button>
                    <a href="admincenter.php?site=admin_languages" class="btn btn-secondary ms-2">Zur Übersicht</a>
                </form>

            <?php else: ?>

                <a href="admincenter.php?site=admin_languages&action=add" class="btn btn-success mb-3">Neue Sprache hinzufügen</a>

                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Flagge</th>
                            <th>ISO 639-1</th>
                            <th>ISO 639-2</th>
                            <th>Englischer Name</th>
                            <th>Name (Muttersprache)</th>
                            <th>Name (Deutsch)</th>
                            <th>Aktiv</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($languages as $lang): ?>
                            <tr>
                                <td><?php echo (int)$lang['id']; ?></td>
                                <td>
                                    <?php if (!empty($lang['flag'])): ?>
                                        <img src="<?php echo htmlspecialchars($lang['flag']); ?>" alt="Flagge" style="max-height:20px;">
                                    <?php else: ?>
                                        <span class="text-muted">–</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($lang['iso_639_1']); ?></td>
                                <td><?php echo htmlspecialchars($lang['iso_639_2']); ?></td>
                                <td><?php echo htmlspecialchars($lang['name_en']); ?></td>
                                <td><?php echo htmlspecialchars($lang['name_native']); ?></td>
                                <td><?php echo htmlspecialchars($lang['name_de']); ?></td>
                                <td>
                                    <?php if ((int)$lang['active'] === 1): ?>
                                        <span class="badge bg-success">Ja</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nein</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="admincenter.php?site=admin_languages&action=edit&id=<?php echo (int)$lang['id']; ?>" class="btn btn-sm btn-primary">Bearbeiten</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($languages) === 0): ?>
                            <tr><td colspan="9" class="text-center">Keine Sprachen gefunden.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
