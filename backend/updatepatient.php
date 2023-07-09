<?php
  include_once '../backend/sessionmanagement.php';

  $accessible_to = array("ADMIN", "SICHTER"); // Whitelist für Benutzerrollen

  if (!in_array($_SESSION["USER_ROLE"], $accessible_to, true)) { // Aktiver strict-mode!
    echo "Error: Zugriff verweigert.";
    exit();
  }
?>

<?php
  function inputDiff($oldData, $newData) {
    $changes = array();
    foreach ($newData as $key => $value) {
      if(array_key_exists($key, $oldData)) {
        // Sonderfall Geburtsdatum: Standardwert aus der DB abfangen
        if ($key == "DOB" && $oldData["DOB"] == "0000-00-00" && $value == "") {
          continue;
        }
        if ($oldData[$key] != $value) {
          array_push($changes, " - [".$key."] " . $oldData[$key] . " => " . $value);
        }
      }
    }
    return implode("<br>", $changes);
  }



 ?>

<?php
  include_once 'db.php';
  $late_edit = false;
  $postdata = json_decode(file_get_contents('php://input'), true);

  $patientendaten = $postdata["patient"];
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
    $patientendaten["DOB"],
    $patientendaten["GESCHLECHT"],
    $patientendaten["INFEKT"],
    $patientendaten["EINGANGSART"],
    $patientendaten["UEBERSCHWER"],
    $patientendaten["PATIENTEN_ID"]
  ];

  // Bisheriger Datenstand wird abgerufen um herauszufinden was geändert wurde
  $oldDaten = safeQuery($conn, "SELECT * FROM PATIENTEN WHERE PATIENTEN_ID = ?", [$patientendaten["PATIENTEN_ID"]]);
  if (count($oldDaten) > 0) {
    $oldDaten = $oldDaten[0];
  } else {
    // Fallback, sollte aber nie auftreten.
    exit("Error: Der Patient wurde anscheinend noch nicht angelegt.");
  }

  if ($oldDaten["AKTIV"] != 1) {
    if ($_SESSION["CAN_LATE_EDIT"] == 1) {
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
      // Änderungen an archivierten Datensätzen werden nur noch im Verlauf vorgenommen
      safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
                          VALUES(?, ?, CONCAT(\"<span style='color: red;'>Nachtragung:</span><br>\", ?));",
                          [$patientendaten["PATIENTEN_ID"],
                           $_SESSION['USER_ID'],
                           inputDiff($oldDaten, $patientendaten)
                          ]);
    } else {
      // Patientendaten aktualisieren
      safeExecute($conn, $updatesql, $update_data);
      // Änderung im Verlauf vermerken
      safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
                          VALUES(?, ?, CONCAT('Patientendaten wurden bearbeitet:<br>', ?));",
                          [$patientendaten["PATIENTEN_ID"],
                           $_SESSION['USER_ID'],
                           inputDiff($oldDaten, $patientendaten)
                          ]);
    }
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

  // Verknüpfte Protokolle werden aktualisiert (INSERT/DEL)

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
        [$patientendaten["PATIENTEN_ID"], $_SESSION['USER_ID'], "Patient an Rettungsmittel ".$postdata["TRANSPORT_RUFNAME"]." übergeben. ". $klinikstring]);
      } catch (\Exception $e) {
        echo "Error: Bei der Übergabe an das Rettungsmittel ist ein Fehler aufgetreten. Bitte überprüfen Sie den Datenstand auf der Patientenseite. ".$e;
        exit();
      }
    }
  }

  echo "Einfügen erfolgreich!";


 ?>
