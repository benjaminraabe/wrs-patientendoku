<?php
  include '../backend/db.php';

  // Wechselnde Anlegeoperation - je nach normaler Ansicht oder Nachtrageansicht
  if (isset($_GET["nachtrPatID"])) {
    $is_new = false;
    $pat_id = $_GET["nachtrPatID"];
    // Sonderberechtigung prüfen
    if (!in_array("PERM_LATE_ENTER_PATIENTS", $_SESSION["PERMISSIONS"], true)) {
      exit("Keine Nachtrageberechtigung. Der Vorfall wurde gemeldet.");
    }

    // Vorabfrage Nachtragezugang: Existiert der Patient bereits, soll der normale Zugang verwendet werden
    $patientencheck = safeQuery($conn, "SELECT * FROM PATIENTEN p WHERE PATIENTEN_ID = ?;",
                               [$pat_id]);
    if (count($patientencheck) > 0) {
      exit("Ein Patient mit dieser Nummer existiert bereits. Bitte die reguläre Patientenansicht verwenden.");
    }

    // Patient mit Rückdatierter Eingangszeitstempel anlegen.
    $eingangsdatetime = date("Y-m-d H:i:s", strtotime($_GET["nachtrEinDatum"] . " " . $_GET["nachtrEinZeit"]));
    safeExecute($conn, "INSERT INTO PATIENTEN(PATIENTEN_ID, ZEIT_EINGANG) VALUES(?, ?)",
      [$pat_id, $eingangsdatetime]);
    safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
    VALUES(?, ?, ?)",
    [$pat_id, $_SESSION['USER_ID'], "Patient rückdatiert (".$_GET["nachtrEinDatum"] . " " . $_GET["nachtrEinZeit"].") Uhr erfasst."]);

    $patientendaten = safeQuery($conn, "SELECT * FROM PATIENTEN
      WHERE PATIENTEN_ID = ?",
      [$pat_id]);

    if(count($patientendaten) > 0) {
      $patientendaten = $patientendaten[0];
    } else {
      $patientendaten = array();
    }

  } else {
    // "Normaler Aufruf der Patientenseite"
    $pat_id = $_GET["id"];
    $is_new = false;

    $patientendaten = safeQuery($conn, "SELECT * FROM PATIENTEN p
      LEFT JOIN PZC pzc
      ON p.PZC = pzc.PZC
      WHERE PATIENTEN_ID = ?;",
      [$pat_id]);

      # Patient existiert noch nicht und wird nun angelegt.
      if (count($patientendaten) == 0) {
        $is_new = true;
        safeExecute($conn, "INSERT INTO PATIENTEN(PATIENTEN_ID) VALUES(?)", [$pat_id]);
        safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
        VALUES(?, ?, ?)",
        [$pat_id, $_SESSION['USER_ID'], "Patient erfasst."]);

        $patientendaten = safeQuery($conn, "SELECT * FROM PATIENTEN
          WHERE PATIENTEN_ID = ?",
          [$pat_id]);
        }
        if(count($patientendaten) > 0) {
          $patientendaten = $patientendaten[0];
        } else {
          $patientendaten = array();
        }
  }




  // Seite wird nicht angezeigt, wenn der Benutzer keinen Zugriff auf den Patienten hat
  if (!is_null($_SESSION['UHS'])) {
    if (!is_null($patientendaten['BEREICH_ID'])) {
      $uhst = array();
      try {
        $uhst = safeQuery($conn, "SELECT UHST_ID FROM BEREICHE WHERE BEREICH_ID = ?", [$patientendaten['BEREICH_ID']]);
      } catch (\Exception $e) {
        exit("Zugriff auf diesen Patienten nicht erlaubt. Die Anfrage wurde dem Administrator gemeldet.");
      }
      if (count($uhst) > 0) {
        if ($_SESSION['UHS'] != $uhst[0]["UHST_ID"]) {
          exit("Zugriff auf diesen Patienten nicht erlaubt. Die Anfrage wurde dem Administrator gemeldet.");
        }
      } else {
        exit("Zugriff auf diesen Patienten nicht erlaubt. Die Anfrage wurde dem Administrator gemeldet.");
      }
    }
  }



  // Datenabfragen
  $kliniken = safeQuery($conn, "SELECT * FROM KLINIKEN;");
  $fahrzeuge = safeQuery($conn, "SELECT * FROM FAHRZEUGE;");
  $verlauf = safeQuery($conn, "SELECT * FROM PATIENTENVERLAUF
                               WHERE PATIENTEN_ID = ?
                               ORDER BY TIMESTAMP DESC", [$pat_id]);

  // Alle Verknüpften Protokolle zu dieser Pat-ID werden abgerufen.
  $linked_prot = safeQuery($conn, "SELECT * FROM PROTOKOLL_VERKNUEPFUNGEN WHERE SRC_PROTOKOLL = ? OR ZIEL_PROTOKOLL = ?;",
    [$pat_id, $pat_id]
  );
  $linked_nrs = array();
  foreach ($linked_prot as $row) {
    array_push($linked_nrs, $row["SRC_PROTOKOLL"], $row["ZIEL_PROTOKOLL"]);
  }
  // Duplikate werden entfernt
  $linked_nrs = array_unique($linked_nrs);
  // Selbstreferenzen werden entfernt
  if (($key = array_search($pat_id, $linked_nrs)) !== false) {
    unset($linked_nrs[$key]);
  }

  // Mögliche Zuweisungsbereiche werden abgefragt. Wenn der User nur einer UHS zugewiesen ist, kann er nur die Bereiche dieser UHS verwenden.
  if (is_null($_SESSION["UHS"])) {
    $uhsen = safeQuery($conn, "SELECT * FROM UHS_DEFINITION ORDER BY UHST_ID");
    $abteilungen = safeQuery($conn, "SELECT BEREICH_ID, a.NAME as ABT_NAME, u.UHST_ID, u.NAME as UHS_NAME
                                     FROM BEREICHE as a
                                     LEFT JOIN UHS_DEFINITION as u
                                     ON a.UHST_ID = u.UHST_ID
                                     ORDER BY a.UHST_ID, a.BEREICH_ID, u.NAME ASC");
  } else {
    $uhsen = safeQuery($conn, "SELECT * FROM UHS_DEFINITION WHERE UHST_ID = ? ORDER BY UHST_ID", [$_SESSION["UHS"]]);
    $abteilungen = safeQuery($conn, "SELECT BEREICH_ID, a.NAME as ABT_NAME, u.UHST_ID, u.NAME as UHS_NAME
                                     FROM BEREICHE as a
                                     LEFT JOIN UHS_DEFINITION as u
                                     ON a.UHST_ID = u.UHST_ID
                                     WHERE a.UHST_ID = ?
                                     ORDER BY a.UHST_ID, a.BEREICH_ID, u.NAME ASC",
                            [$_SESSION["UHS"]]);
  }

  // PZCs werden abgefragt und initial in den Dialog geladen
  $pzcs = safeQuery($conn, "SELECT PZC, DESCRIPTION, CAT_DESCRIPTION
                            FROM PZC p
                            LEFT JOIN PZC_CATEGORIES pc
                            ON p.PZC_CAT_ID = pc.CAT_ID
                            ORDER BY pc.CAT_ID ASC, p.PZC ASC;");
 ?>
