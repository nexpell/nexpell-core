<!-- Footer Consent -->
<?= $pluginManager->getFooterModule(); ?>
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

<?php
    echo $components_js ?? '';
    echo $theme_js ?? '';
    echo '<!--Plugin & Widget js-->' . PHP_EOL;
    echo $plugin_js ?? '';
    echo '<!--Plugin & Widget js END-->' . PHP_EOL;
?>
<script src="./components/scrolltotop/js/scrolltotop.js"></script>
<!-- ... dein HTML-Header etc. ... -->

<!-- CKEditor + reCAPTCHA Loader -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  const ckeditorEl = document.getElementById("ckeditor");
  if (!ckeditorEl) return;

  const loadScript = (src) => {
    return new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = src;
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  };

  (async () => {
    try {
      // 1️⃣ CKEditor Hauptscript laden, falls noch nicht vorhanden
      if (!window.CKEDITOR) {
        await loadScript("/components/ckeditor/ckeditor.js");
      }

      // 2️⃣ Config-Script laden
      await loadScript("/components/ckeditor/config.js");

      // 3️⃣ Alte Instanz entfernen
      if (CKEDITOR.instances["ckeditor"]) {
        CKEDITOR.instances["ckeditor"].destroy(true);
      }

      // 4️⃣ CKEditor ersetzen
      if (window.CKEDITOR) {
        CKEDITOR.replace("ckeditor", {
          customConfig: "/components/ckeditor/config.js",
          removePlugins: "exportpdf",
          height: 200,
          width: "100%"
        });
      } else {
        console.error("CKEDITOR ist nach dem Laden immer noch undefined!");
      }
    } catch (err) {
      console.error("Fehler beim Laden von CKEditor:", err);
    }
  })();

  // --- reCAPTCHA nur laden, wenn vorhanden ---
  if (document.querySelector(".g-recaptcha")) {
    const recaptchaScript = document.createElement("script");
    recaptchaScript.src = "https://www.gstatic.com/recaptcha/api.js?hl=de";
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
