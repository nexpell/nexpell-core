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
 * Gibt die Anzahl neuer Nachrichten für einen Benutzer zurück.
 *
 * @param int $userID Die Benutzer-ID, für die die neuen Nachrichten gezählt werden.
 * @return int Die Anzahl der neuen Nachrichten.
 */
function getnewmessages($userID)
{
    // Zählt die ungelesenen Nachrichten für den angegebenen Benutzer
    return mysqli_num_rows(
        safe_query(
            "SELECT
                messageID
            FROM
                `" . PREFIX . "plugins_messenger`
            WHERE
                `touser` = " . (int)$userID . " AND
                `userID` = " . (int)$userID . " AND
                `viewed` = 0"
        )
    );
}

/**
 * Sendet eine Nachricht an einen Benutzer.
 *
 * @param int $touser Die Benutzer-ID des Empfängers.
 * @param string $title Der Titel der Nachricht.
 * @param string $message Der Inhalt der Nachricht.
 * @param string $from Die Benutzer-ID des Absenders (standardmäßig '0' für Systemnachricht).
 */
function sendmessage($touser, $title, $message, $from = '0')
{
    global $hp_url, $admin_email, $admin_name, $hp_title;

    // Initialisiert die Sprachübersetzungen
    $_language_tmp = new \webspell\Language();
    $systemmail = false;

    // Wenn kein Absender angegeben wurde, wird es als Systemnachricht betrachtet
    if (!$from) {
        $systemmail = true;
        $from = '0';
    }

    // Nachricht an den Absender selbst senden
    if (!$systemmail) {
        safe_query(
            "INSERT INTO
                `" . PREFIX . "plugins_messenger` (`userID`, `date`, `fromuser`, `touser`, `title`, `message`, `viewed`)
            VALUES (
                '$from',
                '" . time() . "',
                '$from',
                '$touser',
                '$title',
                '" . $message . "',
                '0'
            )"
        );
        safe_query("UPDATE `" . PREFIX . "users` SET pmsent = pmsent + 1 WHERE userID = '$from'");
    }

    // Nachricht an den Empfänger senden (wenn der Absender nicht der Empfänger ist oder es eine Systemnachricht ist)
    if ($touser != $from || $systemmail) {
        safe_query(
            "INSERT INTO
                `" . PREFIX . "plugins_messenger` (`userID`, `date`, `fromuser`, `touser`, `title`, `message`, `viewed`)
            VALUES (
                '$touser',
                '" . time() . "',
                '$from',
                '$touser',
                '$title',
                '" . $message . "',
                '0'
            )"
        );
    }

    // Aktualisiert die Anzahl empfangener Nachrichten des Empfängers
    safe_query("UPDATE `" . PREFIX . "users` SET pmgot = pmgot + 1 WHERE userID = '$touser'");

    // Sendet eine E-Mail an den Empfänger, wenn dieser offline ist und E-Mail-Benachrichtigungen aktiviert hat
    if (wantmail($touser) && isonline($touser) == "offline") {
        // Holt die E-Mail-Adresse und Sprache des Empfängers
        $ds = mysqli_fetch_array(
            safe_query(
                "SELECT `email`, `language` FROM `" . PREFIX . "users` WHERE `userID` = " . (int)$touser
            )
        );

        // Setzt die Sprache des Empfängers und lädt die Übersetzungen
        $_language_tmp->setLanguage($ds['language']);
        $_language_tmp->readModule('messenger');

        // Ersetzt Platzhalter im E-Mail-Inhalt
        @$mail_body = str_replace("%nickname%", getnickname($touser), $_language_tmp->module['mail_body']);
        @$mail_body = str_replace("%hp_url%", $hp_url, $mail_body);
        @$subject = $hp_title . ': ' . $_language_tmp->module['mail_subject'];

        // Sendet die E-Mail
        \webspell\Email::sendEmail($admin_email, 'Messenger', $ds['email'], $subject, $mail_body);
    }
}
