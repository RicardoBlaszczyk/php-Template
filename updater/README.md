<!-- PROJECT LOGO -->
<br />
<p align="center">
  <h3 align="center">KT-Updater</h3>

  <p align="center">
    Aktualisiert eine Anwendung auf das aktuellste Release aus Github
    <br />
    <br />
  </p>
</p>

<!-- TABLE OF CONTENTS -->
## Inhalt

* [Config](#Config)
  * [Configfelder](#Configfelder)
* [PHP](#PHP)
* [Update starten](#Update-starten)

## Config
Alle Felder in der Config Datei `updater.ini` **müssen** gesetzt werden.

Als Beispiel für die Config wird hier das Repository `Kaitech-IT-Systems/php-Easy2JobArchive` verwendet
### Configfelder
```INI 
gitHubOwner = "Kaitech-IT-Systems"
```
Besitzer des GitHub Repositorys, im Normalfall `Kaitech-IT-Systems`
```INI 
gitHubRepository = "php-Easy2JobArchive"
```
Das Repository, also das Projekt welches installiert/geupdated werden soll
```INI 
gitHubToken = "123456789" 
```
Ein Github Auth-Token. Dieser benötigt die vollen "repo" Rechte um auch private Repositories zu sehen.
Dazu mehr [hier](https://docs.github.com/en/github/authenticating-to-github/creating-a-personal-access-token).
```INI 
projectConfig = "C:\inetpub\wwwroot\Easy2JobArchive\sicher\config.ini"
```
Hier muss die config.ini des Projekts angegeben werden. Hier wird die aktuelle
Version ausgelesen und nach dem Update gesetzt. Wenn dieses Feld leer ist, oder
die Datei nicht existiert wird kein Versionsvergleich durchgeführt.
```INI 
installDirectory = "C:\inetpub\wwwroot\Easy2JobArchive"
```
Hier wird der Pfad zum Installationsverzeichnis angegeben (ohne \ am Ende) 
```INI 
tempDirectory = "C:\tmp"
```
Ein temporäres Verzeichnis für den Download und das Entpacken der neuen Version
**(wird nach Abschluss des Updates komplett geleert!)**
```INI 
backupDirectory = "C:\backups"
```
Verzeichnis für die Backups der Versionen. 
Vor jedem Update wird ein Backup der aktuellen Version dort abgelegt.

## PHP
Für den Updater wird mindestens PHP 7 gebraucht.
Die update.bat Datei versucht den Pfad zu PHP automatisch zu ermitteln.
Falls php in der PATH Variable hinterlegt ist, wird dieses verwendet.
Ansonsten wird das `C:\Program Files` Verzeichnis nach einem PHP7.x Ordner durchsucht.


## Update starten
Der Updater wird über die `updater/update.bat` gestartet. Diese sucht automatisch
nach einer installierten PHP Version und führt damit das Update-Skript aus.  
Wenn keine PHP.exe ermittelt werden kann, beendet sich der Updater wieder.

Wenn eine PHP Version gefunden wurde, wird die Projektversion 
die in der ProjektConfig eingetragen ist, mit dem aktuellem Release 
auf GitHub abgeglichen. Sollte eine neuere Version verfügbar sein, wird zuerst
ein Backup vom aktuellen Stand gemacht und danach die neue Version installiert.

Falls Änderungen an der installierten Version vorgenommen wurden, bricht das Update
ab!

# Linux *.sh Files

## Verwendung:

1. Speichern Sie die Datei als config-rewrite.sh.
2. Machen Sie sie ausführbar:

    ```
   chmod +x config-rewrite.sh
   ```

3. Führen Sie das Skript aus:

   ```
   ./config-rewrite.sh
   ```