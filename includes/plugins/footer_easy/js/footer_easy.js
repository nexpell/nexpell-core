document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // ==============================
    // 1️⃣ Footer-Kontrast & Links
    // ==============================
    const footer = document.querySelector('footer.footer');
    if (footer) {
        const bodyStyles = getComputedStyle(document.body);
        let bgColor = bodyStyles.getPropertyValue('--bs-body-bg').trim();
        if (!bgColor) bgColor = bodyStyles.backgroundColor || '#ffffff';

        const mode = getContrastYIQ(bgColor);
        let contrastColor, hoverColor;

        if (mode === 'dark') {
            footer.classList.remove('bg-light', 'text-dark');
            footer.classList.add('bg-dark', 'text-white');
            contrastColor = '#ffffff';
            hoverColor = 'rgba(255,255,255,0.7)';
        } else {
            footer.classList.remove('bg-dark', 'text-white');
            footer.classList.add('bg-light', 'text-dark');
            contrastColor = '#000000';
            hoverColor = 'rgba(0,0,0,0.7)';
        }

        const links = footer.querySelectorAll('a, .bi');
        if (links.length) {
            links.forEach(link => {
                link.style.color = contrastColor;
                link.addEventListener('mouseover', () => { link.style.color = hoverColor; });
                link.addEventListener('mouseout', () => { link.style.color = contrastColor; });
            });
        }
    }

    // ==============================
    // 2️⃣ Form-Validation
    // ==============================
    const forms = document.querySelectorAll('.needs-validation');
    if (forms.length) {
        forms.forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    }

    // ==============================
    // 3️⃣ Forum-Elemente
    // ==============================
    const forumButtons = document.querySelectorAll('.forum-button, .forum-toggle');
    if (forumButtons.length) {
        forumButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                console.log('Forum Button geklickt');
            });
        });
    }

    // ==============================
    // 4️⃣ Achievements-Elemente
    // ==============================
    const achievementToggles = document.querySelectorAll('.achievement-toggle');
    if (achievementToggles.length) {
        achievementToggles.forEach(el => {
            el.addEventListener('mouseover', () => {
                console.log('Achievement Hover');
            });
        });
    }

    // ==============================
    // 5️⃣ Logout-Logik
    // ==============================
    let isLoggingOut = false;
    const logoutLink = document.querySelector('#logoutLink');
    if (logoutLink) {
        logoutLink.addEventListener('click', () => {
            isLoggingOut = true;
        });
    }

    const baseUrl = window.location.origin; 
    // z. B. "https://www.demo.nexpell.de" oder "http://localhost/nexpell"

    window.addEventListener("beforeunload", function () {
        if (!isLoggingOut) {
            navigator.sendBeacon(
                baseUrl + "/includes/modules/logout.php",
                new Blob([], { type: "application/x-www-form-urlencoded" })
            );
        }
    });

    // ==============================
    // Hilfsfunktion: Kontrastberechnung
    // ==============================
    function getContrastYIQ(color) {
        let r, g, b;
        if (!color) return 'dark';

        if (color.startsWith('#')) {
            let hex = color.replace('#','');
            if (hex.length === 3) hex = hex.split('').map(c => c+c).join('');
            r = parseInt(hex.substr(0,2),16);
            g = parseInt(hex.substr(2,2),16);
            b = parseInt(hex.substr(4,2),16);
        } else {
            const rgb = color.replace(/[^\d,]/g,'').split(',').map(Number);
            if (rgb.length !== 3) return 'dark';
            [r,g,b] = rgb;
        }

        const yiq = ((r*299)+(g*587)+(b*114))/1000;
        return yiq >= 128 ? 'dark' : 'light';
    }
});

// ==============================
// 6️⃣ Heartbeat (außerhalb, darf immer laufen)
// ==============================
setInterval(function() {
    fetch('/../../system/heartbeat.php')
        .then(res => console.log("Heartbeat OK", res.status))
        .catch(err => console.error("Heartbeat error:", err));
}, 60000);
