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

function rmmodinstall($rubric, $modus, $dir, $id, $getversion) {
    include('../system/func/update_base.php');

    // Definiert die verfügbaren Installationsmodi
    $list = array('0', '1', '2', '3', '4', '5', '6');
    if ($modus == 'install') {
        $installmodus = 'setup';
        $installmodustwo = 'install';
    } else {
        $installmodus = 'update';
        $installmodustwo = 'update';
    }

    // Setze den Plugin- oder Theme-Pfad basierend auf der Rubrik
    if ($rubric == 'temp') {
        $plugin = $updateserverurl.'/theme/style-base_v.'.$getversion.'/';
        $pluginlist = $updateserverurl.'/theme/style-base_v.'.$getversion.'/list.json';
        $instdir = 'expansion';
        $contenthead = 'Themefiles';
    } else {
        $plugin = $updateserverurl.'/plugin/plugin-base_v.'.$getversion.'/';
        $pluginlist = $updateserverurl.'/plugin/plugin-base_v.'.$getversion.'/list.json';
        $instdir = 'plugins';
        $contenthead = 'Pluginfiles';
    }

    // Entfernt Slashes aus dem Verzeichnisnamen
    $dir = str_replace('/', '', $dir);

    // Array für die vergebenen Dateien
    $filesgrant = array();
    if ($rubric == 'temp') {
        // Hole Plugin-Liste und überprüfe die erforderlichen Dateien
        $result = curl_json2array($pluginlist);
        if (isset($result['item'.$id]['required'])) {
            $replacement[] = $dir;
            $pattern = explode(',', $result['item'.$id]['required']);
            foreach ($pattern as $value) { 
                $replacement[] .= $value;
            }
            $multivar = array($dir);
            unset($replacement['0']);
            $multivarplugin = $replacement;
        } else {
            $multivar = array($dir);
            $multivarplugin = '';
        }
    } else {
        // Hole Plugin-Liste und überprüfe die erforderlichen Dateien
        $result = curl_json2array($pluginlist);
        if (isset($result['item'.$id]['required'])) {
            $replacement[] = $dir;
            $pattern = explode(',', $result['item'.$id]['required']);
            foreach ($pattern as $value) { 
                $replacement[] .= $value;
            }
            $multivar = $replacement;
        } else {
            $multivar = array($dir);
        }
    }

    // Verarbeite die verschiedenen Verzeichnisse
    foreach (array_merge(array_filter($multivar)) as $dir) {
        unset($filesgrant);
        $url = $plugin.$dir.'/'.$installmodus.'.json';
        try {
            $result = curl_json2array($url);
            if ($result != "NULL") {
                foreach ($list as $value) {
                    // Index "php" laden
                    $index = $value;
                    $files = @count($result['items'][$index]) - 1;
                    if ($files != '0') {
                        for ($i = 1; $i <= $files; $i++) {
                            try {
                                // Verzeichnis erstellen, falls nicht vorhanden
                                if (!file_exists('../includes/'.$instdir.'/'.$dir.'/')) {
                                    mkdir('../includes/'.$instdir.'/'.$dir.'/', 0777, true);
                                }

                                $filepath = '../includes/'.$instdir.'/'.$result['items'][$index]['file'.$i];
                                $path_parts = pathinfo($filepath);
                                if (!file_exists($path_parts['dirname'])) {
                                    mkdir($path_parts['dirname'], 0777, true);
                                }

                                // Datei herunterladen und speichern
                                $file = '../includes/'.$instdir.'/'.$result['items'][$index]['file'.$i];
                                $content = $plugin.$result['items'][$index]['file'.$i].'.txt';
                                $curl = curl_init();
                                curl_setopt($curl, CURLOPT_URL, $content);
                                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                                $content = curl_exec($curl);
                                file_put_contents($file, $content);
                                curl_close($curl);

                                // Erfolgreiche Datei-Erstellung protokollieren
                                $filesgrant[] = 'File created: '.$file.'<br />';
                            } catch (Exception $f) {
                                echo $f->getMessage();
                            }
                        }
                    }
                }

                // Erfolgsmeldung anzeigen
                echo'
                  <div class=\'card\'>
                    <div class=\'card-header\'>
                      Loading '.$contenthead.'
                    </div>
                    <div class=\'card-body\'>
                      <div class="alert alert-success" role="alert">
                ';
                foreach ($filesgrant as $filesgranted) {
                    echo $filesgranted;
                }
                echo "All ".$contenthead." downloaded successfully <br />Alle ".$contenthead." erfolgreich heruntergeladen<br />Tutti i ".$contenthead." sono stati scaricati correttamente<br />";
                echo'
                      </div>
                    </div>
                  </div>
                ';
                if (file_exists('../includes/'.$instdir.'/'.$dir.'/'.$installmodustwo.'.php')) {
                    include('../includes/'.$instdir.'/'.$dir.'/'.$installmodustwo.'.php');
                } else {
                    echo '<br />No installation file found';
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    // Wenn zusätzliche Plugins vorhanden sind
    if (@$multivarplugin != '') {
        $plugin = $updateserverurl.'/plugin/plugin-base_v.'.$getversion.'/';
        $pluginlist = $updateserverurl.'/plugin/plugin-base_v.'.$getversion.'/list.json';
        $instdir = 'plugins';
        $contenthead = 'Pluginfiles';

        foreach (array_merge(array_filter($multivarplugin)) as $dir) {
            unset($filesgrant);
            unset($add_plugin);
            $url = $plugin.$dir.'/'.$installmodus.'.json';
            try {
                $result = curl_json2array($url);
                if ($result != "NULL") {
                    foreach ($list as $value) {
                        $index = $value;
                        $files = count($result['items'][$index]) - 1;
                        if ($files != '0') {
                            for ($i = 1; $i <= $files; $i++) {
                                try {
                                    // Verzeichnis erstellen, falls nicht vorhanden
                                    if (!file_exists('../includes/'.$instdir.'/'.$dir.'/')) {
                                        mkdir('../includes/'.$instdir.'/'.$dir.'/', 0777, true);
                                    }

                                    $filepath = '../includes/'.$instdir.'/'.$result['items'][$index]['file'.$i];
                                    $path_parts = pathinfo($filepath);
                                    if (!file_exists($path_parts['dirname'])) {
                                        mkdir($path_parts['dirname'], 0777, true);
                                    }

                                    // Datei herunterladen und speichern
                                    $file = '../includes/'.$instdir.'/'.$result['items'][$index]['file'.$i];
                                    $content = $plugin.$result['items'][$index]['file'.$i].'.txt';
                                    $curl = curl_init();
                                    curl_setopt($curl, CURLOPT_URL, $content);
                                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                                    $content = curl_exec($curl);
                                    file_put_contents($file, $content);
                                    curl_close($curl);

                                    // Erfolgreiche Datei-Erstellung protokollieren
                                    $filesgrant[] = 'File created: '.$file.'<br />';
                                } catch (Exception $f) {
                                    echo $f->getMessage();
                                }
                            }
                        }
                    }

                    // Erfolgsmeldung anzeigen
                    echo'
                      <div class=\'card\'>
                        <div class=\'card-header\'>
                          Loading '.$contenthead.'
                        </div>
                        <div class=\'card-body\'>
                          <div class="alert alert-success" role="alert">
                    ';
                    foreach ($filesgrant as $filesgranted) {
                        echo $filesgranted;
                    }
                    echo "All ".$contenthead." downloaded successfully <br />Alle ".$contenthead." erfolgreich heruntergeladen<br />Tutti i ".$contenthead." sono stati scaricati correttamente<br />";
                    echo'
                          </div>
                        </div>
                      </div>
                    ';
                    if (file_exists('../includes/'.$instdir.'/'.$dir.'/'.$installmodustwo.'.php')) {
                        include('../includes/'.$instdir.'/'.$dir.'/'.$installmodustwo.'.php');
                    } else {
                        echo '<br />No installation file found';
                    }
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    // Abschlussmeldung
    echo '<div class="card">
            <div class="card-header">'.$str.' Installation:</div>
            <div class="card-body">
              <div class="alert alert-success">
                <span class="text-dark fs-5">Installation:</span>
                <span class="d-block text-dark">Installation completed successfully!<br>Installation erfolgreich abgeschlossen!</span>
                <br /><br />
                <a class="btn btn-secondary" href="javascript:history.back();reload()">Go Back</a>
              </div>
            </div>
          </div><br />';
}
