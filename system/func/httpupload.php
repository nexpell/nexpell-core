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

/**
 * Klasse zum Hochladen von Dateien
 */
class HttpUpload extends Upload
{
    // Das Feld, das die Datei enthält
    private $field;

    /**
     * Konstruktor der Klasse
     * Initialisiert das Feld und Fehlerbehandlung
     *
     * @param string $field_name - Der Name des Formularfeldes
     */
    public function __construct($field_name)
    {
        parent::__construct();
        $this->field = $field_name;
        $this->error = $_FILES[$this->field]['error'];
    }

    /**
     * Überprüft, ob eine Datei hochgeladen wurde
     *
     * @return bool - True, wenn eine Datei hochgeladen wurde, sonst False
     */
    public function hasFile()
    {
        return (isset($_FILES[$this->field]) && $_FILES[$this->field]['error'] != UPLOAD_ERR_NO_FILE);
    }

    /**
     * Überprüft, ob beim Hochladen der Datei ein Fehler aufgetreten ist
     *
     * @return bool - True, wenn ein Fehler aufgetreten ist, sonst False
     */
    public function hasError()
    {
        return $_FILES[$this->field]['error'] !== UPLOAD_ERR_OK;
    }

    /**
     * Gibt den Fehlercode zurück, falls ein Fehler aufgetreten ist
     *
     * @return int|null - Der Fehlercode oder null, wenn kein Fehler vorliegt
     */
    public function getError()
    {
        if ($this->hasFile()) {
            return $_FILES[$this->field]['error'];
        } else {
            return null;
        }
    }

    /**
     * Gibt den temporären Dateipfad der hochgeladenen Datei zurück
     *
     * @return string - Der Pfad zur temporären Datei
     */
    public function getTempFile()
    {
        return $_FILES[$this->field]['tmp_name'];
    }

    /**
     * Gibt den Dateinamen der hochgeladenen Datei zurück
     *
     * @return string - Der Name der Datei
     */
    public function getFileName()
    {
        return basename($_FILES[$this->field]['name']);
    }

    /**
     * Gibt die Größe der hochgeladenen Datei zurück
     *
     * @return int - Die Größe der Datei in Bytes
     */
    public function getSize()
    {
        return $_FILES[$this->field]['size'];
    }

    /**
     * Speichert die hochgeladene Datei an einem neuen Ort
     *
     * @param string $newFilePath - Der Zielpfad, an dem die Datei gespeichert werden soll
     * @param bool $override - Gibt an, ob die Datei überschrieben werden soll, falls sie bereits existiert
     * @return bool - True, wenn die Datei erfolgreich gespeichert wurde, sonst False
     */
    public function saveAs($newFilePath, $override = true)
    {
        if (!file_exists($newFilePath) || $override) {
            return move_uploaded_file($this->getTempFile(), $newFilePath);
        } else {
            return false;
        }
    }

    /**
     * Gibt den MIME-Typ der Datei zurück
     * Falls der MIME-Typ nicht festgelegt wurde, gibt die Methode den Standard-Typ der Datei zurück
     *
     * @return string - Der MIME-Typ der Datei
     */
    protected function getFallbackMimeType()
    {
        return $_FILES[$this->field]['type'];
    }
}
