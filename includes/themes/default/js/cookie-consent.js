
function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires}; path=/`;
}

function getCookie(name) {
    const cookies = document.cookie.split('; ');
    for (const cookie of cookies) {
        const [key, val] = cookie.split('=');
        if (key === name) return decodeURIComponent(val);
    }
    return '';
}



function loadTwitchEmbeds(mainChannel, extraChannels) {
    if (document.getElementById('twitch-embed-script')) return;

    const script = document.createElement('script');
    script.id = 'twitch-embed-script';
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

function loadDiscordWidget(serverID) {
    const discordCard = document.getElementById("discord-card");
    const discordWidget = document.getElementById("discord-widget");
    if (!discordCard || !discordWidget) return;

    discordWidget.innerHTML = `
        <iframe
            src="https://discord.com/widget?id=${serverID}&theme=dark"
            width="100%" height="500"
            allowtransparency="true"
            frameborder="0"
            class="w-100 border-0 bg-dark"
            sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts">
        </iframe>
    `;
    discordCard.style.display = 'block';
}



document.addEventListener("DOMContentLoaded", function () {
    const banner = document.getElementById('cookie-consent-banner');
    const twitchSwitch = document.getElementById('twitch-switch');
    const discordSwitch = document.getElementById('discord-switch');
    const twitchCheckbox = document.getElementById('consent-twitch');
    const discordCheckbox = document.getElementById('consent-discord');
    const allCheckbox = document.getElementById('consent-all');
    const streamWrapper = document.getElementById("stream-wrapper");
    const fallbackTwitch = document.getElementById("fallback-twitch");
    const discordCard = document.getElementById("discord-card");
    const fallbackDiscord = document.getElementById('fallback-discord');
    const settingsIcon = document.getElementById("cookie-settings-icon");

    const thirdPartySwitches = document.getElementById('third-party-switches');
    if (!PLUGIN_INSTALLED.twitch && !PLUGIN_INSTALLED.discord && thirdPartySwitches) {
        thirdPartySwitches.style.display = 'none';
    }


    // Zahnrad: Einstellungen nachträglich anzeigen
    if (settingsIcon) {
        settingsIcon.addEventListener("click", () => {
            if (PLUGIN_INSTALLED.twitch && twitchCheckbox) {
                twitchCheckbox.checked = (getCookie('nexpell_consent_twitch') === 'accepted');
            }
            if (PLUGIN_INSTALLED.discord && discordCheckbox) {
                discordCheckbox.checked = (getCookie('nexpell_consent_discord') === 'accepted');
            }
            if (allCheckbox) {
                const twitchOK = !PLUGIN_INSTALLED.twitch || twitchCheckbox.checked;
                const discordOK = !PLUGIN_INSTALLED.discord || discordCheckbox.checked;
                allCheckbox.checked = twitchOK && discordOK;
            }
            showBanner();
        });
    }

    // Plugin-Schalter ausblenden, wenn nicht installiert
    if (!PLUGIN_INSTALLED.twitch && twitchSwitch) twitchSwitch.classList.add('d-none');
    if (!PLUGIN_INSTALLED.discord && discordSwitch) discordSwitch.classList.add('d-none');

    // "Alle erlauben"-Checkbox synchronisieren
    if (allCheckbox) {
        allCheckbox.addEventListener('change', () => {
            if (PLUGIN_INSTALLED.twitch && twitchCheckbox) twitchCheckbox.checked = allCheckbox.checked;
            if (PLUGIN_INSTALLED.discord && discordCheckbox) discordCheckbox.checked = allCheckbox.checked;
        });
    }

    [twitchCheckbox, discordCheckbox].forEach(chk => {
        chk?.addEventListener('change', () => {
            if (allCheckbox) {
                const twitchOK = !PLUGIN_INSTALLED.twitch || twitchCheckbox.checked;
                const discordOK = !PLUGIN_INSTALLED.discord || discordCheckbox.checked;
                allCheckbox.checked = twitchOK && discordOK;
            }
        });
    });

    function applyConsentDisplay() {
        // Twitch
        if (PLUGIN_INSTALLED.twitch) {
            if (getCookie('nexpell_consent_twitch') === 'accepted') {
                if (streamWrapper) streamWrapper.style.display = 'block';
                if (fallbackTwitch) fallbackTwitch.style.display = 'none';
                if (typeof TWITCH_CONFIG !== 'undefined') {
                    const main = TWITCH_CONFIG.main;
                    const extra = TWITCH_CONFIG.extra.split(',').map(c => c.trim()).filter(Boolean);
                    loadTwitchEmbeds(main, extra);
                }
            } else {
                if (streamWrapper) streamWrapper.style.display = 'none';
                if (fallbackTwitch) fallbackTwitch.style.display = 'block';
            }
        }

        // Discord
        if (PLUGIN_INSTALLED.discord) {
            if (getCookie('nexpell_consent_discord') === 'accepted') {
                if (discordCard) discordCard.style.display = 'block';
                if (fallbackDiscord) fallbackDiscord.style.display = 'none';
                if (typeof DISCORD_CONFIG !== 'undefined' && DISCORD_CONFIG.serverID) {
                    loadDiscordWidget(DISCORD_CONFIG.serverID);
                }
            } else {
                if (discordCard) discordCard.style.display = 'none';
                if (fallbackDiscord) fallbackDiscord.style.display = 'block';
            }
        }
    }

    function showBanner() {
        banner.classList.remove('d-none');
    }

    function hideBanner() {
        banner.classList.add('d-none');
    }

    // Zustimmen
    document.getElementById('cookie-accept')?.addEventListener('click', function () {
        if (PLUGIN_INSTALLED.twitch) {
            setCookie('nexpell_consent_twitch', twitchCheckbox.checked ? 'accepted' : 'declined', 180);
        } else {
            setCookie('nexpell_consent_twitch', 'declined', 180);
        }

        if (PLUGIN_INSTALLED.discord) {
            setCookie('nexpell_consent_discord', discordCheckbox.checked ? 'accepted' : 'declined', 180);
        } else {
            setCookie('nexpell_consent_discord', 'declined', 180);
        }

        hideBanner();
        applyConsentDisplay();
    });

    // Ablehnen
    document.getElementById('cookie-decline')?.addEventListener('click', function () {
        if (PLUGIN_INSTALLED.twitch) setCookie('nexpell_consent_twitch', 'declined', 180);
        if (PLUGIN_INSTALLED.discord) setCookie('nexpell_consent_discord', 'declined', 180);

        hideBanner();
        applyConsentDisplay();
    });

    // Wenn irgendein Consent fehlt, Banner anzeigen – auch wenn keine Plugins installiert sind
    const consentCookies = [
        getCookie('nexpell_consent_twitch'),
        getCookie('nexpell_consent_discord')
    ];

    const hasAnyConsent = consentCookies.some(val => val !== '');

    if (!hasAnyConsent) {
        showBanner();
    } else {
        applyConsentDisplay();
    }
});
