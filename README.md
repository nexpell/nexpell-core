# Webspell-RM 3.0 – Next Generation (2025)

**Webspell-RM 3.0** ist ein vollständig neu entwickeltes, modulares und sicheres Open-Source CMS für Communities, Clans und Teams – bereit für das Jahr 2025 und darüber hinaus.

![Logo](https://www.webspell-rm.de/includes/plugins/picupdate/images/390.png)
![Logo](https://www.webspell-rm.de/includes/plugins/picupdate/images/371.png)

👉 Offizielle Website: [webspell-rm.de](https://www.webspell-rm.de)  
👉 Forum & Support: [webspell-rm.de/forum.html](https://www.webspell-rm.de/forum.html)  
👉 Dokumentation: [webspell-rm.de/wiki.html](https://www.webspell-rm.de/wiki.html)

---

## 🚀 Highlights

- ✅ **Installer-basiertes Setup (1 Datei – alles drin!)**
- 🔌 **Erweiterbares Plugin-System**
- 🎨 **Moderne Themes mit Bootstrap 5**
- 🌍 **Multilingual mit Sprachdateien**
- 🔐 **DSGVO-konform & reCAPTCHA**
- 🛡️ **Sicher: CSRF-, XSS- & IP-Schutz**
- 📈 **Statistiken, Admincenter 2.0, Rollen & Rechte**
- 📱 **100 % Responsive Design (Frontend & Admin)**
- 📦 **PHP 8.x Unterstützung & saubere OOP-Struktur**

---

## 📥 Installation in 6 Schritten

Die Installation erfolgt **ausschließlich über den neuen Web-Installer**.  
Dieser lädt automatisch **alle Systemdateien und SQL-Strukturen** auf deinen Webserver – kein manuelles Hochladen notwendig.

### 🔧 Voraussetzungen

Bevor du den Installer startest, stellt Webspell-RM sicher, dass dein Webserver folgende Anforderungen erfüllt:

- PHP **≥ 8.1**
- MySQL **8** / MariaDB **≥ 10.3**
- Schreibrechte für relevante Ordner (z. B. `/config`, `/uploads`, etc.)
- Apache/Nginx mit mod_rewrite empfohlen

**⚠️ Wird eine Voraussetzung nicht erfüllt, wird die Installation blockiert!**

---

### 🛠️ Installationsschritte

1. **Lade den offiziellen Installer herunter**  
   👉 [Download Installer (.php)](https://www.webspell-rm.de/download)

2. **Lade den `Installer` auf deinen Webserver (Root-Verzeichnis)**

3. **Rufe im Browser auf:**  


4. **Folge den 6 Installationsschritten:**
- Schritt 1: Serverprüfung
- Schritt 2: Datenbankzugang
- Schritt 3: Systemdaten laden (automatisch)
- Schritt 4: Administrator anlegen
- Schritt 5: Sprache & Einstellungen
- Schritt 6: Abschluss & Cleanup

5. Nach der Installation wird:
- Die komplette CMS-Struktur auf den Webserver entpackt
- Alle Datenbanktabellen angelegt
- Dein System konfiguriert

6. **Entferne den `Install Ordner` danach aus Sicherheitsgründen**

---

## 📂 Systemstruktur (wird durch den Installer angelegt)

```plaintext
/admin/             → Adminbereich mit modularer Navigation  
/includes/          → Kernfunktionen & Klassen  
/plugins/           → Erweiterbare Plugins  
/themes/            → Frontend-Themes  
/system/            → Template-Engine, Auth, CSRF, Router  
/config/            → Konfigurationen (wird automatisch erstellt)  
/install/           → Nur während des Setups vorhanden  
/uploads/           → Medien und Dateien  

🧩 Erweiterbarkeit

    Eigene Themes via /themes/

    Eigene Plugins via /plugins/

    Eigene Module mit Routing und Zugriffskontrolle

    Template-System mit {{ platzhalter }}-Syntax

    Vollständig objektorientiert mit modernen PHP-Strukturen

📚 Dokumentation & Hilfe

    📖 Webspell-RM Wiki

    💬 Forum

    🐛 Bug melden: GitHub Issues

🤝 Mitwirken

Pull Requests, Fehlerberichte und Feature-Vorschläge sind jederzeit willkommen.
Bitte lies unseren Beitrag-Guide (folgt demnächst).

📜 Lizenz

Dieses Projekt steht unter der GNU General Public License v3.0.
Copyright © 2025
webspell-rm.de        
