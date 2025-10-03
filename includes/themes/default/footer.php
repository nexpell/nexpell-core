<!-- Footer Consent -->
<?= get_footer_modul(); ?>
</div>

<!-- Scroll Top Button -->
<div class="scroll-top-wrapper">
    <span class="scroll-top-inner">
        <i class="bi bi-arrow-up-circle" style="font-size: 2rem;" aria-label="Nach oben scrollen"></i>
    </span>
</div>

<!-- Cookie Settings Button -->
<div class="cookies-wrapper">
    <span class="cookies-top-inner">
        <i class="bi bi-gear" style="font-size: 2rem;" id="cookie-settings-icon" data-toggle="tooltip" data-bs-title="Cookie-Einstellungen"></i>
    </span>
</div>

<!-- Cookie Consent -->
<div id="cookie-overlay" style="display:none;"></div>
<?php require_once BASE_PATH . '/components/cookie/cookie-consent.php'; ?>

<!-- Scripts -->
<!-- 1. Google reCAPTCHA API (extern, wird für Formulare benötigt) -->
<!--<script defer src="https://www.google.com/recaptcha/api.js"></script>-->

<?php
    // 2. JavaScript der Core-Komponenten (z. B. CKEditor, ScrollToTop, etc.)
    // Wird dynamisch über $components_js geladen aus der /system/settings.php
    echo $components_js ?? '';

    // 3. Theme-spezifische JavaScript-Dateien
    // Hier können Themes eigene Funktionen/Anpassungen einbinden
    echo $theme_js ?? '';

    // 4. Struktur-Kommentar im HTML: Beginn der Plugin/Widget-Skripte
    echo '<!--Plugin & Widget js-->' . PHP_EOL;

    // 5. Plugin- & Widget-Skripte dynamisch laden
    // Jedes Plugin/Widget kann eigene Skripte registrieren
    echo $plugin_loadheadfile_widget_js ?? '';

    // 6. Struktur-Kommentar im HTML: Ende der Plugin/Widget-Skripte
    echo '<!--Plugin & Widget js END-->' . PHP_EOL;

    echo '<script src="./components/scrolltotop/js/scrolltotop.js"></script>';
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // --- CKEditor laden, wenn <textarea id="ckeditor"> existiert ---
    const ckeditorEl = document.getElementById("ckeditor");
    if (ckeditorEl) {
        const loadScript = (src, callback) => {
            const script = document.createElement("script");
            script.src = src;
            script.onload = callback;
            document.head.appendChild(script);
        };

        loadScript("https://www.nexpell.de/components/ckeditor/ckeditor.js", function() {
            loadScript("https://www.nexpell.de/components/ckeditor/config.js", function() {
                if (typeof CKEDITOR !== "undefined") {
                    CKEDITOR.replace("ckeditor");
                }
            });
        });
    }

    // --- reCAPTCHA laden, wenn ein Formular mit .g-recaptcha existiert ---
    if (document.querySelector(".g-recaptcha")) {
        const recaptchaScript = document.createElement("script");
        recaptchaScript.src = "https://www.gstatic.com/recaptcha/releases/44LqIOwVrGhp2lJ3fODa493O/recaptcha__de.js";
        recaptchaScript.async = true;
        recaptchaScript.defer = true;
        document.head.appendChild(recaptchaScript);
    }
});
</script>

<?php

    #if (defined('DEBUG_PERFORMANCE') && DEBUG_PERFORMANCE) {
    #include BASE_PATH . '/system/performance_debug.php';
#}
?>
</body>
</html>
