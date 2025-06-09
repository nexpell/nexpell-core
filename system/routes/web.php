<?php
// DB-Verbindung und Settings holen
$settings_result = safe_query("SELECT use_routing FROM settings LIMIT 1");
$settings = mysqli_fetch_assoc($settings_result);
$useRouting = (isset($settings['use_routing']) && (int)$settings['use_routing'] === 1);

// Routing ein-/ausschalten
if ($useRouting) {
    // === Routing aktiv ===
    include_once("system/classes/Router.php");
    $router = new Router();

    // Automatische Redirects von ?site=xyz zu /xyz
    $routing_enabled = getRoutingSettingFromDB();

    if ($routing_enabled && isset($_GET['site']) && !empty($_GET['site'])) {
        $redirect = '/' . basename($_GET['site']);
        // Umleitungsschleife verhindern
        if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== $redirect) {
            header("Location: $redirect", true, 301);
            exit;
        }
    }

    // Startseite
    $router->get('', function () {
        global $tpl, $mainContent;
        ob_start();
        include $_SERVER['DOCUMENT_ROOT'] . '/includes/modules/startpage.php';
        $mainContent = ob_get_clean();
    });

/*


    $router->get('/edit_profile/:username', function($username) {
    global $tpl, $mainContent;
    $_GET['username'] = $username; // falls nötig

    ob_start();
    $modulePath = $_SERVER['DOCUMENT_ROOT'] . "/includes/modules/edit_profile.php";

    if (file_exists($modulePath)) {
        include $modulePath;
    } else {
        http_response_code(404);
        echo "<h1>404 - Seite nicht gefunden</h1>";
    }

    $mainContent = ob_get_clean();
});

#https://208.webspell-rm.de/index.php?site=profile&userID=2


    $router->get('/edit_profile/:username', function($username) {
    global $tpl, $mainContent;

    // Optional: username als GET-Parameter setzen, falls dein Edit-Modul das braucht
    $_GET['username'] = $username;

    ob_start();
    $modulePath = $_SERVER['DOCUMENT_ROOT'] . "/includes/modules/edit_profile.php";

    if (file_exists($modulePath)) {
        include $modulePath;
    } else {
        http_response_code(404);
        echo "<h1>404 - Seite nicht gefunden</h1>";
    }

    $mainContent = ob_get_clean();
});

*/

    // Dynamische Catch-All Route für alle anderen Seiten (GET + POST)
    $loadModule = function ($page) {
        global $tpl, $mainContent;

        $page = basename($page); // Sicherheit: kein Pfad-Traversal

        $modulePath = $_SERVER['DOCUMENT_ROOT'] . "/includes/modules/$page.php";
        $pluginPath = $_SERVER['DOCUMENT_ROOT'] . "/includes/plugins/$page/$page.php";

        ob_start();

        if (file_exists($modulePath)) {
            include $modulePath;
        } elseif (file_exists($pluginPath)) {
            include $pluginPath;
        } else {
            http_response_code(404);
            echo "<h1>404 - Seite nicht gefunden</h1>";
            echo "<p>Versucht:</p><ul>";
            echo "<li>$modulePath</li>";
            echo "<li>$pluginPath</li>";
            echo "</ul>";
        }

        $mainContent = ob_get_clean();
    };

    $router->get('/:page', $loadModule);
    $router->post('/:page', $loadModule);

    // Dispatch starten
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $uri = rtrim($uri, '/'); // Entferne trailing slash, falls gewünscht
    $router->dispatch($uri, $_SERVER['REQUEST_METHOD']);

} else {
    // === Routing aus ===
    include_once("system/content.php");
    ob_start();
    get_mainContent();  // bestehende Funktion
    $mainContent = ob_get_clean();
}

// Template laden
include $tpl->themes_path . "index.php";
