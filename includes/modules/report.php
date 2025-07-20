<?php

// Modul 'report' laden, um Sprachressourcen zu verwenden
$_language->readModule('report');

// Versuchen, die reCAPTCHA-Einstellungen aus der Datenbank zu laden
try {
    // Abrufen der reCAPTCHA-Schlüssel aus der Datenbank
    $get = mysqli_fetch_assoc(safe_query("SELECT * FROM `settings_recaptcha`"));
    $webkey = $get['webkey'];  // öffentlicher Schlüssel
    $seckey = $get['seckey'];  // geheimer Schlüssel

    // Überprüfen, ob reCAPTCHA aktiviert ist
    if ($get['activated'] == "1") { 
        $recaptcha = 1;  // reCAPTCHA ist aktiviert
    } else { 
        $recaptcha = 0;  // reCAPTCHA ist deaktiviert
    }
} catch (EXCEPTION $e) {
    // Fehlerbehandlung, falls es Probleme beim Abrufen der Einstellungen gibt
    $recaptcha = 0;  // reCAPTCHA deaktivieren, falls ein Fehler auftritt
}

// Standardwert für die Verarbeitung festlegen
if (isset($run)) {
    $run = 1;  // Wenn $run bereits gesetzt ist, auf 1 setzen
} else {
    $run = 0;  // Ansonsten auf 0 setzen
}

// Überprüfen, ob der Benutzer eingeloggt ist
if ($userID) {
    // Wenn der Benutzer eingeloggt ist, das Formular ausführen
    $run = 1;
} else {
    // Wenn der Benutzer nicht eingeloggt ist, CAPTCHA oder reCAPTCHA verwenden
    if ($recaptcha != 1) {
        // Standard CAPTCHA verwenden
        $CAPCLASS = new \nexpell\Captcha;
        if (!$CAPCLASS->checkCaptcha($_POST['captcha'], $_POST['captcha_hash'])) {
            // CAPTCHA nicht korrekt, Fehler anzeigen
            $fehler[] = "Securitycode Error";
            $runregister = "false";  // Registrierung nicht ausführen
        } else {
            // CAPTCHA korrekt, Registrierung ausführen
            $run = 1;
            $runregister = "true";
        }
    } else {
        // Wenn reCAPTCHA aktiviert ist
        $runregister = "false";  // Registrierung standardmäßig nicht ausführen

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Wenn das Formular per POST gesendet wurde
            $recaptcha = $_POST['g-recaptcha-response'];  // reCAPTCHA-Antwort abholen

            if (!empty($recaptcha)) {
                // Wenn eine reCAPTCHA-Antwort vorhanden ist
                include("system/curl_recaptcha.php");  // reCAPTCHA-Validierung durch CURL einbinden
                $google_url = "https://www.google.com/recaptcha/api/siteverify";
                $secret = $seckey;  // Geheimer Schlüssel für reCAPTCHA
                $ip = $_SERVER['REMOTE_ADDR'];  // IP-Adresse des Benutzers
                $url = $google_url . "?secret=" . $secret . "&response=" . $recaptcha . "&remoteip=" . $ip;

                // CURL-Abfrage an Google senden
                $res = getCurlData($url);
                $res = json_decode($res, true);  // Antwort decodieren

                // Überprüfen, ob reCAPTCHA erfolgreich war
                if ($res['success']) {
                    // Wenn erfolgreich, Registrierung durchführen
                    $runregister = "true";
                    $run = 1;
                } else {
                    // Wenn fehlerhaft, Fehlermeldung anzeigen
                    $fehler[] = "reCAPTCHA Error";
                    $runregister = "false";  // Registrierung nicht ausführen
                }
            } else {
                // Wenn keine reCAPTCHA-Antwort übermittelt wurde
                $fehler[] = "reCAPTCHA Error";
                $runregister = "false";  // Registrierung nicht ausführen
            }
        }
    }
}

// Überprüfen, ob das Formular gesendet wurde und ob alle Prüfungen erfolgreich sind
if (@$_POST['mode'] && $run) {
    // Werte aus dem Formular abholen
    $mode = $_POST['mode'];
    $type = $_POST['type'];
    $info = $_POST['description'];
    $id = $_POST['id'];

    // Wenn keine Beschreibung eingegeben wurde, Standardnachricht verwenden
    if ($info) {
        $info = $info;
    } else {
        $info = $_language->module['no_informations'];  // Standardtext
    }

    // Aktuelles Datum und Uhrzeit festlegen
    $date = time();
    
    // Berichtnachricht formatieren
    $message = sprintf($_language->module['report_message'], $mode, $type, $id, $info, $id, $type);

    // Nachricht an alle Dateimoderatoren senden
    $dead_link = $_language->module['dead_link_file'];  // Text für die Moderatoren
    $ergebnis = safe_query("SELECT userID FROM `user_groups` WHERE files='1'");  // Alle Benutzer mit Datei-Rechten abfragen
    while ($ds = mysqli_fetch_array($ergebnis)) {
        sendmessage($ds['userID'], $dead_link, $message);  // Nachricht an die Benutzer senden
    }

    // Benutzer nach erfolgreichem Bericht einreichen weiterleiten
    redirect("index.php?site=" . $type, $_language->module['report_recognized'], "3");
} else {
    // Falls eine der Prüfungen fehlschlägt, Fehlermeldung anzeigen
    echo $_language->module['wrong_securitycode'];
}
