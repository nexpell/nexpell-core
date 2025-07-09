<?php
 // Standardseiten
$pages = [
    'index' => 'Startseite',
    'forum' => 'Forum',
    'gallery' => 'Galerie'
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
  .widget-list { min-height: 80px; border:1px dashed #ccc; padding:5px; list-style:none; }
  .widget-item { margin:5px; padding:5px 10px; background:#f8f9fa; border:1px solid #ddd; cursor:move; user-select:none; }
</style>
<div class="card">
  <div class="card-header">Widgets verwalten</div>
  <div class="card-body">
    <div class="container py-5">
      <div class="row">
        <!-- Linke Seite -->
        <div class="col-md-3">
          <div class="p-3 border rounded bg-light">
            <label for="page"><h6>📂Seite:</h6></label>
            <select
              id="page"
              class="form-select"
              style="width:auto;display:inline-block;"
              onchange="if(this.value) loadWidgets();"
            >
              <option value="">Bitte auswählen</option>
              <?php foreach ($pages as $value => $label): ?>
                <?php if (!in_array($value, $exclude_plugins, true)) : ?>
                  <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endif; ?>
              <?php endforeach; ?>
            </select>

            <h6 class="mt-3">📦 Verfügbare Widgets</h6>
            <ul id="available" class="widget-list">
              <?php
              $res = safe_query("SELECT * FROM settings_widgets");
              while($row = mysqli_fetch_assoc($res)){
                echo "<li class='widget-item' data-id='{$row['widget_key']}'>{$row['title']}</li>";
              }
              ?>
            </ul>
          </div>
        </div>

        <!-- Rechte Seite -->
        <div class="col-md-9">
          <div class="mb-3 p-3 border rounded bg-light">
            <h6>🧭 Header</h6>
            <ul id="top" class="widget-list"></ul>
          </div>

          <div class="mb-3 p-3 border rounded bg-light">
            <h6>📍 Navigation</h6>
          </div>

          <div class="mb-3 p-3 border rounded bg-light">
            <h6>📍 Under Top</h6>
            <ul id="undertop" class="widget-list"></ul>
          </div>

          <div class="row">
            <div class="col-md-4">
              <div class="p-3 border rounded bg-light">
                <h6>📥 Leftbar</h6>
                <ul id="left" class="widget-list"></ul>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 border rounded bg-light">
                <h6>📝 Main Content</h6>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-3 border rounded bg-light">
                <h6>📤 Rightbar</h6>
                <ul id="right" class="widget-list"></ul>
              </div>
            </div>
          </div>

          <div class="mt-3 p-3 border rounded bg-light">
            <h6>🔚 Footer</h6>
            <ul id="bottom" class="widget-list"></ul>
          </div>

          <button class="btn btn-primary mt-4" onclick="saveWidgets()">Speichern</button>
        </div>
      </div>
    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {

  const positions = ['left','right','top','undertop','bottom','available'];

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

    fetch('admin_widgets_save.php?mode=load&page=' + encodeURIComponent(page))
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

    fetch('admin_widgets_save.php', {
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


