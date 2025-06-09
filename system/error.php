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

ob_start();

/**
 * Gibt einen detaillierten Call-Trace zurück, um Fehlerquellen zu analysieren.
 *
 * @return string
 */
function generateCallTrace()
{
    $trace = debug_backtrace();
    $trace = array_reverse($trace);

    // Entferne Aufruf dieser Funktion selbst + system_error
    array_pop($trace);
    array_pop($trace);

    $basepath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
    $result = [];

    foreach ($trace as $entry) {
        $line = isset($entry['file']) ? str_replace($basepath, '', $entry['file']) : '[internal]';
        $line .= isset($entry['line']) ? '(' . $entry['line'] . '): ' : ': ';
        $line .= "<b>" . $entry['function'] . "</b>(";

        $params = [];
        if (isset($entry['args'])) {
            foreach ($entry['args'] as $param) {
                if (is_scalar($param) || is_null($param)) {
                    $params[] = htmlspecialchars(var_export($param, true));
                } else {
                    $params[] = htmlspecialchars(gettype($param));
                }
            }
        }

        $line .= implode(", ", $params) . ")";
        $result[] = $line;
    }

    return implode("\n", $result);
}

/**
 * Zeigt eine Fehlerseite an und beendet das Skript.
 *
 * @param string $text   Fehlermeldung, die ausgegeben werden soll
 * @param int    $system 1 = Systeminformationen anzeigen (Standard), 0 = einfache Seite
 * @param int    $strace 1 = Stacktrace anzeigen, 0 = ohne Trace
 */
function system_error($text, $system = 1, $strace = 0)
{
    ob_clean(); // vorherigen Output verwerfen
    global $_database;

    $trace = $strace ? '<pre>' . generateCallTrace() . '</pre>' : '';

    // Fehlerseite mit Systeminformationen
    if ($system) {
        if (file_exists('system/version.php')) {
            include('system/version.php');
        } elseif (file_exists('../system/version.php')) {
            include('../system/version.php');
        } else {
            $version = 'unknown';
        }

        $info = '<h1>Error 404</h1>
            <p>Die angefragte Seite konnte nicht gefunden werden.<br>
            The requested page could not be found.<br>
            La pagina richiesta non è stata trovata.<br>
            <a class="btn btn-success" href="index.php">back</a>
            <br> Version: ' . (isset($version) ? $version : 'unknown') . ', PHP Version: ' . phpversion();

        if (!mysqli_connect_error()) {
            $info .= ', MySQL Version: ' . htmlspecialchars($_database->server_info);
        }
    } else {
        // Einfache Fehlerseite ohne Versionsinfos
        $info = '<h1>Error 404</h1>
            <p>Die angefragte Seite konnte nicht gefunden werden.<br>
            The requested page could not be found.<br>
            La pagina richiesta non è stata trovata.<br>
            <a class="btn btn-success" href="index.php">back</a>';
    }

    // HTML-Ausgabe der Fehlerseite
    die('<!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="description" content="Clanpage using webSPELL RM CMS">
            <meta name="author" content="webspell.org">
            <meta name="generator" content="webSPELL-RM">
            <title>webSPELL-RM - Error</title>
            <link href="./components/bootstrap/css/bootstrap.min.css" rel="stylesheet">
            <link href="./components/css/lockpage.css" rel="stylesheet" type="text/css">
        </head>
        <body>
        <div class="lock_wrapper">
            <div class="container text-center">
                <div class="col-lg-12">
                    <img class="img-responsive" src="images/logo.png" alt="logo"/>
                    <div class="shdw"></div>
                </div>
                ' . $info . '</p>
                <h4>INFO</h4>
                <div class="alert alert-danger" role="alert">
                    <strong>Ein Fehler ist aufgetreten<br>An error has occurred<br>Si è verificato un errore</strong>
                </div>
                <div class="alert alert-info" role="alert">' . $text . '</div>
                <div class="alert alert-warning" role="alert">' . $trace . '</div>
            </div>
        </div>
        </body>
        </html>');
}
