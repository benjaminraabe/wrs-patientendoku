<?php
  include_once '../backend/sessionmanagement.php';

  $accessible_to = array("ADMIN", "TEL", "SICHTER"); // Whitelist für Benutzerrollen

  if (!in_array($_SESSION["USER_ROLE"], $accessible_to, true)) { // Aktiver strict-mode!
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include '../backend/db.php';

  // Wechselnde Anlegeoperation - je nach normaler Ansicht oder Nachtrageansicht
  if (isset($_GET["nachtrPatID"])) {
    $is_new = false;
    $pat_id = $_GET["nachtrPatID"];
    // Sonderberechtigung prüfen
    if ($_SESSION["CAN_BACKDATE_PROTOCOL"] != 1) {
      exit("Keine Nachtrageberechtigung. Der Vorfall wurde gemeldet.");
    }

    // Vorabfrage Nachtragezugang: Existiert der Patient bereits, soll der normale Zugang verwendet werden
    $patientencheck = safeQuery($conn, "SELECT * FROM PATIENTEN p WHERE PATIENTEN_ID = ?;",
                               [$pat_id]);
    if (count($patientencheck) > 0) {
      exit("Ein Patient mit dieser Nummer existiert bereits. Bitte die reguläre Patientenansicht nutzen.");
    }

    // Patient mit Rückdatierter Eingangszeitstempel anlegen.
    safeExecute($conn, "INSERT INTO PATIENTEN(PATIENTEN_ID, ZEIT_EINGANG) VALUES(?, ?)",
      [$pat_id, $_GET["nachtrEinDatum"] . " " . $_GET["nachtrEinZeit"]]);
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
        safeExecute($conn, "INSERT INTO PATIENTEN(PATIENTEN_ID)
        VALUES(?)",
        [$pat_id]);
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

  $kliniken = safeQuery($conn, "SELECT * FROM KLINIKEN;");

  $fahrzeuge = safeQuery($conn, "SELECT * FROM FAHRZEUGE;");

  $verlauf = safeQuery($conn, "SELECT * FROM PATIENTENVERLAUF
                               WHERE PATIENTEN_ID = ?
                               ORDER BY TIMESTAMP DESC",
                      [$pat_id]);

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

  # Mögliche Zuweisungsbereiche werden abgefragt. Wenn der User nur einer UHS zugewiesen ist, kann er nur die Bereiche dieser UHS verwenden.
  if (is_null($_SESSION["UHS"])) {
    $abteilungen = safeQuery($conn, "SELECT BEREICH_ID, a.NAME as ABT_NAME, u.NAME as UHS_NAME
                                     FROM BEREICHE as a
                                     LEFT JOIN UHS_DEFINITION as u
                                     ON a.UHST_ID = u.UHST_ID
                                     ORDER BY a.BEREICH_ID, u.NAME ASC");
  } else {
    $abteilungen = safeQuery($conn, "SELECT BEREICH_ID, a.NAME as ABT_NAME, u.NAME as UHS_NAME
                                     FROM BEREICHE as a
                                     LEFT JOIN UHS_DEFINITION as u
                                     ON a.UHST_ID = u.UHST_ID
                                     WHERE a.UHST_ID = ?
                                     ORDER BY a.BEREICH_ID, u.NAME ASC",
                            [$_SESSION["UHS"]]);
  }

  // PZCs werden abgefragt und initial in den Dialog geladen
  $pzcs = safeQuery($conn, "SELECT PZC, DESCRIPTION, CAT_DESCRIPTION
                            FROM PZC p
                            LEFT JOIN PZC_CATEGORIES pc
                            ON p.PZC_CAT_ID = pc.CAT_ID
                            ORDER BY pc.CAT_ID ASC, p.PZC ASC;");
 ?>

<?php
  include_once '../config.php';

  if ($is_new) {
    $PAGE_TITLE = "Neuer Patient #".str_pad($pat_id, 5, "0", STR_PAD_LEFT);
  } else {
    $PAGE_TITLE = "Patient #".str_pad($pat_id, 5, "0", STR_PAD_LEFT);
  }
?>






<!DOCTYPE html>
<html lang="de" dir="ltr">
  <head>
    <title> <?php echo $PAGE_TITLE . " | " . $ORG_NAME; ?> </title>
    <meta charset="UTF-8">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="../styles/metro-all.min.css">
    <script src="../scripts/metro.min.js" charset="utf-8"></script>
    <script src="../scripts/patient.js" charset="utf-8"></script>
    <link rel="stylesheet" href="../styles/page.css">
    <link rel="stylesheet" href="../styles/patform.css">

    <style type="text/css" media="print">
      @page {
          size: portrait;
          margin: 0;
      }
      body {
        margin: 1.69cm 1.8cm;
      }
    </style>

    <style type="text/css" media="screen">
      @page {
          size: portrait;
          margin: 0;
      }
    </style>
  </head>
  <body>

    <div class="page-wrapper" style="padding-top: 139px; min-width: 550px;">
      <?php include_once '../modules/patform_header.php'; ?>
      <?php if($patientendaten["AKTIV"] == "0") : ?>
      <div class="banner-korrektur mb-5">
        Der Patient ist bereits entlassen worden. <br>
        Änderungen werden im Verlauf dokumentiert, aber nicht in den Patientendaten angepasst.
      </div>
      <?php endif; ?>
        <!-- Sichtungskategorien -->
      <div class="grid pl-4 pr-4">
        <div class="row buttonwrapper buttongroup">
          <div class="cell">
            <button type="button" data-caption="Sofort (S1)" data-text="SOFORT" class="bg-prio1 js-sichtk
              <?php if($patientendaten["SICHTUNGSKATEGORIE"] === "SOFORT"){echo "active";} ?>">
            </button>
          </div>
          <div class="cell">
            <button type="button" data-caption="< 10min (S1)" data-text="SEHR DRINGEND" class="bg-prio2 js-sichtk
              <?php if($patientendaten["SICHTUNGSKATEGORIE"] === "SEHR DRINGEND"){echo "active";} ?>">
            </button>
          </div>
          <div class="cell">
            <button type="button" data-caption="< 30min (S2)" data-text="DRINGEND" class="bg-prio3 js-sichtk
              <?php if($patientendaten["SICHTUNGSKATEGORIE"] === "DRINGEND"){echo "active";} ?>">
            </button>
          </div>
          <div class="cell">
            <button type="button" data-caption="< 90min (S3)" data-text="NORMAL" class="bg-prio4 js-sichtk
              <?php if($patientendaten["SICHTUNGSKATEGORIE"] === "NORMAL"){echo "active";} ?>">
            </button>
          </div>
          <div class="cell">
            <button type="button" data-caption="< 120min (S3)" data-text="NICHT DRINGEND" class="bg-prio5 js-sichtk
              <?php if($patientendaten["SICHTUNGSKATEGORIE"] === "NICHT DRINGEND"){echo "active";} ?>">
            </button>
          </div>
        </div>
      </div>

      <!-- Abteilungen der UHS -->
      <h4 class="pl-4 mt-8">Abteilung</h4>
      <div class="grid pl-4 pr-4 buttongroup">
        <div class="row buttonwrapper mb-3">
          <?php foreach ($abteilungen as $row):?>
            <div class="cell">
              <button
                type="button"
                class="js-bereich <?php if($row["BEREICH_ID"] === $patientendaten["BEREICH_ID"]) {echo "active";} ?>"
                data-caption="<?php echo $row["UHS_NAME"]; ?>"
                data-text="<?php echo $row["ABT_NAME"]; ?>"
                data-value="<?php echo $row["BEREICH_ID"]; ?>">
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <h4 class="pl-4 mt-8">Eingangsdaten</h4>
      <div class="grid pl-4 pr-4 buttongroup">
        <div class="row buttonwrapper mb-3">
            <div class="cell">
              <button type="button" class="js-eingangsart <?php if($patientendaten["EINGANGSART"] != 1 && $patientendaten["EINGANGSART"] != 2) {echo "active";} ?>" data-caption="Eingangsart" data-text="Unbekannt" data-value="0"></button>
            </div>
            <div class="cell">
              <button type="button" class="js-eingangsart <?php if($patientendaten["EINGANGSART"] == 1) {echo "active";} ?>" data-caption="Eingangsart" data-text="UHS selbstständig aufgesucht" data-value="1"  ></button>
            </div>
            <div class="cell">
              <button type="button" class="js-eingangsart <?php if($patientendaten["EINGANGSART"] == 2) {echo "active";} ?>" data-caption="Eingangsart" data-text="Zugeführt durch W:R:S" data-value="2"></button>
            </div>
        </div>
        <div class="row textfieldwrapper" style="border-top: 1px solid black;">
          <div id="form-Pzc" class="cell cell-3 field text-center" data-caption="PZC">
            <input id="pzc-input" type="text" class="input" onfocus="document.activeElement.blur();openPzcSuche();" value="<?php echo $patientendaten["PZC"];?>" tabIndex="0">
          </div>
          <div  id="form-beschreibung-wrapper" class="cell field text-center" data-caption="Beschreibung" onclick="openPzcSuche();">
            <span id="pzc-beschreibung">
            <?php if (array_key_exists("DESCRIPTION", $patientendaten)) {echo $patientendaten["DESCRIPTION"];}?>
            </span>
          </div>
        </div>
        <div class="row textfieldwrapper">
          <div class="cell field p-2" data-caption="Sonstige Eigenschaften">
            <input id="cbInfekt" type="checkbox" data-role="checkbox" data-caption="Infektiös" <?php if($patientendaten["INFEKT"] == 1) {echo "checked";} ?>>
            <input id="cbGewicht" type="checkbox" data-role="checkbox" data-caption="Adipös (>150kg)" <?php if($patientendaten["UEBERSCHWER"] == 1) {echo "checked";} ?>>
          </div>
        </div>
        <div class="row">
          <div class="cell field" data-caption="Verknüpfte Protokolle (Bestätigen mit Enter)">
            <input id="eProtokollVerkn" type="text" data-role="taginput" data-tag-trigger="Enter" value="<?php echo implode(",", $linked_nrs); ?>">
          </div>
        </div>
      </div>

      <!-- Patientendaten -->
      <h4 class="pl-4 mt-8">Patientendaten</h4>
      <div class="grid pl-4 pr-4 mt-8">
        <div class="row textfieldwrapper">
          <div id="form-Name" class="cell field" data-caption="Name">
            <input type="text" class="input" value="<?php echo $patientendaten["NAME"];?>">
          </div>
          <div id="form-Vname" class="cell field" data-caption="Vorname">
            <input type="text" class="input" value="<?php echo $patientendaten["VORNAME"];?>">
          </div>
          <div id="form-Geburtsdatum" class="cell field" data-caption="Geburtsdatum" data-value="<?php echo $patientendaten["DOB"];?>">
            <input type="date" class="input" value="<?php echo $patientendaten["DOB"];?>">
          </div>
          <div id="form-Geschlecht" class="cell field" data-caption="Geschlecht" data-value="<?php echo $patientendaten["GESCHLECHT"];?>">
            <select id="form-geschlecht-select" data-role="select" data-filter="False" class="geschlechtSelect">
              <option value="" <?php if ($patientendaten["GESCHLECHT"] == ''){echo ' selected';}?>>Keine Angabe</option>
              <option value="M"<?php if ($patientendaten["GESCHLECHT"] == 'M'){echo ' selected';}?>>Männlich</option>
              <option value="W"<?php if ($patientendaten["GESCHLECHT"] == 'W'){echo ' selected';}?>>Weiblich</option>
              <option value="D"<?php if ($patientendaten["GESCHLECHT"] == 'D'){echo ' selected';}?>>Divers</option>
            </select>
          </div>
        </div>
        <div class="row textfieldwrapper">
          <div id="form-Land" class="cell field" data-caption="Land">
            <input type="text" class="input" value="<?php echo $patientendaten["LAND"];?>">
          </div>
          <div id="form-Ort" class="cell field" data-caption="Ort">
            <input type="text" class="input" value="<?php echo $patientendaten["ORT"];?>">
          </div>
          <div id="form-Str" class="cell field" data-caption="Straße">
            <input type="text" class="input" value="<?php echo $patientendaten["STRASSE"];?>">
          </div>
          <div id="form-Hnr" class="cell field" data-caption="Hausnummer">
            <input type="text" class="input" value="<?php echo $patientendaten["HAUSNUMMER"];?>">
          </div>
        </div>
      </div>

      <div class="grid pl-4 pr-4 mt-8">
        <div class="row textfieldwrapper">
          <div id="form-Bemerkung" class="cell field bigfield" data-caption="Bemerkung">
            <textarea><?php echo $patientendaten["BEMERKUNG"] ?></textarea>
          </div>
        </div>
      </div>

      <!-- Patientenverlauf -->
      <h4 class="pl-4 mt-8">Verlauf</h4>
      <div class="grid pl-4 pr-4 mb-3 verlauf">
          <?php foreach ($verlauf as $row):?>
            <div class="row">
              <div class="cell cell-2 text-center">
                <?php echo $row["USERNAME"] ?> <br>
                <?php echo dayNumberToDayShort(intval(date("w", strtotime($row["TIMESTAMP"])))) . " " . date("d.m.", strtotime($row["TIMESTAMP"])); ?> <br>
                <?php echo date("H:i", strtotime($row["TIMESTAMP"])) . " Uhr"; ?>
              </div>
              <div class="cell cell-10">
                <?php echo $row["EINTRAG"]; ?>
              </div>
            </div>
          <?php endforeach; ?>
      </div>

      <!-- Lade-Bildschirm -->
      <div id="patform-loader">
        <div data-role="activity" data-type="cycle" data-style="color"></div>
      </div>

      <?php include_once '../modules/footer.php'; ?>


      <!-- PZC-Dialog -->
      <div class="dialog" data-role="dialog" id="pzcDialog">
        <div class="dialog-title">PZC auswählen</div>
        <div class="dialog-content" style="overflow-y: scroll;">

          <input id="i-pzcsuche" type="text" data-role="input" class="primary mb-3" data-search-button="true" onkeyup="pzcSuche(this)">

          <div class="grid pl-0 pr-0 mb-3 pzctable">
              <?php $lastcategory = ''; ?>
              <?php foreach ($pzcs as $row):?>
                <?php if ($lastcategory != $row["CAT_DESCRIPTION"]) : ?>
                  <?php $lastcategory = $row["CAT_DESCRIPTION"]; ?>
                  <div class="row fg-light" style="background-color: #999;">
                    <div class="cell p-0 pl-2"><?php echo $row["CAT_DESCRIPTION"]; ?></div>
                  </div>
                <?php endif; ?>
                <div class="row" onclick="selectPZC('<?php echo $row["PZC"]; ?>', '<?php echo $row["DESCRIPTION"]; ?>');" style="border-bottom: 1px solid lightgray;">
                  <div class="cell cell-2 text-center">
                    <?php echo $row["PZC"] ?> <br>
                  </div>
                  <div class="cell cell-10">
                    <?php echo $row["DESCRIPTION"]; ?>
                  </div>
                  <div style="display: none;"><?php echo $row["CAT_DESCRIPTION"]; ?></div>
                </div>
              <?php endforeach; ?>
          </div>
        </div>
        <div class="dialog-actions">
            <button class="button js-dialog-close place-right">Schließen</button>
        </div>
      </div>


      <!-- Dialog Entlassung -->
      <div class="dialog" data-role="dialog" id="entlassungDialog">
        <div class="dialog-title">Patient entlassen</div>
        <div class="dialog-content" style="overflow-y: auto;">
          <div class="text-center" id="dlgEntlassenSuccess">
            <span class="mif-checkmark icon fg-green"></span>
            Alle Daten sind vollständig!
          </div>

          <div class="text-center" id="dlgEntlassenFail">
            <div class="grid" id="dlgEntlassenFailGrid">
            </div>
          </div>

        </div>
        <div class="dialog-actions pr-6">
            <button class="button success w-100 mb-2" id="dlgEntlassenSuccessButton" onclick="run_pat_exit();">Patient entlassen</button>
            <button class="button outline alert w-100 mb-2" id="dlgEntlassenForceButton" onclick="update_pat_data(true);">Entlassung erzwingen</button>
            <button class="button w-100 js-dialog-close">Abbrechen</button>
        </div>
      </div>


      <!-- Dialog Transport -->
      <div class="dialog" data-role="dialog" id="transportDialog">
        <div class="dialog-title">
          <?php if(is_null($patientendaten["TRANSPORTKATEGORIE"])){echo "Transportanforderung";} else {echo "Entlassung an Rettungsmittel";} ?>
        </div>
        <div class="dialog-content" style="overflow-y: auto; min-height: 300px;">
          <div class="grid mb-4">
            <div class="row">
              <select id="form-transport-kategorie" data-role="select" data-prepend="Kategorie" <?php if (!is_null($patientendaten["TRANSPORTKATEGORIE"])){echo " disabled";} ?>>
                <option value="KBF"
                  <?php if($patientendaten["TRANSPORTKATEGORIE"] == "KBF"){echo " selected";} ?>>KBF</option>
                <option value="Notfall K"
                  <?php if($patientendaten["TRANSPORTKATEGORIE"] == "Notfall K"){echo " selected";} ?>>Notfall K</option>
                <option value="Notfall 01"
                  <?php if($patientendaten["TRANSPORTKATEGORIE"] == "Notfall 01"){echo " selected";} ?>>Notfall 01</option>
                <option value="Notfall 11"
                  <?php if($patientendaten["TRANSPORTKATEGORIE"] == "Notfall 11"){echo " selected";} ?>>Notfall 11</option>
                <!-- <option value="Platztransport"
                  <?php #if($patientendaten["TRANSPORTKATEGORIE"] == "Platztransport"){echo " selected";} ?>>Platztransport</option> -->
                <!-- <option value="Infektion"
                  <?php #if($patientendaten["TRANSPORTKATEGORIE"] == "Infektion"){echo " selected";} ?>>Infektion</option>
                <option value="Überschwer"
                  <?php #if($patientendaten["TRANSPORTKATEGORIE"] == "Überschwer"){echo " selected";} ?>>Überschwer</option> -->
              </select>
            </div>
            <div class="row mt-2 <?php if(is_null($patientendaten["TRANSPORTKATEGORIE"])){echo " hidden";} ?>">
              <input id="form-transport-rufname" type="text" data-role="input" data-prepend="Rufname ">
              <!-- <select data-prepend="Rufname" data-role="select">
                <?php #foreach ($fahrzeuge as $fzg): ?>
                  <option value="<?php #echo $fzg["RUFNAME"]; ?>"><?php #echo $fzg["RUFNAME"]; ?></option>
                <?php #endforeach; ?>
              </select> -->
            </div>
            <div class="row mt-2 <?php if(is_null($patientendaten["TRANSPORTKATEGORIE"])){echo " hidden";} ?>">
              <select id="form-transport-zielkh" data-role="select" data-prepend="Zielklinik">
                <option value=""></option>
                <?php foreach ($kliniken as $klinik):?>
                  <option value="<?php echo $klinik["KLINIK_ID"];?>"><?php echo $klinik["KLINIK_NAME"];?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="text-center" id="dlgTransportSuccess">
            <span class="mif-checkmark icon fg-green"></span>
            Alle Daten sind vollständig!
          </div>

          <div class="text-center" id="dlgTransportFail">
            <div class="grid" id="dlgTransportFailGrid">
            </div>
          </div>

        </div>
        <?php if (is_null($patientendaten["TRANSPORTKATEGORIE"])): ?>
          <!-- Transportanforderung -->
          <div class="dialog-actions pr-6">
              <button class="button success w-100 mb-2" id="dlgTransportButton" onclick="run_pat_transport_req();">Anforderung speichern</button>
              <button class="button outline alert w-100 mb-2" id="dlgTransportForceButton" onclick="run_pat_transport_req();">Anforderung erzwingen</button>
              <button class="button w-100 js-dialog-close">Abbrechen</button>
          </div>
        <?php endif; ?>
        <?php if (!is_null($patientendaten["TRANSPORTKATEGORIE"])): ?>
          <!-- Entlassung an Rettungsmittel -->
          <div class="dialog-actions pr-6">
              <button class="button success w-100 mb-2" id="dlgTransportButton" onclick="run_pat_transport_req(true);">Entlassung speichern</button>
              <button class="button outline alert w-100 mb-2" id="dlgTransportForceButton" onclick="run_pat_transport_req(true);">Entlasung erzwingen</button>
              <button class="button w-100 js-dialog-close">Abbrechen</button>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <script src="../scripts/customformfields.js" charset="utf-8"></script>
  </body>
</html>
