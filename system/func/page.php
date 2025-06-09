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
 * @copyright       2018-2023 by webspell-rm.de                                                              *
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

/**
 * Führt eine Weiterleitung zu einer angegebenen URL durch.
 *
 * @param string $url Die Ziel-URL für die Weiterleitung. 
 *                    Wenn 'back', wird die Seite zurückgeladen.
 * @param string $info Die Information, die angezeigt wird, bevor die Weiterleitung erfolgt.
 * @param int $time Die Zeit in Sekunden, bis die Weiterleitung erfolgt (Standard: 1 Sekunde).
 */
function redirect($url, $info, $time = 1)
{
    if ($url == "back" && $info != '' && isset($_SERVER['HTTP_REFERER'])) {
        // Wenn die URL 'back' ist und ein Referer existiert, leite zur vorherigen Seite weiter.
        $url = $_SERVER['HTTP_REFERER'];
        $info = '';
    } elseif ($url == "back" && $info != '') {
        // Wenn die URL 'back' ist, aber kein Referer existiert, benutze die angegebene Info als URL.
        $url = $info;
        $info = '';
    }
    
    // Zeigt eine Weiterleitung mit einer Nachricht an
    echo
        '<meta http-equiv="refresh" content="' . $time . ';URL=' . $url . '"><br />' .
        '<p style="color:#000000">' . $info . '</p><br /><br />';
}

/**
 * Überprüft, ob die aktuelle Seite eine statische Seite ist.
 *
 * @param int|null $staticID Die ID der statischen Seite, die überprüft wird (optional).
 * @return bool Gibt true zurück, wenn es sich um eine statische Seite handelt, andernfalls false.
 */
function isStaticPage($staticID = null)
{
    // Prüft, ob der aktuelle Seitentyp "static" ist
    if ($GLOBALS['site'] != "static") {
        return false;
    }

    // Wenn eine statische ID angegeben ist, vergleiche sie mit der aktuellen URL
    if ($staticID !== null) {
        if ($_GET['staticID'] != $staticID) {
            return false;
        }
    }

    return true;
}

/**
 * Generiert eine HTML-Alert-Box.
 *
 * @param string $text Der Text, der in der Box angezeigt wird.
 * @param string $class Die CSS-Klasse für das Aussehen der Box (Standard: 'alert-warning').
 * @param bool $dismissible Gibt an, ob die Box geschlossen werden kann (Standard: false).
 * @return string Der HTML-Code für die Alert-Box.
 */
function generateAlert($text, $class = 'alert-warning', $dismissible = false)
{
    $classes = 'alert ' . $class;
    
    // Wenn die Box schließbar sein soll, füge die Schaltfläche hinzu
    if ($dismissible) {
        $classes .= ' alert-dismissible';
    }

    // Erstelle den HTML-Code für die Alert-Box
    $return = '<div class="' . $classes . '" role="alert">';
    if ($dismissible) {
        $return .= '<button type="button" class="close" data-dismiss="alert">';
        $return .= '<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>';
        $return .= '</button>';
    }
    $return .= $text;
    $return .= '</div>';
    return $return;
}

/**
 * Generiert eine Fehler-Alert-Box.
 *
 * @param string $message Die Fehlermeldung, die angezeigt wird.
 * @param bool $dismissible Gibt an, ob die Box geschlossen werden kann (Standard: false).
 * @return string Der HTML-Code für die Fehler-Alert-Box.
 */
function generateErrorBox($message, $dismissible = false)
{
    return generateAlert($message, 'alert-danger', $dismissible);
}

/**
 * Generiert eine Erfolg-Alert-Box.
 *
 * @param string $message Die Erfolgsmeldung, die angezeigt wird.
 * @param bool $dismissible Gibt an, ob die Box geschlossen werden kann (Standard: false).
 * @return string Der HTML-Code für die Erfolg-Alert-Box.
 */
function generateSuccessBox($message, $dismissible = false)
{
    return generateAlert($message, 'alert-success', $dismissible);
}

/**
 * Generiert eine Fehler-Alert-Box basierend auf einer Fehlerliste.
 *
 * @param string $intro Die Einleitung der Fehlermeldung.
 * @param array $errors Die Liste der Fehler, die angezeigt werden sollen.
 * @param bool $dismissible Gibt an, ob die Box geschlossen werden kann (Standard: false).
 * @return string Der HTML-Code für die Fehler-Alert-Box.
 */
function generateErrorBoxFromArray($intro, $errors, $dismissible = false)
{
    $message = '<strong>' . $intro . ':</strong><br/><ul>';
    foreach ($errors as $error) {
        $message .= '<li>' . $error . '</li>';
    }
    $message .= '</ul>';
    return generateAlert($message, 'alert-danger', $dismissible);
}

/**
 * Generiert eine allgemeine Alert-Box basierend auf einer Fehlerliste und einer Klasse.
 *
 * @param string $intro Die Einleitung der Nachricht.
 * @param string $class Die CSS-Klasse für die Box.
 * @param array $errors Die Liste der Nachrichten, die angezeigt werden sollen.
 * @param bool $dismissible Gibt an, ob die Box geschlossen werden kann (Standard: false).
 * @return string Der HTML-Code für die Alert-Box.
 */
function generateBoxFromArray($intro, $class, $errors, $dismissible = false)
{
    $message = '<strong>' . $intro . ':</strong><br/><ul>';
    foreach ($errors as $error) {
        $message .= '<li>' . $error . '</li>';
    }
    $message .= '</ul>';
    return generateAlert($message, $class, $dismissible);
}
