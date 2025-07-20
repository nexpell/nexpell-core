<?php

use nexpell\LanguageService;
use nexpell\AccessControl;

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Standardsprache setzen
$_SESSION['language'] = $_SESSION['language'] ?? 'de';

// Initialisieren
global $_database, $languageService;
$languageService = new LanguageService($_database);
$languageService->readModule('site_lock', true);

// Adminrechte prüfen
AccessControl::checkAdminAccess('ac_site_lock');

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
    <div class="card-header"><i class="bi bi-gear"></i> ' . $languageService->get('settings') . '</div>
    <div class="card-body">
        <a href="admincenter.php?site=settings" class="text-decoration-none">' . $languageService->get('settings') . '</a> &raquo; ' . $languageService->get('pagelock') . '<br><br>';

if (!$closed) {
    if (isset($_POST["submit"])) {
        if (empty($_POST['reason'])) {
            die('<div class="alert alert-danger">Fehler: Sperrgrund darf nicht leer sein.</div>');
        }

        $CAPCLASS = new Captcha();

        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {
            $res_lock = safe_query("SELECT * FROM settings_site_lock");
            if (mysqli_num_rows($res_lock)) {
                safe_query("UPDATE settings_site_lock SET reason = '" . $_POST['reason'] . "', time = '" . time() . "'");
            } else {
                safe_query("INSERT INTO settings_site_lock (time, reason) VALUES ('" . time() . "', '" . $_POST['reason'] . "')");
            }

            safe_query("UPDATE settings SET closed = '1'");
            redirect("admincenter.php?site=site_lock", $languageService->get('page_locked'), 3);
        } else {
            die('<div class="alert alert-danger">' . $languageService->get('transaction_invalid') . '</div>');
        }
    } else {
        $res_lock = safe_query("SELECT * FROM settings_site_lock");
        $ds = mysqli_fetch_assoc($res_lock);
        $reason = $ds['reason'] ?? '';

        $CAPCLASS = new Captcha();
        $CAPCLASS->createTransaction();
        $hash = $CAPCLASS->getHash();

        echo '<form method="post" action="">
            <div class="mb-3">
                <label for="reason" class="form-label"><i class="bi bi-lock"></i> <strong>' . $languageService->get('pagelock') . '</strong></label>
                <small class="form-text text-muted d-block mb-2">' . $languageService->get('you_can_use_html') . '</small>
                <textarea class="form-control ckeditor" id="reason" name="reason" rows="10">' . htmlspecialchars($reason) . '</textarea>
            </div>
            <input type="hidden" name="captcha_hash" value="' . $hash . '" />
            <button class="btn btn-danger" type="submit" name="submit">
                <i class="bi bi-lock"></i> ' . $languageService->get('lock') . '
            </button>
        </form>';
    }
} else {
    if (isset($_POST['submit']) && isset($_POST['unlock'])) {
        $CAPCLASS = new Captcha();

        if ($CAPCLASS->checkCaptcha(0, $_POST['captcha_hash'])) {
            safe_query("UPDATE settings SET closed = '0'");
            redirect("admincenter.php?site=site_lock", $languageService->get('page_unlocked'), 3);
        } else {
            die('<div class="alert alert-danger">' . $languageService->get('transaction_invalid') . '</div>');
        }
    } else {
        $res_lock = safe_query("SELECT * FROM settings_site_lock");
        $ds = mysqli_fetch_assoc($res_lock);
        $locked_since = isset($ds['time']) ? date("d.m.Y - H:i", $ds['time']) : '-';

        $CAPCLASS = new Captcha();
        $CAPCLASS->createTransaction();
        $hash = $CAPCLASS->getHash();

        echo '<form method="post" action="">
            <p>' . $languageService->get('locked_since') . ' <strong>' . $locked_since . '</strong></p>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="unlock" id="unlockCheck" />
                <label class="form-check-label" for="unlockCheck">' . $languageService->get('unlock_page') . '</label>
            </div>
            <input type="hidden" name="captcha_hash" value="' . $hash . '" />
            <button class="btn btn-success" type="submit" name="submit">
                <i class="bi bi-unlock"></i> ' . $languageService->get('unlock') . '
            </button>
        </form>';
    }
}

echo '</div></div></div>';
