
# W:R:S Patientendoku

Implementiert eine digitale Patientenverwaltung, diverse Statistiken/Reports, Monitore für die Unfallhilfsstellen und einige zusätzliche Verwaltungswerkzeuge.

Die Software ist primär auf die Bedürfnisse des Wacken:Rescue:Squad ausgelegt - es steht jedem frei, sie auf die Anforderungen anderer Veranstaltungen anzupassen.




## Lizenz

Lizensiert unter [AGPL v3](https://www.gnu.org/licenses/agpl-3.0.txt).

Ausgeschlossen davon ist die Verwendung des W:R:S-Logos (```images/background.png```). Es wird empfohlen, dieses durch das Logo der eigenen Organisation mit einer Deckkraft von ca. 20% zu ersetzen.

Die Software wird mit den JS-Bibliotheken [MetroUI-CSS (MIT-Lizenz)](https://github.com/olton/Metro-UI-CSS), sowie [plotly.js (MIT-Lizenz)](https://github.com/plotly/plotly.js/) und [zxing-js (Apache 2.0 Lizenz)](https://github.com/zxing-js/library) ausgeliefert.

Projektmanagement durch Sebastian Rappmann.




## Deployment

**Vorraussetzungen**
- Web-Server mit PHP 8.1 (Andere Versionen können funktionieren, getestet ist die Software aber nur mit PHP 8.1. Bei älteren Versionen kann es zu Problemen mit den Array-Funktionen ```explode(...)``` und ```implode(...)``` kommen.)

- MySQL / MariaDB Datenbank (UTF-8, idealerweise utf8mb4_general_ci)

**Installation**
- Repository in das Zielverzeichnis (Domain- oder Subdomain Root) des Webservers kopieren.

- Datenbank-Struktur installieren, indem ```setup.sql``` in der Datenbank ausgeführt wird.

- Zugangsdaten für die Datenbank in Config-Datei ```config.php``` anpassen.

- Initialer Login mit mitgeliefertem "DEAKTIVIER_MICH_ADMIN" und Passwort "root". Accounts in der Benutzerverwaltung anlegen und "DEAKTIVIER_MICH_ADMIN" deaktivieren oder aus der Datenbank löschen.

- _Optional_: Daten-Setup für Wacken einspielen, indem ```wrs_daten_setup.sql``` ausgeführt wird.

- _Optional_: Hintergrundbild ```images/background.png``` auf das Logo der eigenen Organisation anpassen.


## "Hidden" Features

 - Personalisierte Links für Benutzer können erstellt werden, indem an die URL der Login-Seite der GET-Parameter ```&u=``` angehängt wird. Der übergebene Benutzername wird automatisch ausgewählt, so dass der User nur noch sein Passwort eingeben muss. Beispiel: ```../frontend/login.php?u=DemoAdmin``` wählt automatisch den Benutzer "DemoAdmin" aus.

 - Die Lebenszeit einer Session wird standardmäßig auf 12 Stunden gesetzt, das kann aber in der Konfiguration angepasst werden, indem der Wert für ```$CST_SESSION_LIFETIME``` auf den gewünschten Wert (in Sekunden) gesetzt wird.

 - Benutzernamen werden standardmäßig nicht mehr bei der Loginseite zur Auswahl angezeigt. Soll das Feature reaktiviert werden, kann der Wert für ```$SHOW_USERNAMES_ON_LOGIN``` in der Konfiguration auf ```True``` gesetzt werden. \\
 Das wird normalerweise nicht empfohlen, weil es alle hinterlegten Benutzernamen preisgibt - bevor es aktiviert wird, sollte man sich also Gedanken über die Auswirkungen auf die Anwendungssicherheit machen.



## Bekannte Fehler

**Ausdrucke der Statistiken unter Chrome**

Unter Chrome (und je nach Standardeinstellung unter Firefox) sehen die Ausdrucke der Statistiken (Patientenliste, Transportliste, RKISH-Statistik) ggf. falsch formatiert aus.

Es wird empfohlen mit "Als PDF speichern" zu drucken und die Option "Hintergrund anzeigen" zu aktivieren und "Kopf- und Fußzeilen anzeigen" zu deaktivieren. Damit wird die Darstellung korrigiert.
