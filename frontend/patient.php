<?php
  // Große Formularseite für einen Patienten mit übergebener Kennnung (GET-Parameter).
  // Hier wird ein Patient angelegt oder angezeigt, Änderungen, Transporte und Entlassungen
  //    werden von dieser Seite ausgeführt.

  include_once '../backend/sessionmanagement.php';

  $accessible_to = array("ADMIN", "TEL", "SICHTER"); // Whitelist für Benutzerrollen
  if (!in_array($_SESSION["USER_ROLE"], $accessible_to, true)) { // Aktiver strict-mode!
    echo "Zugriff verweigert.";
    exit();
  }
?>


<?php
  include_once 'patient/patient_datenabfragen.php';
  include_once '../config.php';

  if ($is_new) {
    $PAGE_TITLE = "Neuer Patient #".$pat_id;
  } else {
    $PAGE_TITLE = "Patient #".$pat_id;
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
    <script src="patient/patient.js" charset="utf-8"></script>
    <link rel="stylesheet" href="../styles/page.css">
    <link rel="stylesheet" href="../styles/customforms.css">
    <link rel="stylesheet" href="patient/patient.css">
  </head>
  <body>

    <div class="page-wrapper">
      <?php include_once '../modules/patform_header.php'; ?>

      <div class="page-content">
        <!-- Warnhinweis für entlassene Patienten -->
        <?php if($patientendaten["AKTIV"] == "0") : ?>
        <div class="banner-korrektur mt-4 mb-5">
          Der Patient ist bereits entlassen worden. <br>
          Änderungen werden im Verlauf markiert.
        </div>
        <?php endif; ?>


        <!-- Tab-Navigation und Tab-Inhalte -->
        <div class="tab-box p-4">
          <div class="tab-navigation pl-4">
            <button class="tab-button active" onclick="openTab('#tab-eingang', this)">
              [F1] Eingang</button>
            <button class="tab-button" onclick="openTab('#tab-daten', this)">
              [F2] Patientendaten</button>
            <button class="tab-button" onclick="openTab('#tab-verlauf', this)">
              [F3] Verlauf</button>
            <button class="tab-button" onclick="openExitPage(); openTab('#tab-ausgang', this)">
              [F4] Transport / Ausgang</button>
          </div>

          <div class="tab-wrapper">
            <div class="tab-patient" id="tab-eingang">
              <?php include 'patient/patient_eingang.php' ?>
            </div>

            <div class="tab-patient" id="tab-daten">
              <?php include 'patient/patient_daten.php' ?>
            </div>

            <div class="tab-patient" id="tab-verlauf">
              <?php include 'patient/patient_verlauf.php' ?>
            </div>

            <div class="tab-patient" id="tab-ausgang">
              <?php include 'patient/patient_ausgang.php' ?>
            </div>
          </div>
        </div>





        <!-- Lade-Overlay -->
        <div id="patform-loader">
          <div data-role="activity" data-type="cycle" data-style="color"></div>
        </div>
      </div>

      <?php include_once '../modules/footer.php'; ?>
    </div>


    <!-- Nachtragezugang Zeiten/Daten -->
    <?php
      $zeit_daten = safeQuery($conn, "SELECT DATE(ZEIT_EINGANG) AS EINDATUM, TIME(ZEIT_EINGANG) AS EINZEIT,
                                      DATE(ZEIT_ENTLASSUNG) AS AUSDATUM, TIME(ZEIT_ENTLASSUNG) AS AUSZEIT
                                      FROM PATIENTEN p
                                      WHERE PATIENTEN_ID = ?;", [$pat_id]);
    ?>
    <div class="dialog" data-role="dialog" id="dlgTimeEdit">
      <div class="dialog-title">Zeiten editieren</div>
      <div class="dialog-content" style="overflow-y: scroll;">
        <div class="grid">
          <div class="row">
            <div class="cell">
              Eingangsdatum:
            </div>
            <div class="cell">
              <input type="date" id="frmEinDatum" data-role="input" data-clear-button="false"
                value="<?php echo $zeit_daten[0]["EINDATUM"]; ?>">
            </div>
          </div>
          <div class="row">
            <div class="cell">
              Eingangszeit:
            </div>
            <div class="cell">
              <input type="time" id="frmEinZeit" data-role="input" data-clear-button="false"
                value="<?php echo $zeit_daten[0]["EINZEIT"]; ?>">
            </div>
          </div>
          <hr>
          <div class="row pt-4" style="border-top: 1px solid lightgray;">
            <div class="cell">
              Ausgangsdatum:
            </div>
            <div class="cell">
              <input type="date" id="frmAusZeit" data-role="input" data-clear-button="false"
                value="<?php echo $zeit_daten[0]["AUSDATUM"]; ?>">
            </div>
          </div>
          <div class="row">
            <div class="cell">
              Ausgangszeit:
            </div>
            <div class="cell">
              <input type="time" id="frmAusDatum" data-role="input" data-clear-button="false"
                value="<?php echo $zeit_daten[0]["AUSZEIT"]; ?>">
            </div>
          </div>
        </div>
      </div>
      <div class="dialog-actions pr-6">
          <button class="button success w-100 mb-2" onclick="update_pat_data(false, false, false, true)">Speichern</button>
          <button class="button w-100 js-dialog-close">Abbrechen</button>
      </div>
    </div>

    <script src="../scripts/customformfields.js" charset="utf-8"></script>
  </body>
</html>
