<?php

// Überprüfen, ob die Session bereits gestartet wurde
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
use webspell\AccessControl;

// Admin-Zugriff für das Modul prüfen
AccessControl::checkAdminAccess('ac_site_lock');


// Sprachmodul laden
$_language->readModule('site_lock', false, true);



function escape($str) {
    return mysqli_real_escape_string($_database['_database'], $str);
}


// Captcha-Klasse
class Captcha {
    public function createTransaction() {
        $_SESSION['captcha_hash'] = bin2hex(random_bytes(16));
    }
    public function getHash() {
        return $_SESSION['captcha_hash'] ?? '';
    }
    public function checkCaptcha($dummy, $hash) {
        return isset($_SESSION['captcha_hash']) && $hash === $_SESSION['captcha_hash'];
    }
}

// Aktuellen Status laden
$res_settings = safe_query("SELECT closed FROM settings LIMIT 1");
$row_settings = mysqli_fetch_assoc($res_settings);
$closed = (int)($row_settings['closed'] ?? 0);

echo '<div class="card">
    <div class="card-header"><i class="bi bi-gear"></i> ' . $_language->module['settings'] . '</div>
    <div class="card-body">
        <a href="admincenter.php?site=settings" class="text-decoration-none">' . $_language->module['settings'] . '</a> &raquo; ' . $_language->module['pagelock'] . '<br><br>';

// Wenn die Seite noch nicht gesperrt ist → Sperr-Formular
if (!$closed) {
    #if (!empty($_POST['submit'])) {
    if (isset($_POST["submit"])) {    
        if (empty($_POST['reason'])) {
            die('<div class="alert alert-danger">Fehler: Sperrgrund darf nicht leer sein.</div>');
        }

        $CAPCLASS = new Captcha();

        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {
            #$reason = escape($_POST['reason']);

            $res_lock = safe_query("SELECT * FROM site_lock");
            if (mysqli_num_rows($res_lock)) {
                safe_query("UPDATE site_lock SET reason = '".$_POST['reason']."', time = '" . time() . "'");
            } else {
                safe_query("INSERT INTO site_lock (time, reason) VALUES ('" . time() . "', '".$_POST['reason']."')");
            }

            safe_query("UPDATE settings SET closed = '1'");

            redirect("admincenter.php?site=site_lock", $_language->module['page_locked'], 3);
        } else {
            die('<div class="alert alert-danger">' . $_language->module['transaction_invalid'] . '</div>');
        }
    } else {
        $res_lock = safe_query("SELECT * FROM site_lock");
        $ds = mysqli_fetch_assoc($res_lock);
        $reason = $ds['reason'];

        $CAPCLASS = new Captcha();
        $CAPCLASS->createTransaction();
        $hash = $CAPCLASS->getHash();

        echo '<form method="post" action="">
            <div class="mb-3">
                <label for="reason" class="form-label"><i class="bi bi-lock"></i> <strong>' . $_language->module['pagelock'] . '</strong></label>
                <small class="form-text text-muted d-block mb-2">' . $_language->module['you_can_use_html'] . '</small>
                <textarea class="form-control ckeditor"" id="reason" name="reason" rows="10">' . htmlspecialchars($reason) . '</textarea>
            </div>
          <input type="hidden" name="captcha_hash" value="' . $hash . '" />
            <button class="btn btn-danger" type="submit" name="submit">
                <i class="bi bi-lock"></i> ' . $_language->module['lock'] . '
            </button>
        </form>';
    }
} else {
    // Seite ist gesperrt → Entsperr-Formular
    if (isset($_POST['submit']) && isset($_POST['unlock'])) {
        $CAPCLASS = new Captcha();

        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {
            safe_query("UPDATE settings SET closed = '0'");
            redirect("admincenter.php?site=lock", $_language->module['page_unlocked'], 3);
        } else {
            die('<div class="alert alert-danger">' . $_language->module['transaction_invalid'] . '</div>');
        }
    } else {
        $res_lock = safe_query("SELECT * FROM site_lock");
        $ds = mysqli_fetch_assoc($res_lock);
        $locked_since = isset($ds['time']) ? date("d.m.Y - H:i", $ds['time']) : '-';

        $CAPCLASS = new Captcha();
        $CAPCLASS->createTransaction();
        $hash = $CAPCLASS->getHash();

        echo '<form method="post" action="">
            <p>' . $_language->module['locked_since'] . ' <strong>' . $locked_since . '</strong></p>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="unlock" id="unlockCheck" />
                <label class="form-check-label" for="unlockCheck">' . $_language->module['unlock_page'] . '</label>
            </div>
            <input type="hidden" name="captcha_hash" value="' . $hash . '" />
            <button class="btn btn-success" type="submit" name="submit">
                <i class="bi bi-unlock"></i> ' . $_language->module['unlock'] . '
            </button>
        </form>';
    }
}

echo '</div></div></div>';
