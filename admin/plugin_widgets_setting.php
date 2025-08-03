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
$languageService->readModule('plugin_widgets', true);

use nexpell\AccessControl;
// Den Admin-Zugriff für das Modul überprüfen
AccessControl::checkAdminAccess('ac_plugin_widgets_setting');
 // Standardseiten
$pages = [
    'index' => 'Startseite'
];

// Plugins aus DB holen
$stmt = $_database->prepare("SELECT modulname, name FROM settings_plugins ORDER BY name ASC");
$stmt->execute();
$res = $stmt->get_result();

// Plugins ergänzen
while ($row = $res->fetch_assoc()) {
    $pages[$row['modulname']] = $row['name'];
}

$exclude_plugins = ['navigation', 'carousel', 'error_404', 'footer_easy', 'login', 'register', 'lostpassword', 'profile', 'edit_profile', 'lastlogin']; // Plugins, die nicht angezeigt werden sollen
?>



<title>Widget-Manager</title>

<style>
  .widget-list { min-height: 40px; border:1px dashed #ccc; padding:5px; list-style:none; }
  .widget-item { margin:5px; padding:5px 10px; background:#f8f9fa; border:1px solid #ddd; cursor:move; user-select:none; }
</style>
<div class="card">
  <div class="card-header">Widgets verwalten</div>
  <div class="card-body">
    <div class="container py-5">
      <div class="row">
        <!-- Linke Seite -->
        <div class="col-md-3">
          <div class="p-3 border rounded bg-secondary-subtle">
            <!-- Seitenauswahl -->
            <label for="page" class="form-label">
              <h6><i class="bi bi-folder2-open"></i> Page:</h6>
            </label>
            <select id="page" name="page" class="form-select mb-3" style="width:auto; display:inline-block;">
              <option value="">Please select</option>
              <?php foreach ($pages as $value => $label): ?>
                <?php if (!in_array($value, $exclude_plugins, true)) : ?>
                  <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>

            <!-- Verfügbare Widgets -->
            <h6 class="mt-4"><i class="bi bi-box-seam"></i> Available Widgets</h6>
            <ul id="available" class="widget-list">
              <?php
              $res = safe_query("SELECT * FROM settings_widgets");
              while ($row = mysqli_fetch_assoc($res)) {
                echo "<li class='widget-item' data-id='{$row['widget_key']}'>" . htmlspecialchars($row['title']) . "</li>";
              }
              ?>
            </ul>
          </div>
        </div>

                <!-- Rechte Seite -->
                



        <div class="col-md-9">
          <!-- Header Area -->
          <div class="mb-3 p-3 border rounded bg-primary-subtle">
            <h6><i class="bi bi-compass"></i> Header</h6>
            <ul id="top" class="widget-list"></ul>
          </div>

          <!-- Navigation Area -->
          <div class="mb-3 p-3 border rounded bg-secondary-subtle">
            <h6><i class="bi bi-list"></i> Navigation</h6>
          </div>

          <!-- Content Header -->
          <div class="mb-3 p-3 border rounded bg-info-subtle">
            <h6><i class="bi bi-layout-three-columns"></i> Content Header</h6>
            <ul id="undertop" class="widget-list"></ul>
          </div>

          <div class="row">
            <!-- Left Sidebar -->
            <div class="col-md-3">
              <div class="p-3 border rounded bg-warning-subtle">
                <h6><i class="bi bi-arrow-bar-left"></i> Left Sidebar</h6>
                <ul id="left" class="widget-list"></ul>
              </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-6">
              <div class="mb-3 p-3 border rounded bg-success-subtle">
                <h6><i class="bi bi-arrow-bar-up"></i> Main Content Top</h6>
                <ul id="maintop" class="widget-list"></ul>
              </div>

              <div class="mb-3 p-3 border rounded bg-light">
                <h6><i class="bi bi-file-text"></i> Main Content</h6>
              </div>

              <div class="mb-3 p-3 border rounded bg-success-subtle">
                <h6><i class="bi bi-arrow-bar-down"></i> Main Content Bottom</h6>
                <ul id="mainbottom" class="widget-list"></ul>
              </div>
            </div>

            <!-- Right Sidebar -->
            <div class="col-md-3">
              <div class="p-3 border rounded bg-warning-subtle">
                <h6><i class="bi bi-arrow-bar-right"></i> Right Sidebar</h6>
                <ul id="right" class="widget-list"></ul>
              </div>
            </div>
          </div>

          <!-- Content Footer -->
          <div class="mt-3 p-3 border rounded bg-danger-subtle">
            <h6><i class="bi bi-box-arrow-down"></i> Content Footer</h6>
            <ul id="bottom" class="widget-list"></ul>
          </div>

          <!-- Save Button -->
          <button class="btn btn-primary mt-4" onclick="saveWidgets()">
            <i class="bi bi-save"></i> Save
          </button>
        </div>



      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>

function toggleWidgetList() {
    const select = document.getElementById('page');
    const widgetList = document.getElementById('available');
    if (select.value === '') {
      widgetList.style.display = 'none'; // ausblenden
    } else {
      widgetList.style.display = 'block'; // einblenden
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
  toggleWidgetList(); // beim Laden prüfen

  const pageSelect = document.getElementById('page');

  pageSelect.addEventListener('change', () => {
    toggleWidgetList(); // Sichtbarkeit der verfügbaren Widgets
    loadWidgets();      // 🔥 Widgets aus DB laden
  });
});


document.addEventListener("DOMContentLoaded", function() {

  const positions = ['top','undertop','left','maintop','mainbottom','right','bottom','available'];


  // Sortable initialisieren
  positions.forEach(pos => {
    new Sortable(document.getElementById(pos), {
      group: 'shared',
      animation: 150,
      ghostClass: 'sortable-ghost',
    });
  });

  window.loadWidgets = function() {
    const page = document.getElementById('page').value;

    fetch('plugin_widgets_save.php?mode=load&page=' + encodeURIComponent(page))
      .then(response => response.json())
      .then(data => {
        // Listen leeren
        positions.forEach(pos => {
          document.getElementById(pos).innerHTML = '';
        });

        // zugewiesene Widgets
        data.assigned.forEach(widget => {
          if (positions.includes(widget.position)) {
            const li = document.createElement('li');
            li.className = 'widget-item';
            li.dataset.id = widget.widget_key;
            li.textContent = widget.title;
            document.getElementById(widget.position).appendChild(li);
          }
        });

        // verfügbare Widgets
        data.available.forEach(widget => {
          const li = document.createElement('li');
          li.className = 'widget-item';
          li.dataset.id = widget.widget_key;
          li.textContent = widget.title;
          document.getElementById('available').appendChild(li);
        });
      })
      .catch(err => {
        alert('Fehler beim Laden der Widgets: ' + err.message);
      });
  }

  window.saveWidgets = function() {
    const page = document.getElementById('page').value;
    const data = {};

    positions.forEach(pos => {
      data[pos] = Array.from(document.getElementById(pos).querySelectorAll('.widget-item'))
        .map(li => li.dataset.id);
    });

    fetch('plugin_widgets_save.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({page, data})
    })
    .then(response => response.text())
    .then(msg => alert(msg))
    .catch(() => alert('Fehler beim Speichern'));
  }

});
</script>


