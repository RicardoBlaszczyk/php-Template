# ScanHUB (php-ScanHUB)

Web-Interface zur Verarbeitung von Dokumentenstapeln (Batches) inkl. Upload, Status-Tracking, Dokument-/Seitenverwaltung
und Integrationen (z. B. Parashift, Barcode-Workflow).

## Überblick

ScanHUB stellt eine Oberfläche bereit, um:

- **Stapel (Batches)** anzulegen, anzuzeigen und zu verwalten
- Dokumente/Seiten pro Stapel zu verarbeiten (Import/Preview/Weitergabe)
- Status-Aktualisierungen automatisiert auszuführen (Cronjobs)
- Authentifizierung über klassische Logins sowie optionale Token-/JWT-Mechanismen zu nutzen

## Projektstruktur (Auszug)

- `index.php` – Einstieg/Weiterleitungen
- `login.php` – Login (klassisch + optional Token/JWT)
- `setup.php` – Setup/Erstinstallation (INI schreiben, Admin anlegen)
- `batches.php` – Stapelübersicht, UI-Logik inkl. Auto-Refresh
- `documents.php` – Dokumentübersicht/Bearbeitung pro Stapel
- `api/v1/*` – interne REST-Endpunkte (Upload, Import, Listen, Update, …)
- `cronjobs/*` – Hintergrundjobs (z. B. Status-Updates)
- `sicher/ini/*` – Konfigurationsdateien (INI)
- `sicher/data.php` – zentrale Includes/Konstanten/Bootstrap
- `inc/*` – Libraries & Basisklassen (u. a. FPDI, JWT-Lib, DB, …)
- `data/*` – Arbeits-/Ablagedaten (abhängig von Konfiguration)

## Voraussetzungen

- PHP (8.x empfohlen)
- Webserver (Apache/Nginx/IIS) mit PHP-FPM/Mod-PHP
- Datenbank (je nach Setup: MSSQL oder MySQL/MariaDB)
- Optional: OpenSSL (für RS256 Key-Generierung), cURL (für API-Calls)

## Installation / Setup

1. Projekt in ein Webroot-Verzeichnis deployen.
2. `setup.php` im Browser aufrufen:
    - Beispiel: `http://<host>/<pfad>/setup.php`
3. Pflichtfelder ausfüllen (DB-Verbindung, Admin-Login, Kunden-/Filialdaten).
4. Setup speichert Konfiguration nach:
    - `sicher/ini/config.ini`

> Hinweis: Falls `sicher/ini/default.ini` existiert, kann sie für Vorbelegungen im Setup genutzt werden (je nach
> Projektstand).

## Konfiguration

Wichtige Konfigurationsablage:

- `sicher/ini/config.ini` – produktive Konfiguration
- `sicher/ini/process.ini` – prozessbezogene Konfiguration (falls genutzt)
- `sicher/ini/default.ini` – Defaults (z. B. für Setup-Vorbelegung)

## JWT (RS256): Private/Public Key erzeugen

Wenn du RS256-Verifikation nutzen möchtest, brauchst du ein RSA-Keypair.

### Schlüssel im Verzeichnis `scanhub/sicher` erzeugen

1. In das Zielverzeichnis wechseln:
   ```bash
   cd scanhub/sicher
   ```

2. Private Key erzeugen (RSA 2048):
   ```bash
   openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:2048 -out jwt_private.pem
   ```

3. Public Key aus dem Private Key ableiten:
   ```bash
   openssl pkey -in jwt_private.pem -pubout -out jwt_public.pem
   ```

### Ergebnisdateien

- `scanhub/sicher/jwt_private.pem` – **privat**, vertraulich behandeln, nicht committen
- `scanhub/sicher/jwt_public.pem` – **öffentlich**, zur Verifikation geeignet

## Cronjobs / Hintergrundjobs

Im Ordner `cronjobs/` liegen Skripte zur automatischen Verarbeitung/Status-Aktualisierung.
Je nach Betrieb kannst du sie:

- per Windows Task Scheduler / cron ausführen
- oder über AJAX-Trigger aus der Oberfläche anstoßen (wo vorgesehen)

## Entwicklung

### Assets / JavaScript

- `js/*` enthält UI-Logik (Uploads, Refresh, Konfiguration, …)
- Einige Seiten binden JS inline ein (z. B. Stapelübersicht).

### Libraries

- FPDI/FPDF: `inc/fpdi`, `inc/fpdf`
- JWT: `inc/php-jwt` (falls genutzt)

## Poppler (pdftoppm) installieren

Für die PDF→Bild-Konvertierung wird `pdftoppm` aus Poppler verwendet.

### Windows 11

1. Poppler für Windows installieren (inkl. `pdftoppm.exe`).
2. Stelle sicher, dass `pdftoppm.exe` entweder:
    - im **PATH** liegt, oder
    - unter einem der typischen Pfade installiert ist (Beispiele):
        - `C:\Program Files\poppler-25.07.0\Library\bin\pdftoppm.exe`
        - `C:\Program Files\poppler\bin\pdftoppm.exe`
        - `C:\poppler\bin\pdftoppm.exe`

> Hinweis: Wenn du den PATH änderst, danach den Webserver/IIS neu starten, damit PHP die neue Umgebung sieht.

### Linux (Debian/Ubuntu)

`bash sudo apt-get update sudo apt-get install poppler-utils`

### Test

`bash pdftoppm -v`

Wenn der Befehl eine Version ausgibt, ist Poppler korrekt installiert.

## Sicherheitshinweise

- `sicher/` enthält sensible Konfiguration und ggf. Keys:
    - Zugriffe auf `sicher/*` sollten serverseitig geschützt werden (kein Directory Listing, keine direkte
      Auslieferung).
- Private Keys (`jwt_private.pem`) gehören **nicht** in ein öffentlich zugängliches Webroot.

## Troubleshooting

- **Redirect-Loops (z. B. bei API/Clients)**: Redirect-Follow in Clients (Postman) deaktivieren und Ziel-URL direkt
  testen.
- **JWT-Probleme**: Prüfen, ob Library geladen wird, Algorithmen aktiviert sind und Zeit-Claims (`iat/exp/nbf`) korrekt
  sind.

## Changelog

Siehe: [`CHANGELOG.md`](CHANGELOG.md)

---
Stand: 2026-02