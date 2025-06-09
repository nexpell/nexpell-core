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

abstract class Upload
{
    // Fehlercode für nicht lesbare Dateien
    const UPLOAD_ERR_CANT_READ = 99;

    // Fehlercode der aktuellen Upload-Instanz
    protected $error;

    // Konstruktor der Upload-Klasse
    public function __construct()
    {
        // Initialisierung wenn notwendig
    }

    /**
     * Prüft, ob eine Datei hochgeladen wurde.
     * @return bool true wenn eine Datei vorhanden ist, andernfalls false
     */
    abstract public function hasFile();

    /**
     * Prüft, ob ein Fehler bei der Dateiübertragung aufgetreten ist.
     * @return bool true wenn ein Fehler vorhanden ist, andernfalls false
     */
    abstract public function hasError();

    /**
     * Gibt den Fehlercode zurück.
     * @return int Fehlercode
     */
    abstract public function getError();

    /**
     * Gibt den Pfad zur temporären Datei zurück.
     * @return string Pfad zur temporären Datei
     */
    abstract public function getTempFile();

    /**
     * Gibt den Dateinamen zurück.
     * @return string Dateiname
     */
    abstract public function getFileName();

    /**
     * Gibt die Größe der Datei zurück.
     * @return int Dateigröße in Bytes
     */
    abstract public function getSize();

    /**
     * Speichert die Datei unter einem neuen Pfad.
     * @param string $newFilePath Der neue Speicherort der Datei
     * @param bool $override Gibt an, ob bestehende Dateien überschrieben werden sollen
     * @return bool true bei Erfolg, andernfalls false
     */
    abstract public function saveAs($newFilePath, $override = true);

    /**
     * Gibt den Fallback-MIME-Typ zurück, wenn der MIME-Typ nicht ermittelt werden kann.
     * @return string Fallback-MIME-Typ
     */
    abstract protected function getFallbackMimeType();

    /**
     * Gibt die Dateiendung basierend auf dem Dateinamen zurück.
     * @return string|null Dateiendung oder null wenn keine vorhanden ist
     */
    public function getExtension()
    {
        $filename = $this->getFileName();
        if (stristr($filename, ".") !== false) {
            return substr($filename, strrpos($filename, ".") + 1);
        } else {
            return null;
        }
    }

    /**
     * Gibt den MIME-Typ der Datei zurück.
     * @return string MIME-Typ der Datei
     */
    public function getMimeType()
    {
        $filename = $this->getTempFile();
        if (function_exists("finfo_file")) {
            $handle = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($handle, $filename);
            if (stristr($mime, ";") !== false) {
                $mime = substr($mime, 0, strpos($mime, ";"));
            }
        } elseif (function_exists("mime_content_type")) {
            $mime = mime_content_type($filename);
        }

        if (!isset($mime) || empty($mime)) {
            $mime = $this->getFallbackMimeType();
        }

        return $mime;
    }

    /**
     * Überprüft, ob der MIME-Typ der Datei mit den angegebenen MIME-Typen übereinstimmt.
     * @param mixed $required_mime Ein einzelner MIME-Typ oder ein Array von MIME-Typen
     * @return bool true wenn der MIME-Typ übereinstimmt, andernfalls false
     */
    public function supportedMimeType($required_mime)
    {
        $mime = $this->getMimeType();

        if (is_array($required_mime)) {
            foreach ($required_mime as $req_mime) {
                if ($req_mime == $mime) {
                    return true;
                }
            }
        } else {
            if ($required_mime == $mime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Übersetzt den Fehlercode in eine Fehlermeldung.
     * @return string Fehlermeldung
     */
    public function translateError()
    {
        global $_language;
        $_language->readModule('upload', true);

        // Übersetzte Fehlermeldungen basierend auf dem Fehlercode
        switch ($this->error) {
            case UPLOAD_ERR_INI_SIZE:
                $message = $_language->module['file_too_big'];
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = $_language->module['file_too_big'];
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = $_language->module['incomplete_upload'];
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = $_language->module['no_file_uploaded'];
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = $_language->module['no_temp_folder_available'];
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = $_language->module['cant_write_temp_file'];
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = $_language->module['unexpected_error'];
                break;
            case self::UPLOAD_ERR_CANT_READ:
                $message = $_language->module['cant_copy_file'];
                break;
            default:
                $message = $_language->module['unexpected_error'];
                break;
        }

        return $message;
    }
}
