<?php



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

