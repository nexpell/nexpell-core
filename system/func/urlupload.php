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

class UrlUpload extends Upload
{
    // Temporäre Datei und Dateiinformationen
    private $tempFile;
    private $file;
    private $fileName;

    /**
     * Konstruktor für die UrlUpload-Klasse.
     * Lädt die Datei von einer URL herunter.
     * @param string $url Die URL der Datei, die heruntergeladen werden soll.
     */
    public function __construct($url)
    {
        parent::__construct();
        $this->file = $url;
        $this->error = UPLOAD_ERR_NO_FILE;
        $this->download();
    }

    /**
     * Lädt die Datei von der angegebenen URL herunter.
     * Speichert sie als temporäre Datei.
     */
    private function download()
    {
        if (empty($this->file) === false) {
            // Erzeugt eine temporäre Datei
            $this->tempFile = tempnam('tmp/', 'upload_');
            // Extrahiert den Dateinamen aus der URL
            $this->fileName = basename(parse_url($this->file, PHP_URL_PATH));
            // Kopiert die Datei von der URL in die temporäre Datei
            if (copy($this->file, $this->tempFile)) {
                $this->error = UPLOAD_ERR_OK; // Erfolg
            } else {
                $this->error = self::UPLOAD_ERR_CANT_READ; // Fehler beim Lesen
            }
        } else {
            $this->error = UPLOAD_ERR_NO_FILE; // Keine Datei angegeben
        }
    }

    /**
     * Überprüft, ob eine Datei hochgeladen wurde.
     * @return bool true, wenn eine Datei vorhanden ist, andernfalls false
     */
    public function hasFile()
    {
        return ($this->error != UPLOAD_ERR_NO_FILE);
    }

    /**
     * Überprüft, ob beim Upload ein Fehler aufgetreten ist.
     * @return bool true, wenn ein Fehler vorliegt, andernfalls false
     */
    public function hasError()
    {
        return ($this->error !== UPLOAD_ERR_OK);
    }

    /**
     * Gibt den Fehlercode zurück, wenn ein Fehler aufgetreten ist.
     * @return int|null Fehlercode oder null, wenn kein Fehler vorliegt
     */
    public function getError()
    {
        if ($this->hasFile()) {
            return $this->error;
        } else {
            return null;
        }
    }

    /**
     * Gibt den Pfad zur temporären Datei zurück.
     * @return string Pfad zur temporären Datei
     */
    public function getTempFile()
    {
        return $this->tempFile;
    }

    /**
     * Gibt den Dateinamen der hochgeladenen Datei zurück.
     * @return string Dateiname
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Gibt die Größe der temporären Datei zurück.
     * @return int Dateigröße in Bytes
     */
    public function getSize()
    {
        return filesize($this->getTempFile());
    }

    /**
     * Gibt den MIME-Typ der Datei zurück.
     * Falls der MIME-Typ nicht verfügbar ist, wird ein Fallback-Typ verwendet.
     * @return string MIME-Typ der Datei
     */
    protected function getFallbackMimeType()
    {
        $headers = get_headers($this->file, 1);
        return (isset($headers['Content-Type'])) ? $headers['Content-Type'] : "application/octet-stream";
    }

    /**
     * Speichert die temporäre Datei an einem neuen Speicherort.
     * @param string $newFilePath Der Pfad, unter dem die Datei gespeichert werden soll.
     * @param bool $override Gibt an, ob vorhandene Dateien überschrieben werden sollen.
     * @return bool true bei Erfolg, andernfalls false
     */
    public function saveAs($newFilePath, $override = true)
    {
        // Wenn der Speicherort existiert oder überschreiben erlaubt ist, wird die Datei umbenannt
        if (!file_exists($newFilePath) || $override) {
            return rename($this->getTempFile(), $newFilePath);
        } else {
            return false;
        }
    }
}
