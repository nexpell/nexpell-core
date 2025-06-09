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

namespace {

    // Überprüft, ob die Konstante PASSWORD_BCRYPT definiert ist
    if (!defined('PASSWORD_BCRYPT')) {
        define('PASSWORD_BCRYPT', 1);
        define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);
        define('PASSWORD_BCRYPT_DEFAULT_COST', 10);
    }

    // Überprüft, ob die Funktion password_hash existiert, falls nicht, wird sie definiert
    if (!function_exists('password_hash')) {

        /**
         * Hash das Passwort mit dem angegebenen Algorithmus.
         *
         * @param string $password Das zu hashende Passwort.
         * @param int $algo Der Algorithmus, der verwendet werden soll (definiert durch PASSWORD_* Konstanten).
         * @param array $options Die Optionen für den Algorithmus.
         *
         * @return string|false Das gehashte Passwort oder false im Fehlerfall.
         */
        function password_hash($password, $algo, array $options = array()) {
            // Überprüft, ob die Funktion 'crypt' existiert
            if (!function_exists('crypt')) {
                trigger_error("Crypt muss geladen sein, damit password_hash funktioniert", E_USER_WARNING);
                return null;
            }

            // Überprüft, ob das Passwort eine gültige Zeichenkette ist
            if (is_null($password) || is_int($password)) {
                $password = (string) $password;
            }
            if (!is_string($password)) {
                trigger_error("password_hash(): Passwort muss eine Zeichenkette sein", E_USER_WARNING);
                return null;
            }

            // Verarbeitet den Algorithmus und die Kostenoptionen
            $resultLength = 0;
            switch ($algo) {
                case PASSWORD_BCRYPT:
                    $cost = PASSWORD_BCRYPT_DEFAULT_COST;
                    if (isset($options['cost'])) {
                        $cost = (int) $options['cost'];
                        if ($cost < 4 || $cost > 31) {
                            trigger_error(sprintf("password_hash(): Ungültiger Bcrypt-Kostenparameter: %d", $cost), E_USER_WARNING);
                            return null;
                        }
                    }
                    // Setzt den Salt-Längenwert und das Hash-Format
                    $raw_salt_len = 16;
                    $required_salt_len = 22;
                    $hash_format = sprintf("$2y$%02d$", $cost);
                    $resultLength = 60;
                    break;
                default:
                    trigger_error(sprintf("password_hash(): Unbekannter Passwort-Hashing-Algorithmus: %s", $algo), E_USER_WARNING);
                    return null;
            }

            // Verarbeitet den Salt und prüft die Länge
            $salt_req_encoding = false;
            $salt = '';
            if (isset($options['salt'])) {
                $salt = (string) $options['salt'];
                if (strlen($salt) < $required_salt_len) {
                    trigger_error(sprintf("password_hash(): Der angegebene Salt ist zu kurz: %d, erwartet %d", strlen($salt), $required_salt_len), E_USER_WARNING);
                    return null;
                }
            } else {
                // Generiert einen zufälligen Salt
                $salt = bin2hex(random_bytes($raw_salt_len));
                $salt_req_encoding = true;
            }

            // Kodiert den Salt, falls erforderlich
            if ($salt_req_encoding) {
                $salt = strtr(rtrim(base64_encode($salt), '='), 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/', './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
            }

            // Erzeugt das finale Hash
            $hash = $hash_format . $salt;
            $ret = crypt($password, $hash);

            // Überprüft, ob das Resultat korrekt ist
            if (!is_string($ret) || strlen($ret) != $resultLength) {
                return false;
            }

            return $ret;
        }

        /**
         * Gibt Informationen über den Passwort-Hash zurück.
         *
         * @param string $hash Der Passwort-Hash.
         *
         * @return array Ein Array mit Informationen über den Hash.
         */
        function password_get_info($hash) {
            $return = array(
                'algo' => 0,
                'algoName' => 'unknown',
                'options' => array(),
            );

            // Prüft, ob der Hash ein Bcrypt-Hash ist
            if (substr($hash, 0, 4) == '$2y$' && strlen($hash) == 60) {
                $return['algo'] = PASSWORD_BCRYPT;
                $return['algoName'] = 'bcrypt';
                list($cost) = sscanf($hash, "$2y$%d$");
                $return['options']['cost'] = $cost;
            }

            return $return;
        }

        /**
         * Überprüft, ob der Passwort-Hash erneut gehasht werden muss.
         *
         * @param string $hash Der zu überprüfende Hash.
         * @param int $algo Der verwendete Algorithmus.
         * @param array $options Die Optionen.
         *
         * @return boolean True, wenn der Hash neu erstellt werden muss.
         */
        function password_needs_rehash($hash, $algo, array $options = array()) {
            $info = password_get_info($hash);
            if ($info['algo'] !== (int) $algo) {
                return true;
            }

            switch ($algo) {
                case PASSWORD_BCRYPT:
                    $cost = isset($options['cost']) ? (int) $options['cost'] : PASSWORD_BCRYPT_DEFAULT_COST;
                    if ($cost !== $info['options']['cost']) {
                        return true;
                    }
                    break;
            }

            return false;
        }

        /**
         * Überprüft ein Passwort gegen einen Hash.
         *
         * @param string $password Das zu überprüfende Passwort.
         * @param string $hash Der Hash, gegen den überprüft werden soll.
         *
         * @return boolean True, wenn das Passwort übereinstimmt.
         */
        function password_verify($password, $hash) {
            if (!function_exists('crypt')) {
                trigger_error("Crypt muss geladen sein, damit password_verify funktioniert", E_USER_WARNING);
                return false;
            }

            // Vergleicht das Passwort mit dem Hash
            $ret = crypt($password, $hash);
            if (!is_string($ret) || strlen($ret) != strlen($hash) || strlen($ret) <= 13) {
                return false;
            }

            $status = 0;
            for ($i = 0; $i < strlen($ret); $i++) {
                $status |= (ord($ret[$i]) ^ ord($hash[$i]));
            }

            return $status === 0;
        }
    }
}

namespace PasswordCompat\binary {

    if (!function_exists('PasswordCompat\\binary\\_strlen')) {

        /**
         * Zählt die Anzahl der Bytes in einer Zeichenkette
         *
         * @param string $binary_string Die Eingabezeichenkette
         *
         * @internal
         * @return int Die Anzahl der Bytes
         */
        function _strlen($binary_string) {
            if (function_exists('mb_strlen')) {
                return mb_strlen($binary_string, '8bit');
            }
            return strlen($binary_string);
        }

        /**
         * Gibt einen Teilstring basierend auf Byte-Grenzen zurück
         *
         * @see _strlen()
         *
         * @param string $binary_string Die Eingabezeichenkette
         * @param int $start Der Startindex
         * @param int $length Die Länge des Substrings
         *
         * @internal
         * @return string Der Substring
         */
        function _substr($binary_string, $start, $length) {
            if (function_exists('mb_substr')) {
                return mb_substr($binary_string, $start, $length, '8bit');
            }
            return substr($binary_string, $start, $length);
        }

        /**
         * Überprüft, ob die aktuelle PHP-Version mit der Bibliothek kompatibel ist
         *
         * @return boolean Das Ergebnis der Überprüfung
         */
        function check() {
            static $pass = NULL;

            if (is_null($pass)) {
                if (function_exists('crypt')) {
                    $hash = '$2y$04$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
                    $test = crypt("password", $hash);
                    $pass = $test == $hash;
                } else {
                    $pass = false;
                }
            }
            return $pass;
        }
    }
}
