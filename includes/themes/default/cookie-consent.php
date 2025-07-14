<!-- cookie-consent.php -->
<div id="cookie-consent-banner" class="position-fixed bottom-0 start-0 end-0 p-3 bg-dark text-white d-none" style="z-index: 9999;">
  <div class="container d-flex justify-content-between align-items-center flex-column flex-md-row">
    <div class="mb-2 mb-md-0">
      Wir verwenden Cookies, um Ihre Erfahrung zu verbessern. 
      <a href="/index.php?site=privacy_policy" class="text-light text-decoration-underline">Mehr erfahren</a>
    </div>
    <div>
      <button class="btn btn-sm btn-outline-light me-2" id="cookie-decline">Ablehnen</button>
      <button class="btn btn-sm btn-primary" id="cookie-accept">Zustimmen</button>
    </div>
  </div>
</div>

<style>
  #cookie-consent-banner a:hover {
    text-decoration: none;
    color: #5fb3fb;
  }
</style>

<script src="/includes/themes/default/js/cookie-consent.js"></script>
