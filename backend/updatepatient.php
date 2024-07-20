<?php
  // Dieser Endpoint nimmt Daten zu neuen oder veränderten Patienten in Empfang,
  //    validiert sie und versucht sie in die Datenbank zu schreiben.
  // Zusätzlich werden korrespondierende Einträge im Patientenverlauf angelegt.


  include_once '../backend/sessionmanagement.php';

  if (!in_array("PERM_WRITE_PATIENTS", $_SESSION["PERMISSIONS"], true)) {
    echo "Zugriff verweigert.";
    exit();
  }


  include_once 'db.php';
  $late_edit = false;
  $postdata = json_decode(file_get_contents('php://input'), true);

  $patientendaten = $postdata["patient"];
  $patientendaten["PATIENTEN_ID"] = safeCharsOnly($patientendaten["PATIENTEN_ID"] ?? "");

  // Bisheriger Datenstand wird abgerufen um herauszufinden was geändert wurde
  $oldDaten = safeQuery($conn, "SELECT * FROM PATIENTEN WHERE PATIENTEN_ID = ?", [$patientendaten["PATIENTEN_ID"]]);
  if (count($oldDaten) > 0) {
    $oldDaten = $oldDaten[0];
  } else {
    // Fallback, sollte aber nie auftreten.
    exit("Error: Der Patient wurde anscheinend noch nicht angelegt.");
  }

    // Änderungen in der Eingangszeit setzen
    if (array_key_exists("EINGANG_TIMESTAMP", $postdata)) {
      // User ist berechtigt die Zeit zu verändern
      if (($patientendaten["AKTIV"] == 0)
          && (in_array("PERM_LATE_ENTER_PATIENTS", $_SESSION["PERMISSIONS"], true) ||
              in_array("PERM_CHANGE_ARCHIVED_PATIENT_DATA", $_SESSION["PERMISSIONS"], true))) {
        $e_timestamp = trim($postdata["EINGANG_TIMESTAMP"]);
        // Eingangszeit darf nicht leer sein
        if ($e_timestamp != '') {
          $e_timestamp = date("Y-m-d H:i:s", strtotime($e_timestamp));
          $old_e_timestamp = date("Y-m-d H:i:s", strtotime($oldDaten["ZEIT_EINGANG"]));
          // Eingangszeit weicht von bekannter Zeit ab
          if ($e_timestamp != $old_e_timestamp) {
            try {
              safeExecute($conn, "UPDATE PATIENTEN SET ZEIT_EINGANG = ? WHERE PATIENTEN_ID = ?;", [$e_timestamp, $patientendaten["PATIENTEN_ID"]]);
              safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
                VALUES(?, ?, CONCAT(\"<span style='color: red;'>Eingangszeit verändert:</span><br>\", ?));",
                [$patientendaten["PATIENTEN_ID"],
                $_SESSION['USER_ID'],
                $old_e_timestamp . " => " . $e_timestamp
              ]);
            } catch (\Exception $e) {}
          }
        }
      } else {
        exit("Error: Berechtigungen für Zeitänderung nicht erteilt oder Patient ist noch aktiv.");
      }
    }

    // Änderungen in der Ausgangszeit setzen
    if (array_key_exists("AUSGANG_TIMESTAMP", $postdata)) {
      // User ist berechtigt die Zeit zu verändern
      if (($patientendaten["AKTIV"] == 0)
          && (in_array("PERM_LATE_ENTER_PATIENTS", $_SESSION["PERMISSIONS"], true) ||
              in_array("PERM_CHANGE_ARCHIVED_PATIENT_DATA", $_SESSION["PERMISSIONS"], true))) {
        $a_timestamp = trim($postdata["AUSGANG_TIMESTAMP"]);
        // Eingangszeit darf nicht leer sein
        if ($a_timestamp != '') {
          $a_timestamp = date("Y-m-d H:i:s", strtotime($a_timestamp));
          $old_a_timestamp = date("Y-m-d H:i:s", strtotime($oldDaten["ZEIT_ENTLASSUNG"]));
          // Eingangszeit weicht von bekannter Zeit ab
          if ($a_timestamp != $old_a_timestamp) {
            try {
              safeExecute($conn, "UPDATE PATIENTEN SET ZEIT_ENTLASSUNG = ? WHERE PATIENTEN_ID = ?;", [$a_timestamp, $patientendaten["PATIENTEN_ID"]]);
              safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
                VALUES(?, ?, CONCAT(\"<span style='color: red;'>Ausgangszeit verändert:</span><br>\", ?));",
                [$patientendaten["PATIENTEN_ID"],
                $_SESSION['USER_ID'],
                $old_a_timestamp . " => " . $a_timestamp
              ]);
            } catch (\Exception $e) {}
          }
        }
      } else {
        exit("Error: Berechtigungen für Zeitänderung nicht erteilt oder Patient ist noch aktiv.");
      }
    }


  if ($patientendaten["DOB"] != "") {
    $dob = date("Y-m-d", strtotime($patientendaten["DOB"]));
  }
  $date_of_birth =
  $update_data = [
    $patientendaten["SICHTUNGSKATEGORIE"],
    $patientendaten["BEREICH_ID"],
    $patientendaten["PZC"],
    $patientendaten["VORNAME"],
    $patientendaten["NAME"],
    $patientendaten["LAND"],
    $patientendaten["ORT"],
    $patientendaten["STRASSE"],
    $patientendaten["HAUSNUMMER"],
    $patientendaten["BEMERKUNG"],
    $dob,
    $patientendaten["GESCHLECHT"],
    $patientendaten["INFEKT"],
    $patientendaten["EINGANGSART"],
    $patientendaten["UEBERSCHWER"],
    $patientendaten["PATIENTEN_ID"]
  ];



  if ($oldDaten["AKTIV"] != 1) {
    if (in_array("PERM_LATE_ENTER_PATIENTS", $_SESSION["PERMISSIONS"], true)) {
      $late_edit = true;
    } else {
      exit("Error: Benutzer ist nicht dazu berechtigt Datensätze nachträglich zu editieren.");
    }
  }

  $patientendaten = nullEmptyString($patientendaten);
  $updatemessage = "Patient wurde von ".$_SESSION['USER_ID']." bearbeitet.";
  $updatesql = "UPDATE PATIENTEN
                SET SICHTUNGSKATEGORIE = ?,
                    BEREICH_ID = ?,
                    PZC = ?,
                    VORNAME = ?,
                    NAME = ?,
                    LAND = ?,
                    ORT = ?,
                    STRASSE = ?,
                    HAUSNUMMER = ?,
                    BEMERKUNG = ?,
                    `DOB` = ?,
                    GESCHLECHT = ?,
                    INFEKT = ?,
                    EINGANGSART = ?,
                    UEBERSCHWER = ?
                WHERE PATIENTEN_ID = ?;";

  try {
    if ($late_edit) {
      // Änderungen an archivierten Datensätzen werden zusätzlich im Verlauf vermerkt
      if (trim(inputDiff($oldDaten, $patientendaten)) != "") {
        safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
                            VALUES(?, ?, CONCAT(\"<span style='color: red;'>Korrektur nach Entlassung:</span><br>\", ?));",
                            [$patientendaten["PATIENTEN_ID"],
                            $_SESSION['USER_ID'],
                            inputDiff($oldDaten, $patientendaten)
                          ]);
      }
    } else {
      // Änderung im Verlauf vermerken
      if (trim(inputDiff($oldDaten, $patientendaten)) != "") {
        safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
                            VALUES(?, ?, CONCAT('Patientendaten wurden bearbeitet:<br>', ?));",
                            [$patientendaten["PATIENTEN_ID"],
                             $_SESSION['USER_ID'],
                             inputDiff($oldDaten, $patientendaten)
                            ]);
      }
    }
    // Patientendaten aktualisieren
    safeExecute($conn, $updatesql, $update_data);

  } catch (\Exception $e) {
    echo "Error: Beim Speichern der Patientendaten ist ein Fehler aufgetreten. Bitte überprüfen Sie den Datenstand auf der Patientenseite. ".$e;
    exit();
  }

  // Patient wurde (nach Hause) entlassen. Übergaben an ein Rettungsmittel haben andere Vorgehen
  if ($postdata["exit"]) {
    try {
      // Patienten auf "Inaktiv" schalten
      safeExecute($conn, "UPDATE PATIENTEN SET Aktiv = 0, ZEIT_ENTLASSUNG = CURRENT_TIMESTAMP WHERE PATIENTEN_ID = ?", [$patientendaten["PATIENTEN_ID"]]);
      // Entlassung im Verlauf vermerken
      safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
                          VALUES(?, ?, ?)",
                 [$patientendaten["PATIENTEN_ID"], $_SESSION['USER_ID'], "Patient wurden entlassen."]);
    } catch (\Exception $e) {
      echo "Error: Beim Entlassen des Patienten ist ein Fehler aufgetreten. Bitte überprüfen Sie den Datenstand auf der Patientenseite. ".$e;
      exit();
    }
  }

  // Verknüpfte Protokolle werden aktualisiert (INSERT/DELETION)
  $linked_prot = explode(",", $patientendaten["VERKNUEPFUNG"]);
  try {
    // Zuerst müssen alle Referenzen gelöscht werden, die von diesem Protokoll ausgehen, oder darauf zeigen
    //    damit etwaige Löschungen übernommen werden.
    $prot_sql = "DELETE FROM PROTOKOLL_VERKNUEPFUNGEN WHERE SRC_PROTOKOLL = ? OR ZIEL_PROTOKOLL = ?;";
    safeExecute($conn, $prot_sql, [$patientendaten["PATIENTEN_ID"], $patientendaten["PATIENTEN_ID"]]);
  } catch (\Exception $e) {
    echo "Error: Beim Verknüpfungs-Reset des Protokolls ist ein Fehler aufgetreten: ".$e;
  }
  // Löschen ist aus dem Randfall "Leerer String" ausgenommen, damit alle Referenzen entfernt werden können.
  //    Die neuen Inserts werden nur bei übergebenen Daten ausgeführt, sonst führt "" zu einer Referenz zum Protokoll 0.
  if ($patientendaten["VERKNUEPFUNG"] != "") {
    foreach ($linked_prot as $protnr) {
      $prot_sql = "INSERT INTO PROTOKOLL_VERKNUEPFUNGEN(SRC_PROTOKOLL, ZIEL_PROTOKOLL) VALUES (?, ?);";
      try {
        // Als nächstes werden alle noch vorhandenen Referenzen wieder eingefügt.
        safeExecute($conn, $prot_sql, [$patientendaten["PATIENTEN_ID"], $protnr]);
      } catch (\Exception $e) {
        echo "Error: Beim Verknüpfen des Protokolls ist ein Fehler aufgetreten: ".$e;
      }
    }
  }


  // Transport wurde angefordert
  if (array_key_exists("TRANSPORTKATEGORIE", $postdata)) {
    try {
      // Patienten auf Transportkategorie vermerken
      safeExecute($conn, "UPDATE PATIENTEN SET TRANSPORTKATEGORIE = ? WHERE PATIENTEN_ID = ?", [$postdata["TRANSPORTKATEGORIE"], $patientendaten["PATIENTEN_ID"]]);
      // Anforderung im Verlauf vermerken
      safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
                          VALUES(?, ?, ?)",
                 [$patientendaten["PATIENTEN_ID"], $_SESSION['USER_ID'], "Transport in der Kategorie ".$postdata["TRANSPORTKATEGORIE"]." angefordert."]);
    } catch (\Exception $e) {
      echo "Error: Bei der Transportanforderung des Patienten ist ein Fehler aufgetreten. Bitte überprüfen Sie den Datenstand auf der Patientenseite. ".$e;
      exit();
    }
  }

  // Transport wird durchgeführt. Rufname und Zielklinik nachtragen, Patient entlassen.
  if (array_key_exists("TRANSPORT_RUFNAME", $postdata)) {
    if (array_key_exists("TRANSPORT_ZIELKLINIK", $postdata)) {
      try {
        // Name der Zielklinik aus der Nummer ermitteln
        $klinikstring = "Keine Zielklinik angegeben.";
        if ($postdata["TRANSPORT_ZIELKLINIK"]) {
          try {
            $kname = safeQuery($conn, "SELECT KLINIK_NAME FROM KLINIKEN WHERE KLINIK_ID = ?", [$postdata["TRANSPORT_ZIELKLINIK"]]);
            if (count($kname) > 0) {
              $klinikstring = "Ziel: [".$postdata["TRANSPORT_ZIELKLINIK"]."] " . $kname[0]["KLINIK_NAME"]. ".";
            }
          } catch (\Exception $e) {}
        }

        // Rufname des Transportierenden EMS und Zielklinik setzen und auf "Nicht aktiv" setzen
        safeExecute($conn, "UPDATE PATIENTEN SET TRANSPORT_RUFNAME = ?, ZIELKLINIK = ?, Aktiv = 0, ZEIT_ENTLASSUNG = CURRENT_TIMESTAMP WHERE PATIENTEN_ID = ?", [$postdata["TRANSPORT_RUFNAME"], $postdata["TRANSPORT_ZIELKLINIK"], $patientendaten["PATIENTEN_ID"]]);
        // Anforderung im Verlauf vermerken
        safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
        VALUES(?, ?, ?)",
        [$patientendaten["PATIENTEN_ID"], $_SESSION['USER_ID'], "Patient an Rettungsmittel ".$postdata["TRANSPORT_RUFNAME"]." übergeben.\n". $klinikstring]);
      } catch (\Exception $e) {
        echo "Error: Bei der Übergabe an das Rettungsmittel ist ein Fehler aufgetreten. Bitte überprüfen Sie den Datenstand auf der Patientenseite. ".$e;
        exit();
      }
    }
  }

  echo "Einfügen erfolgreich!";

 ?>
