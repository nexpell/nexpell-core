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
$languageService->readModule('headstyle', true);

use nexpell\AccessControl;

// Admin access check
AccessControl::checkAdminAccess('ac_headstyle');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_style'])) {
    // sanitize input
    $style = htmlspecialchars($_POST['selected_style'] ?? '', ENT_QUOTES, 'UTF-8');
    // update in database (use prepared statement)
    $stmt =  $_database->prepare("UPDATE settings_headstyle_config SET selected_style = ? WHERE id = 1");
    mysqli_stmt_bind_param($stmt, 's', $style);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo 'Überschriften-Stil <strong>' . htmlspecialchars($style) . '</strong> gespeichert!';
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Fetch current selection
$res = safe_query("SELECT selected_style FROM settings_headstyle_config WHERE id = 1");
$current = mysqli_fetch_assoc($res);
$selected = $current['selected_style'] ?? '';

// Styles array (key => label)
$styles = [];
for ($i = 1; $i <= 10; $i++) {
    $key = "head-boxes-$i";
    $styles[$key] = "Überschrift $i";
}

?>
<style>
  input[type="radio"]:checked + label {
    font-weight: bold;
    font-size: 0.9rem;
    color: #fe821d;
  }

  input[type="radio"]:checked + label::before {
    content: '✔ ';
    font-size: 1.2rem;
    color: #fe821d;
  }

  input[type="radio"]:checked {
    transform: scale(1.2);
  }

  input[type="radio"].form-check-input:checked {
    accent-color: #0d6efd;
  }
  .form-check-label.custom-height {
    height: 30px;
    line-height: 30px;
  }


</style>

<div class="card">
<div class="card-header">
        Überschriften-Stil wählen
    </div>
    <div class="card-body"><div class="container py-5">

<form method="post" class="row">
    
    <?php foreach (array_chunk($styles, 5, true) as $column): ?>
        <div class="col-md-6">
            <div class="row row-cols-1">
                <?php foreach ($column as $key => $label): ?>
                    <div class="col">
                        <div class="card">
                            <div class="card-body text-center">
                                <input class="form-check-input mb-2"
                                       type="radio"
                                       name="selected_style"
                                       id="style_<?= $key ?>"
                                       value="<?= htmlspecialchars($key) ?>"
                                       <?= $selected === $key ? 'checked' : '' ?> />

                                <label class="form-check-label d-block mb-2 custom-height" for="style_<?= $key ?>">
                                  <?= htmlspecialchars($label) ?>
                                </label>
                                <img src="/admin/images/headlines/<?= str_replace('head-boxes-', 'headlines-', $key) ?>.jpg" 
                                     alt="<?= htmlspecialchars($label) ?>" 
                                     class="img-fluid rounded" 
                                     style="max-height: 180px; border:1px solid #dee2e6;" />
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <br class="mb-3">
    <div class="col-12 mb-3">
        <button type="submit" class="btn btn-primary">Speichern</button>
    </div>
</form>
</div></div></div>