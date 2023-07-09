
# W:R:S Patientendoku

Implementiert eine digitale Patientenverwaltung, diverse Statistiken/Reports, Monitore für die Unfallhilfsstellen und einige zusätzliche Verwaltungswerkzeuge.

Die Software ist primär auf die Bedürfnisse des Wacken:Rescue:Service ausgelegt - es steht jedem frei, sie auf die Anforderungen anderer Veranstaltungen anzupassen.




## Lizenz

Lizensiert als [CreativeCommons Attribution-ShareAlike 4.0](https://creativecommons.org/licenses/by-sa/4.0/).

Ausgeschlossen davon ist die Verwendung des W:R:S-Logos (```images/background.png```). Es wird empfohlen, dieses durch das Logo der eigenen Organisation mit einer Deckkraft von ca. 20% zu ersetzen.

Die Software wird mit den JS-Bibliotheken [MetroUI-CSS (MIT-Lizenz)](https://github.com/olton/Metro-UI-CSS), sowie [plotly.js (MIT-Lizenz)](https://github.com/plotly/plotly.js/) und [zxing-js (Apache 2.0 Lizenz)](https://github.com/zxing-js/library) ausgeliefert.

Projektmanagement durch Sebastian Rappmann.



## Deployment

**Vorraussetzungen**
- Web-Server mit PHP 8.1 (Andere Versionen können funktionieren, getestet ist die Software aber nur mit PHP 8.1. Bei älteren Versionen kann es zu Problemen mit den Array-Funktionen ```explode(...)``` und ```implode(...)``` kommen.)

- MySQL / MariaDB Datenbank (UTF-8, idealerweise utf8mb4)

**Installation**
- Repository in das Zielverzeichnis (Domain- oder Subdomain Root) des Webservers kopieren.

- Datenbank-Struktur installieren, indem ```setup.sql``` in der Datenbank ausgeführt wird.

- Zugangsdaten für die Datenbank in Config-Datei ```config.php``` anpassen.

- Initialer Login mit mitgeliefertem "ENTFERN_MICH_ADMIN" und Passwort "root". Accounts in der Benutzerverwaltung anlegen und "ENTFERN_MICH_ADMIN" deaktivieren oder aus der Datenbank löschen.

- _Optional_: Daten-Setup für Wacken einspielen, indem ```wrs_daten_setup.sql``` ausgeführt wird.

- _Optional_: Hintergrundbild ```images/background.png``` auf das Logo der eigenen Organisation anpassen.


## "Hidden" Features

 - Personalisierte Links für Benutzer können erstellt werden, indem an die URL der Login-Seite der GET-Parameter ```&u=``` angehängt wird. Der übergebene Benutzername wird automatisch ausgewählt, so dass der User nur noch sein Passwort eingeben muss. Beispiel: ```../frontend/login.php?u=DemoAdmin``` wählt automatisch den Benutzer "DemoAdmin" aus.

 - Dokumentation via GitHub-Wiki folgt.



## Bekannte Fehler

**Arzt-Accounts und Berechnung der Wartezeiten**

Wird einem Arzt-Account eine andere Benutzerrolle zugewiesen werden seine Visiten nicht mehr als solche erkannt. Die kann die Berechnung der Wartezeiten durcheinander bringen. Wird analog dazu z.B. einem Sichter-Account die "Arzt"-Rolle zugewiesen, so werden seine Einträge als Arzt-Visiten betrachtet.

Für den Augenblick wird empfohlen nur frischen Accounts die "Arzt"-Rolle zuzuweisen und diese im Nachgang nicht zu ändern.

**Ausdrucke der Statistiken unter Chrome**

Unter Chrome (und je nach Standardeinstellung unter Firefox) sehen die Ausdrucke der Statistiken (Patientenliste, Transportliste, RKISH-Statistik) ggf. falsch formatiert aus.

Es wird empfohlen mit "Als PDF speichern" zu drucken und die Option "Hintergrund anzeigen" zu aktivieren und "Kopf- und Fußzeilen anzeigen" zu deaktivieren. Damit wird die Darstellung korrigiert.
