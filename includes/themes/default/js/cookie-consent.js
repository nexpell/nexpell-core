function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/`;
}

function getCookie(name) {
    return document.cookie.split('; ').reduce((r, v) => {
        const parts = v.split('=');
        return parts[0] === name ? decodeURIComponent(parts[1]) : r;
    }, '');
}

function loadTwitchEmbeds(mainChannel, extraChannels) {
    const script = document.createElement('script');
    script.src = "https://embed.twitch.tv/embed/v1.js";
    script.onload = () => {
        new Twitch.Embed("main-stream", {
            width: "100%",
            height: 500,
            channel: mainChannel,
            layout: "video-with-chat",
            parent: [window.location.hostname]
        });

        extraChannels.forEach((channel, index) => {
            const div = document.createElement("div");
            div.className = "stream";
            div.id = "extra-stream-" + (index + 1);
            document.getElementById("extra-streams").appendChild(div);

            new Twitch.Embed(div.id, {
                width: "100%",
                height: 400,
                channel: channel,
                layout: "video",
                parent: [window.location.hostname]
            });
        });
    };
    document.body.appendChild(script);
}

document.addEventListener("DOMContentLoaded", function () {
    const cookieBanner = document.getElementById('cookie-consent-banner');
    const settingsIcon = document.getElementById('cookie-settings-icon');
    const overlay = document.getElementById('cookie-overlay');
    const fallback = document.getElementById('fallback-message');

    function showBanner() {
        if (cookieBanner) cookieBanner.classList.remove('d-none');
        if (overlay) overlay.classList.add('visible');
        if (fallback) fallback.style.display = 'block';
    }

    function hideBanner() {
        if (cookieBanner) cookieBanner.classList.add('d-none');
        if (overlay) overlay.classList.remove('visible');
        if (fallback) fallback.style.display = 'none';
    }

    function bindBannerButtons() {
        document.getElementById('cookie-accept')?.addEventListener('click', function () {
            setCookie('nexpell_cookie_consent', 'accepted', 180);
            hideBanner();
            location.reload(); // Optional: Twitch-Reload nur wenn nötig
        });

        document.getElementById('cookie-decline')?.addEventListener('click', function () {
            setCookie('nexpell_cookie_consent', 'declined', 180);
            hideBanner();
            location.reload();
        });
    }

    // Öffnen über Zahnrad
    if (settingsIcon) {
        settingsIcon.addEventListener('click', function () {
            showBanner();
            bindBannerButtons();
        });
    }

    const consent = getCookie('nexpell_cookie_consent');

    if (!consent) {
        showBanner();
        bindBannerButtons();
    } else if (consent === 'accepted') {
        hideBanner();
        const main = TWITCH_CONFIG.main;
        const extra = TWITCH_CONFIG.extra.split(',').map(c => c.trim()).filter(Boolean);
        loadTwitchEmbeds(main, extra);
    } else if (consent === 'declined') {
        hideBanner();
        if (fallback) fallback.style.display = 'block';
    }
});
