## Webspell-RM 3.0 – Verzeichnisstruktur mit Beschreibung

```
/Webspell-RM 3.0/
├── /admin/                          # Adminbereich des CMS
│   ├── /css/                        # Stylesheets für das Admin-Interface
│   ├── /images/                     # Admin-Bilder (z. B. Flaggen, Überschriften)
│   │   ├── /flags/                  # Länderflaggen für Sprachumschaltung o. ä.
│   │   └── /headlines/              # Headerbilder für Admin-Module
│   ├── /img/                        # Weitere Admin-Bilder
│   │   └── /bootstrap-colorpicker/  # Farbauswahl-Tool im Adminbereich
│   ├── /js/                         # JavaScript-Funktionen für Adminmodule
│   ├── /templates/                  # HTML/PHP-Templates für Adminmodule
│   └── /languages/                  # Sprachdateien für Adminmodule
│       ├── /de/                     # Deutsch
│       ├── /en/                     # Englisch
│       └── /it/                     # Italienisch
│
├── /components/                     # Drittanbieter-Bibliotheken & wiederverwendbare UI-Komponenten
│   ├── /bootstrap/                  # Bootstrap-Framework (lokal eingebunden)
│   │   ├── /css/                    # Bootstrap-Stylesheets
│   │   └── /js/                     # Bootstrap-JavaScript
│   ├── /ckeditor/                   # CKEditor für Rich-Text-Editor-Funktionalität
│   ├── /cookies/                    # Cookie-Hinweis/Verwaltung (Cookie-Consent)
│   │   ├── /css/                    # Stylesheets für Cookie-Banner
│   │   └── /js/                     # JS für Cookie-Funktionalität
│   ├── /css/                        # Zusätzliche globale Styles (z. B. Icons, Themes)
│   ├── /PHPMailer/                 # PHP-Mail-Versandbibliothek (SMTP, TLS etc.)
│   └── /scrolltotop/                # Button-Funktion zum Hochscrollen
│       ├── /css/                    # Styles für den Scroll-Button
│       └── /js/                     # JS für Scroll-Logik
│
├── /images/                         # Globale Bilder für das Frontend
│   ├── /avatars/                    # Benutzer-Avatare
│   └── /userpics/                   # Benutzerbilder (Uploads)
│
├── /includes/                       # Zentrale Funktionsbibliothek des Systems
│   ├── /modules/                    # Backend-/Frontend-Module mit eigener Logik
│   ├── /plugins/                    # Plugin-Initialisierungen & Helper
│   └── /themes/                     # Template-bezogene Hilfsfunktionen
│       ├── /404/                    # Fallback-Template für Fehlerseiten
│       └── /default/                # Standard-Theme
│           ├── /css/                # Theme-Stylesheets
│           ├── /images/             # Theme-spezifische Bilder
│           └── /templates/          # Theme-Templates (HTML/PHP)
│
├── /install/                        # Installationsroutine und Setup-Dateien
│   ├── /css/                        # Styles für das Setup-UI
│   ├── /data/                       # SQL-Dateien für Struktur & Demodaten
│   ├── /images/                     # Installationsgrafiken, Logos etc.
│   └── /js/                         # JavaScript für Setup-Ablauf
│
├── /languages/                      # Globale Sprachdateien für das Frontend
│   ├── /de/                         # Deutsch
│   ├── /en/                         # Englisch
│   └── /it/                         # Italienisch
│
├── /system/                         # Systemlogik und Infrastruktur
│   ├── /classes/                    # Kernklassen (z. B. Authentifizierung, DB, Templating)
│   └── /func/                       # Zentrale Funktionen (z. B. Security, Mail, Routing)
│
├── /tmp/                            # Temporäre Dateien (z. B. während Uploads oder Exports)
│
├── .gitignore                       # Git-Konfiguration: ignorierte Dateien/Ordner
├── .htaccess                        # Apache-Umschreiberegeln und Zugriffsschutz
├── CHANGELOG.md                     # Änderungsprotokoll und Versionshistorie
├── README.md                        # Projektübersicht, Installation, Hinweise
├── index.php                        # Einstiegspunkt für das Frontend
├── license.txt                      # Lizenztext (z. B. GNU GPLv3)
├── package.json                     # Node.js-Konfiguration für Tools & Assets
└── rewrite.php                      # PHP-Fallback für URL-Rewriting (z. B. bei fehlender .htaccess)
```

In Arbeit:
/system/
├── classes/
│   └── Router.php          ← Die Routing-Klasse
├── routes/
│   └── web.php             ← Hier definierst du die Routen
└── func/
    └── ...                 ← (z. B. Controller-Dateien)