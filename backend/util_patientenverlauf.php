<?php
  include_once 'db.php';

  define("ENTY_TYPE_UNUSED", 0);
  define("ENTY_TYPE_ARZTVISITE", 20);



  // Genereischer Wrapper zum Einfügen einer Eintragung in den Patientenverlauf.
  //    Findet sich der Eintragungstyp 0 in der Datenbank, weist das auf einen Fehler hin.
  function newEntryPatientenverlauf($conn, $patientID, $username, $data, $entryType=0) {
    safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG, ART)
                        VALUES(?, ?, ?, ?)",
                        [$patientID, $username, $data, $entryType]);
  }



  // Eintragung einer neuen Arztvisite. Für die Eintragung wird die Wartezeit aus der Differenz
  //    der Eingangszeit und der aktuellen Zeit in Minuten berechnet.
  function newArztvisite($conn, $patientID, $username, $timediff){
    newEntryPatientenverlauf($conn, $patientID, $username,
                             "Arztvisite nach ".$timediff." Minuten Wartezeit.",
                             ENTY_TYPE_ARZTVISITE);
  }

 ?>
