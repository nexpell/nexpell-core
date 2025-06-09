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

namespace webspell;

class SpamApi
{
    // Konstanten für Spam-Status
    const NOSPAM = 0;
    const SPAM = 1;

    // Singleton-Instanz
    private static $instance;

    // API-Key für die Authentifizierung
    private $key;

    // URL der Spam-API
    private $host;

    // Gibt an, ob die API aktiviert ist
    private $enabled;

    // Gibt an, ob Beiträge blockiert werden sollen, wenn sie nicht verifiziert werden können
    private $blockOnError;

    // Maximale Anzahl an Beiträgen, die ein Benutzer senden darf
    private $maxPosts;

    /**
     * Gibt die Singleton-Instanz der SpamApi zurück.
     * 
     * @return SpamApi Die Singleton-Instanz
     */
    final public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $class = __CLASS__;
            self::$instance = new $class;
        }
        return self::$instance;
    }

    // Verhindert das Klonen der Singleton-Instanz
    private function __clone()
    {
    }

    /**
     * Lernt, ob eine Nachricht Spam oder kein Spam (Ham) ist.
     * 
     * @param string $message Der Text, der gelernt werden soll
     * @param int $type Der Typ: SpamApi::SPAM oder SpamApi::NOSPAM
     */
    public function learn($message, $type)
    {
        if ($this->enabled && $this->key) {
            $postdata = array();
            $postdata["apikey"] = $this->key;
            $postdata["learn"] = json_encode(array("message" => $message, "type" => $type));
            $response = $this->postRequest($postdata);
            if (!empty($response)) {
                $json = json_decode($response, true);
                if ($json['response'] != "ok") {
                    $this->logError($response, $postdata);
                }
            }
        }
    }

    /**
     * Validiert eine Nachricht, um festzustellen, ob sie Spam ist.
     * 
     * @param string $message Der Text, der validiert werden soll
     * @return int SpamApi::SPAM oder SpamApi::NOSPAM
     */
    public function validate($message)
    {
        if ($this->enabled) {
            $run = true;
            // Überprüfen, ob der Benutzer bereits Beiträge überschreitet
            if ($GLOBALS['loggedin']) {
                if (getuserforumposts($GLOBALS['userID']) + getallusercomments($GLOBALS['userID']) > $this->maxPosts) {
                    $run = false;
                }
            }
            if ($run) {
                $ret = self::NOSPAM;
                $postdata = array();
                $postdata["validate"] = json_encode(array("message" => $message));
                $response = $this->postRequest($postdata);
                if (!empty($response)) {
                    $json = json_decode($response, true);
                    if ($json['response'] != "ok") {
                        $this->logError($response, $postdata);
                        if ($this->blockOnError) {
                            $ret = self::SPAM;
                        }
                    } else {
                        $rating = (float)$json["response"];
                        if ($rating >= $GLOBALS['spamCheckRating']) {
                            $ret = self::SPAM;
                        }
                    }
                } else {
                    if ($this->blockOnError) {
                        $ret = self::SPAM;
                    }
                }
                return $ret;
            }
            return self::NOSPAM;
        }
        return self::NOSPAM;
    }

    /**
     * Schreibt eine Fehlermeldung in das Log.
     * 
     * @param string $message Die Fehlermeldung
     * @param array $data Die Daten, die mit dem Fehler verbunden sind
     */
    private function logError($message, $data)
    {
        // Loggt die Fehlermeldung in die Tabelle `api_log`
        safe_query(
            "INSERT INTO
                `api_log` (`message`, `date`, `data`)
            VALUES (
                '" . addslashes($message) . "',
                '" . time() . "',
                '" . json_encode($data) . "'
            )"
        );
    }

    /**
     * Führt eine POST-Anfrage an die Spam-API aus.
     * 
     * @param array $data Die zu sendenden Daten
     * @return string Die Antwort der API
     */
    private function postRequest($data)
    {
        // Überprüft, ob cURL verfügbar ist und führt die Anfrage aus
        if (function_exists("curl_init")) {
            $ch = curl_init($this->host);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (stripos($this->host, "https") === 0) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CAINFO, "src/ca.pem");
            }
            $response = curl_exec($ch);
            if ($response !== false) {
                curl_close($ch);
                return $response;
            } else {
                $this->logError("No Api-Response. " . curl_error($ch), $data);
                curl_close($ch);
                return "";
            }
        } elseif (include("HTTP/Request2.php") && class_exists("HTTP_Request2")) {
            // Alternative Methode für HTTP-Request2
            $request = new \HTTP_Request2($this->host, \HTTP_Request2::METHOD_POST);
            if (stripos($this->host, "https") === 0) {
                $request->setConfig(array("ssl_cafile" => "src/ca.pem", "ssl_verify_peer" => false));
            }
            $url = $request->getUrl();
            $url->setQueryVariables($data);
            try {
                return $request->send()->getBody();
            } catch (\Exception $ex) {
                $this->logError("No Api-Response. Code: " . $ex->getCode() . ", Message: " . $ex->getMessage(), $data);
                return "";
            }
        } elseif (class_exists("HttpRequest")) {
            // Alternative Methode für HttpRequest
            $request = new \HttpRequest($this->host, \HttpRequest::METH_POST);
            $request->addPostFields($data);
            try {
                return $request->send()->getBody();
            } catch (\Exception $ex) {
                $this->logError("No Api-Response. Code: " . $ex->getCode() . ", Message: " . $ex->getMessage(), $data);
                return "";
            }
        } elseif (ini_get("allow_url_fopen")) {
            // Fallback für URL-Fopen
            $build_data = http_build_query($data);
            $params = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded",
                    'content' => $build_data
                )
            );
            $context = stream_context_create($params);
            $con = file_get_contents($this->host, false, $context);
            if ($con !== false) {
                return $con;
            } else {
                $this->logError("No Api-Response.", $data);
                return "";
            }
        } else {
            $this->logError(
                "No Method available to query Api.",
                "Enable Curl or Pear HTTP Request(2) or allow_url_fopen"
            );
            return "";
        }
    }
}
