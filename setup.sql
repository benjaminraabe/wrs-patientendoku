-- DDL-Wacken-Patientendokumentation

CREATE TABLE `FAHRZEUGE` (
  `UID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique Identifier',
  `RUFNAME` varchar(100) NOT NULL COMMENT 'Rufname des Fahrzeugs',
  PRIMARY KEY (`UID`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `KLINIKEN` (
  `KLINIK_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'UID der gewählten Klinik',
  `KLINIK_NAME` varchar(250) NOT NULL COMMENT 'Name der Klinik',
  PRIMARY KEY (`KLINIK_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `MONITOR_STRINGS` (
  `UID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique Identifier',
  `ITEM` varchar(500) NOT NULL COMMENT 'String der auf dem Durchlauf auf dem Einsatzmonitor angezeigt werde soll.',
  PRIMARY KEY (`UID`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `PZC_CATEGORIES` (
  `CAT_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'UUID der Kategorie',
  `CAT_DESCRIPTION` varchar(250) NOT NULL COMMENT 'Beschreibung der PZC-Kategorie',
  PRIMARY KEY (`CAT_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `UHS_DEFINITION` (
  `UHST_ID` int(11) NOT NULL AUTO_INCREMENT,
  `NAME` varchar(256) NOT NULL,
  PRIMARY KEY (`UHST_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `BEREICHE` (
  `BEREICH_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'UID des Bereichs',
  `UHST_ID` int(11) NOT NULL COMMENT 'UHST die dem Bereich zugeordnet ist.',
  `NAME` varchar(250) NOT NULL COMMENT 'Bezeichnung des Bereichs',
  `CAPACITY` int(11) DEFAULT NULL COMMENT 'Optional: Patientenkapazität / Betten des Bereichs.',
  PRIMARY KEY (`BEREICH_ID`),
  KEY `bereiche_FK` (`UHST_ID`),
  CONSTRAINT `bereiche_FK` FOREIGN KEY (`UHST_ID`) REFERENCES `UHS_DEFINITION` (`UHST_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `PATIENTEN` (
  `PATIENTEN_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'UID des Patienten.',
  `BEREICH_ID` int(11) DEFAULT NULL COMMENT 'Optional: Bereich, dem der Pat. zugewiesen ist.',
  `AKTIV` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Wenn WAHR: Pat ist noch in der Behandlung. Sonst: Pat nur noch im Archiv.',
  `ZEIT_EINGANG` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Zeit der ersten Erfassung des Patienten.',
  `ZEIT_ENTLASSUNG` timestamp NULL DEFAULT NULL COMMENT 'Optional: Zeitstempel des Patientenausgangs. (Z.B. Übergabe an RD, Behandlungsende, ...)',
  `SICHTUNGSKATEGORIE` varchar(20) NOT NULL DEFAULT 'NICHT DRINGEND' COMMENT 'Sichtungskategorie nach dem Manchester-Triage-System.',
  `PZC` varchar(50) DEFAULT NULL COMMENT 'Optional: PZC. Hier als String um verschiedene Formate (nicht nur Nummern) zu erlauben.',
  `TRANSPORTKATEGORIE` varchar(250) DEFAULT NULL COMMENT 'Optional: Transportkategorie nach örtlichem System.',
  `TRANSPORTZIEL` varchar(250) DEFAULT NULL COMMENT 'Optional: Name / Beschreibung des Zielortes für einen möglichen Transport.',
  `TRANSPORT_RUFNAME` varchar(100) DEFAULT NULL COMMENT 'Optional: Rufname des Transportierenden Rettungsmittels.',
  `VORNAME` varchar(250) DEFAULT NULL COMMENT 'Optional: Vorname des Patienten.',
  `NAME` varchar(250) DEFAULT NULL COMMENT 'Optional: Nachname des Patienten',
  `LAND` varchar(250) DEFAULT NULL COMMENT 'Optional: Land des Patientenwohnortes.',
  `ORT` varchar(250) DEFAULT NULL COMMENT 'Optional: Stadtname des Patientenwohnortes.',
  `STRASSE` varchar(250) DEFAULT NULL COMMENT 'Optional: Strassenname des Patientenwohnortes.',
  `HAUSNUMMER` varchar(50) DEFAULT NULL COMMENT 'Optional: Hausnummer/Addr.zusatz des Patientenwohnortes.',
  `BEMERKUNG` varchar(1000) DEFAULT NULL COMMENT 'Optional: Freitextfeld Bemerkung zu dem Patienten.',
  `DOB` date DEFAULT NULL COMMENT 'Optional: Geburtsdatum des Patienten',
  `GESCHLECHT` char(1) DEFAULT NULL COMMENT 'Optional: Geschlecht des Patienten {''M'','' W'', ''D''}',
  `ZIELKLINIK` int(11) DEFAULT NULL COMMENT 'Optional: Zielklinik, an die der Patient Transportiert wird.',
  `NACHBEARBEITET` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Optional: Markiert ob ein Protokoll nachträglich eingetragen wurde. (D.h. die Zeit/Datum nicht automatisch gesetzt wurden)',
  `INFEKT` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Optional: Markiert einen Patienten als Infektiös.',
  `EINGANGSART` tinyint(4) DEFAULT 0 COMMENT 'Optional: Art des Patienteneingangs. (0= Unbekannt, 1=Selbst zur UHS, 2=Von SAN-Team aufgenommen)',
  `UEBERSCHWER` tinyint(1) DEFAULT 0 COMMENT 'Optional: Markiert einen Patienten als überschwer (>150kg).',
  PRIMARY KEY (`PATIENTEN_ID`),
  KEY `patienten_FK` (`BEREICH_ID`),
  KEY `PATIENTEN_FK_1` (`ZIELKLINIK`),
  CONSTRAINT `PATIENTEN_FK_1` FOREIGN KEY (`ZIELKLINIK`) REFERENCES `KLINIKEN` (`KLINIK_ID`),
  CONSTRAINT `patienten_FK` FOREIGN KEY (`BEREICH_ID`) REFERENCES `BEREICHE` (`BEREICH_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `PATIENTENVERLAUF` (
  `UID` int(11) NOT NULL AUTO_INCREMENT,
  `PATIENTEN_ID` int(11) NOT NULL COMMENT 'ID eine Patienten. Referenziert Patienten.PATIENTEN_ID',
  `USERNAME` varchar(100) NOT NULL COMMENT 'Benutzer der den Eintrag vornimmt. Referenziert Benutzer.USERNAME',
  `TIMESTAMP` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Zeit der Eintragung',
  `EINTRAG` varchar(1000) NOT NULL COMMENT 'Text / Anlass der Eintragung',
  PRIMARY KEY (`UID`),
  KEY `patientenverlauf_FK` (`PATIENTEN_ID`),
  CONSTRAINT `patientenverlauf_FK` FOREIGN KEY (`PATIENTEN_ID`) REFERENCES `PATIENTEN` (`PATIENTEN_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `PROTOKOLL_VERKNUEPFUNGEN` (
  `UID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique Identifier',
  `SRC_PROTOKOLL` int(11) NOT NULL COMMENT 'Ursprungsprotokoll, in dem die Referenz erwähnt wurde.',
  `ZIEL_PROTOKOLL` int(11) NOT NULL COMMENT 'Protokoll, welches referenziert wird. Kein Foreign-Key, da das Protokoll mglw. noch nicht existiert.',
  PRIMARY KEY (`UID`),
  KEY `PROTOKOLL_VERKNUEPFUNGEN_FK` (`SRC_PROTOKOLL`),
  KEY `PROTOKOLL_VERKNUEPFUNGEN_FK_1` (`ZIEL_PROTOKOLL`),
  CONSTRAINT `PROTOKOLL_VERKNUEPFUNGEN_FK` FOREIGN KEY (`SRC_PROTOKOLL`) REFERENCES `PATIENTEN` (`PATIENTEN_ID`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `PZC` (
  `PZC` varchar(3) NOT NULL COMMENT 'Eindeutiger PZC',
  `DESCRIPTION` varchar(250) NOT NULL COMMENT 'Klartextbeschreibung der PZC',
  `PZC_CAT_ID` int(11) DEFAULT NULL COMMENT 'Optionale Referenz zu einer Kategorie, an die die PZC angesiedelt ist.',
  PRIMARY KEY (`PZC`),
  KEY `PZC_FK` (`PZC_CAT_ID`),
  CONSTRAINT `PZC_FK` FOREIGN KEY (`PZC_CAT_ID`) REFERENCES `PZC_CATEGORIES` (`CAT_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `USER` (
  `USERNAME` varchar(100) NOT NULL,
  `PW_HASH` varchar(1000) NOT NULL,
  `UHST_ID` int(11) DEFAULT NULL COMMENT 'ID der UHS der der User zugeordnet ist. ''Null'' hat Zugriff auf die Daten aller UHST.',
  `ACTIVE` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'User kann deaktiviert werden. Referenzen auf diesen Account bleiben erhalten, der Zugriff wird aber verhindert.',
  `USER_ROLE` varchar(100) NOT NULL DEFAULT '' COMMENT 'Benutzerrolle des Users. Bestimmt welche Seiten er einsehen/verwenden kann. Dokumentation konsultieren!',
  `CAN_LATE_EDIT` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Regelt Berechtigungen für den Nachtragezugang. Bei TRUE darf der User Daten nachtragen.',
  `CAN_SEARCH_PATIENTS` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Berechtigung um die Patientensuche aufzurufen.',
  `CAN_BACKDATE_PROTOCOL` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Berechtigung um den Nachtragezugang (Protokolle mit anderem Start/Endzeit) einzupflegen.',
  PRIMARY KEY (`USERNAME`),
  KEY `user_FK` (`UHST_ID`),
  CONSTRAINT `user_FK` FOREIGN KEY (`UHST_ID`) REFERENCES `UHS_DEFINITION` (`UHST_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default-Zugang und Monitor-Nachricht
INSERT INTO MONITOR_STRINGS (ITEM) VALUES('');
INSERT INTO `USER` (USERNAME,PW_HASH,UHST_ID,ACTIVE,USER_ROLE,CAN_LATE_EDIT,CAN_SEARCH_PATIENTS,CAN_BACKDATE_PROTOCOL) VALUES ('ENTFERN_MICH_ADMIN','$2y$10$0k1PNfCRF0jaMiTJS7mcuOcgwVHVg3QmaNuxa5AbV2HobroA6u3Fm',NULL,1,'ADMIN',1,1,1);
