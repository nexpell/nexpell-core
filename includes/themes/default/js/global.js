// DataTables Sprache
const LangDataTables = '<?= htmlspecialchars($currentLang, ENT_QUOTES, 'UTF-8'); ?>';

// Bootstrap Form Validation
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    if (!forms || forms.length === 0) return; // Keine Formulare vorhanden

    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});

