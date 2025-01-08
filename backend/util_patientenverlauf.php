<?php
  include_once 'db.php';

  // Fallback
  define("ENTRY_TYPE_UNUSED", 0);

  // Administration
  define("ENTRY_TYPE_DATENAENDERUNG", 11);

  // Arztvisite, Maßnahmen
  define("ENTRY_TYPE_ARZTVISITE", 20);

  // Ein- / Ausgänge
  define("ENTRY_TYPE_EINGANG", 31);
  define("ENTRY_TYPE_AUSGANG_EIGENSTAENDIG", 32);
  define("ENTRY_TYPE_AUSGANG_TRANSPORT_ANFORDERUNG", 33);
  define("ENTRY_TYPE_AUSGANG_TRANSPORT", 34);

  // Nachträgliche Änderungen
  define("ENTRY_TYPE_NACHTRAG_EINGANGSZEIT", 91);
  define("ENTRY_TYPE_NACHTRAG_AUSGANGSZEIT", 92);
  define("ENTRY_TYPE_NACHTRAG_DATENAENDERUNG", 93);



  // Genereischer Wrapper zum Einfügen einer Eintragung in den Patientenverlauf.
  //    Findet sich der Eintragungstyp 0 in der Datenbank, weist das auf einen Fehler hin.
  function newEntryPatientenverlauf($conn, $patientID, $username, $data, $entryType=ENTRY_TYPE_UNUSED) {
    safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG, ART)
                        VALUES(?, ?, ?, ?)",
                        [$patientID, $username, $data, $entryType]);
  }



  // Eintragung einer neuen Arztvisite. Für die Eintragung wird die Wartezeit aus der Differenz
  //    der Eingangszeit und der aktuellen Zeit in Minuten berechnet.
  function newArztvisite($conn, $patientID, $username, $timediff){
    newEntryPatientenverlauf($conn, $patientID, $username,
                             "Arztvisite nach ".$timediff." Minuten Wartezeit.",
                             ENTRY_TYPE_ARZTVISITE);
  }

  function nachtragEingangszeit($conn, $patientID, $username, $timestamp_old, $timestamp_new){
    newEntryPatientenverlauf($conn, $patientID, $username,
                             "Eingangszeit verändert: " . $timestamp_old . " ↦ " . $timestamp_new,
                             ENTRY_TYPE_NACHTRAG_EINGANGSZEIT);
  }

  function nachtragAusgangszeit($conn, $patientID, $username, $timestamp_old, $timestamp_new){
    newEntryPatientenverlauf($conn, $patientID, $username,
                             "Ausgangszeit verändert: " . $timestamp_old . " ↦ " . $timestamp_new,
                             ENTRY_TYPE_NACHTRAG_AUSGANGSZEIT);
  }

 ?>
