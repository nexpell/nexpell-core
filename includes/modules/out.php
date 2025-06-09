<?php
/**
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 *                  Webspell-RM      /                        /   /                                          *
 *                  -----------__---/__---__------__----__---/---/-----__---- _  _ -                         *
 *                   | /| /  /___) /   ) (_ `   /   ) /___) /   / __  /     /  /  /                          *
 *                  _|/_|/__(___ _(___/_(__)___/___/_(___ _/___/_____/_____/__/__/_                          *
 *                               Free Content / Management System                                            *
 *                                           /                                                               *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @version         webspell-rm                                                                              *
 *                                                                                                           *
 * @copyright       2018-2025 by webspell-rm.de                                                              *
 * @support         For Support, Plugins, Templates and the Full Script visit webspell-rm.de                 *
 * @website         <https://www.webspell-rm.de>                                                             *
 * @forum           <https://www.webspell-rm.de/forum.html>                                                  *
 * @wiki            <https://www.webspell-rm.de/wiki.html>                                                   *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @license         Script runs under the GNU GENERAL PUBLIC LICENCE                                         *
 *                  It's NOT allowed to remove this copyright-tag                                            *
 *                  <http://www.fsf.org/licensing/licenses/gpl.html>                                         *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
 * @author          Code based on WebSPELL Clanpackage (Michael Gruber - webspell.at)                        *
 * @copyright       2005-2011 by webspell.org / webspell.info                                                *
 *                                                                                                           *
 *¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯*
*/

<?php
// Basisverzeichnis setzen
chdir("../../");

// Fehlerzähler initialisieren
$err = 0;

// Wichtige Systemdateien einbinden
if (file_exists("system/sql.php")) {
    include("system/sql.php");
} else {
    $err++;
}

if (file_exists("system/settings.php")) {
    include("system/settings.php");
} else {
    $err++;
}

if (file_exists("system/functions.php")) {
    include("system/functions.php");
} else {
    $err++;
}

// Temporär Wartungsmodus deaktivieren
$closed_tmp = $closed;
$closed = 0;

// Sprachdatei für Ausgabeseite laden
$_language->readModule('out');

// Aktuelles Protokoll (http oder https) bestimmen
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

// Ziel-URL initialisieren
$target = null;

// Banner-Link
if (isset($_GET['bannerID']) && is_numeric($_GET['bannerID'])) {
    $bannerID = (int)$_GET['bannerID'];

    // Klick zählen
    safe_query("UPDATE `plugins_bannerrotation` SET `hits` = `hits` + 1 WHERE `bannerID` = '$bannerID'");

    // URL ermitteln
    $result = safe_query("SELECT `bannerurl` FROM `plugins_bannerrotation` WHERE `bannerID` = '$bannerID'");
    if ($ds = mysqli_fetch_assoc($result)) {
        $target = $protocol . '://' . preg_replace('#^https?://#', '', $ds['bannerurl']);
    }
}

// Partner-Link
if (isset($_GET['partnerID']) && is_numeric($_GET['partnerID'])) {
    $partnerID = (int)$_GET['partnerID'];

    // Klick zählen
    safe_query("UPDATE `plugins_partners` SET `hits` = `hits` + 1 WHERE `partnerID` = '$partnerID'");

    // URL ermitteln
    $result = safe_query("SELECT `url` FROM `plugins_partners` WHERE `partnerID` = '$partnerID'");
    if ($ds = mysqli_fetch_assoc($result)) {
        $target = $protocol . '://' . preg_replace('#^https?://#', '', $ds['url']);
    }
}

// Sponsor-Link
if (isset($_GET['sponsorID']) && is_numeric($_GET['sponsorID'])) {
    $sponsorID = (int)$_GET['sponsorID'];

    // Klick zählen
    safe_query("UPDATE `plugins_sponsors` SET `hits` = `hits` + 1 WHERE `sponsorID` = '$sponsorID'");

    // URL ermitteln
    $result = safe_query("SELECT `url` FROM `plugins_sponsors` WHERE `sponsorID` = '$sponsorID'");
    if ($ds = mysqli_fetch_assoc($result)) {
        $target = $protocol . '://' . preg_replace('#^https?://#', '', $ds['url']);
    }
}

// Weiterleitung durchführen
if (!empty($target)) {
    header("Location: $target");
} else {
    // Fallback-Weiterleitung, wenn keine ID erkannt wurde
    $fallback = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: $fallback");
}

exit;
