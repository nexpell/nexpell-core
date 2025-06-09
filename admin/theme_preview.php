<?php

// Überprüfen, ob die Session bereits gestartet wurde
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

#use webspell\AccessControl;

// Admin-Zugriff überprüfen
#AccessControl::checkAdminAccess('ac_theme_preview');

// Konfigurationsdatei sicher einbinden
$configPath = __DIR__ . '/../system/config.inc.php';
if (!file_exists($configPath)) {
    die("Fehler: Konfigurationsdatei nicht gefunden.");
}
require_once $configPath;

// Datenbankverbindung aufbauen
$_database = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Fehlerprüfung
if ($_database->connect_error) {
    die("Verbindung zur Datenbank fehlgeschlagen: " . $_database->connect_error);
}

$themename = 'flatly'; // default
$result = $_database->query("SELECT themename FROM settings_themes WHERE active = '1' LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $themename = $row['themename'];
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Bootswatch Theme-Wechsler</title>

  <link id="bootstrap-css" rel="stylesheet" href="/../includes/themes/default/css/dist/<?= htmlspecialchars($themename) ?>/bootstrap.min.css"/>

  <style>
    .theme-card { cursor: pointer; transition: transform 0.2s ease; position: relative; }
    .theme-card:hover { transform: scale(1.03); }
    .color-box { width: 30px; height: 30px; border-radius: 0.25rem; border: 1px solid #ccc; }
    .theme-preview-colors { margin-top: 0.5rem; }
    #saveMsg { margin-left: 1rem; }
  </style>
</head>
<body class="p-4">
  <div class="container">
    
    <h1 class="mb-4">Theme-Wechsler (Vorschau)</h1>

    <!-- Dropdown -->
    <div class="mb-4">
      <label for="themeSwitcher" class="form-label">Theme auswählen:</label>
      <select class="form-select" id="themeSwitcher">
        <?php
        $themes = ['brite', 'cerulean', 'cosmo', 'cyborg', 'darkly', 'flatly', 'journal', 'litera', 'lumen', 'lux', 'materia'];
        foreach ($themes as $theme) {
            $selected = $themename === $theme ? 'selected' : '';
            echo "<option value=\"$theme\" $selected>" . ucfirst($theme) . "</option>";
        }
        ?>
      </select>
    </div>

    <!-- Vorschaukarten -->
    <div class="row g-3 mb-4" id="themeCards"></div>

    <!-- Demo-Elemente -->
      <h4>Navigation (Demo)</h4>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="navbarStyle" id="nav1" value="bg-primary|light">
        <label class="form-check-label" for="nav1">
          <nav class="navbar navbar-expand-lg bg-primary mb-2" data-bs-theme="light">
            <div class="container-fluid">
              <a class="navbar-brand" href="#">DemoNavbar</a>
            </div>
          </nav>
        </label>
      </div>

      <div class="form-check">
        <input class="form-check-input" type="radio" name="navbarStyle" id="nav2" value="bg-dark|dark">
        <label class="form-check-label" for="nav2">
          <nav class="navbar navbar-expand-lg bg-dark mb-2" data-bs-theme="dark">
            <div class="container-fluid">
              <a class="navbar-brand" href="#">DemoNavbar</a>
            </div>
          </nav>
        </label>
      </div>

      <div class="form-check">
        <input class="form-check-input" type="radio" name="navbarStyle" id="nav3" value="bg-light|light">
        <label class="form-check-label" for="nav3">
          <nav class="navbar navbar-expand-lg bg-light mb-2" data-bs-theme="light">
            <div class="container-fluid">
              <a class="navbar-brand" href="#">DemoNavbar</a>
            </div>
          </nav>
        </label>
      </div>

      <div class="form-check">
        <input class="form-check-input" type="radio" name="navbarStyle" id="nav4" value="bg-body-tertiary|light">
        <label class="form-check-label" for="nav4">
          <nav class="navbar navbar-expand-lg bg-body-tertiary mb-2">
            <div class="container-fluid">
              <a class="navbar-brand" href="#">DemoNavbar</a>
            </div>
          </nav>
        </label>
      </div>
   

    <div class="mb-4">
      <h4>Buttons (Demo)</h4>
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-primary">Primär</button>
        <button class="btn btn-secondary">Sekundär</button>
        <button class="btn btn-success">Erfolg</button>
        <button class="btn btn-danger">Fehler</button>
        <button class="btn btn-warning">Warnung</button>
        <button class="btn btn-info">Info</button>
        <button class="btn btn-light">Hell</button>
        <button class="btn btn-dark">Dunkel</button>
        <button class="btn btn-outline-primary">Umriss</button>
      </div>
    </div>

    <!-- Speichern -->
    <div class="mb-5">
      <button class="btn btn-success" id="saveBtn">Übernehmen</button>
      <span id="saveMsg"></span>

     
              
    </div>
    
  </div>

  <script>
    const themes = ['brite','cerulean','cosmo','cyborg','darkly','flatly','journal','litera','lumen','lux','materia'];
    const themeSelect = document.getElementById("themeSwitcher");
    const themeLink = document.getElementById("bootstrap-css");
    const themeCards = document.getElementById("themeCards");
    const saveBtn = document.getElementById("saveBtn");
    const saveMsg = document.getElementById("saveMsg");

    window.setTheme = function(theme) {
      themeLink.href = `/../includes/themes/default/css/dist/${theme}/bootstrap.min.css?v=${Date.now()}`;
      themeSelect.value = theme;
      saveMsg.textContent = '';
    }

    function createThemeCard(theme) {
      const col = document.createElement("div");
      col.className = "col-md-3";
      col.innerHTML = `
        <div class="card theme-card" id="card-${theme}" onclick="setTheme('${theme}')">
          <div class="theme-preview-colors d-flex gap-1 px-2 pt-2">
            <div class="color-box" data-color="primary"></div>
            <div class="color-box" data-color="secondary"></div>
            <div class="color-box" data-color="success"></div>
          </div>
          <div class="card-body">
            <h5 class="card-title">${theme.charAt(0).toUpperCase() + theme.slice(1)}</h5>
          </div>
        </div>
      `;
      themeCards.appendChild(col);
      updateThemePreviewColors(theme, col);
    }

    function updateThemePreviewColors(theme, col) {
      const tempLink = document.createElement("link");
      tempLink.rel = "stylesheet";
      tempLink.href = `/../includes/themes/default/css/dist/${theme}/bootstrap.min.css?v=${Date.now()}`;
      document.head.appendChild(tempLink);

      tempLink.onload = () => {
        const tempDiv = document.createElement("div");
        tempDiv.style.position = "absolute";
        tempDiv.style.visibility = "hidden";
        document.body.appendChild(tempDiv);

        // Hintergrundfarbe der Karte (bg-body)
        tempDiv.className = 'bg-body';
        const cardBg = getComputedStyle(tempDiv).backgroundColor;
        const card = col.querySelector(".theme-card");
        if (card) {
          card.style.backgroundColor = cardBg;
          card.style.color = getContrastYIQ(cardBg);
        }

        // Farben für .color-box setzen
        ["primary", "secondary", "success"].forEach(color => {
          tempDiv.className = `bg-${color}`;
          const bgColor = getComputedStyle(tempDiv).backgroundColor;
          const box = col.querySelector(`.color-box[data-color="${color}"]`);
          if (box) box.style.backgroundColor = bgColor;
        });

        document.body.removeChild(tempDiv);
        document.head.removeChild(tempLink);
      };
    }

    function getContrastYIQ(color) {
      const rgb = color.replace(/[^\d,]/g, '').split(',').map(Number);
      const yiq = ((rgb[0] * 299) + (rgb[1] * 587) + (rgb[2] * 114)) / 1000;
      return yiq >= 128 ? 'black' : 'white';
    }

    themes.forEach(createThemeCard);

    themeSelect.addEventListener("change", () => {
      setTheme(themeSelect.value);
    });

    saveBtn.addEventListener("click", () => {
  const selectedTheme = themeSelect.value;
  const navbarRadio = document.querySelector('input[name="navbarStyle"]:checked');
  const selectedNavbar = navbarRadio ? navbarRadio.value : '';

  const params = new URLSearchParams();
  params.append("theme", selectedTheme);
  params.append("navbar", selectedNavbar);

  fetch("theme_save.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: params.toString()
  })
  .then(res => res.text())
  .then(msg => {
    console.log("Antwort vom Server:", msg);
    if (msg.trim() === "OK") {
      saveMsg.textContent = "Theme & Navbar gespeichert!";
      saveMsg.className = "text-success";
    } else {
      saveMsg.textContent = msg;
      saveMsg.className = "text-danger";
    }
  })
  .catch((err) => {
    console.error("Fetch-Fehler:", err);
    saveMsg.textContent = "Fehler beim Speichern.";
    saveMsg.className = "text-danger";
  });
});



  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
