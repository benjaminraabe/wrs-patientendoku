<?php
  // Implementiert eine große Statistik über vorhandene Patienten und Transporte.
  // Initial werden alle Daten abgerufen und für die notwendigen Statistiken
  //    pivotiert oder nachbearbeitet.

  
  include_once '../backend/sessionmanagement.php';

  $accessible_to = array("ADMIN", "TEL"); // Whitelist für Benutzerrollen.

  if (!in_array($_SESSION["USER_ROLE"], $accessible_to, true)) { // Aktiver strict-mode!
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../backend/db.php';
  $uhsen = safeQuery($conn, "SELECT * FROM UHS_DEFINITION");
  $patient_data = safeQuery($conn, "SELECT * FROM PATIENTEN p
                                    LEFT JOIN BEREICHE b on p.BEREICH_ID = b.BEREICH_ID
                                    ORDER BY p.ZEIT_EINGANG ASC;");
  $transport_data = safeQuery($conn, "SELECT * FROM PATIENTEN p
                                      WHERE TRANSPORTKATEGORIE IS NOT NULL AND TRANSPORT_RUFNAME IS NOT NULL
                                      ORDER BY p.ZEIT_ENTLASSUNG ASC;");
  $eingang = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) as CT FROM PATIENTEN GROUP BY EINGANGSART;");


  // Pivot: Sichtungskategorie
  function newSKRow() {
    return ["Gesamt" => 0,
            "SOFORT" => 0,
            "SEHR DRINGEND" => 0,
            "DRINGEND" => 0,
            "NORMAL" => 0,
            "NICHT DRINGEND" => 0];
  }

  function newUHSRow($uhsen, $key="UHST_ID") {
    $res = ["Infektion" => 0];
    foreach ($uhsen as $uhs) {
      $res[strval($uhs[$key])] = 0;
    }
    return $res;
  }

  function newTransportRow() {
    return ["Gesamt" => 0,
            "KBF" => 0,
            "Notfall K" => 0,
            "Notfall 01" => 0,
            "Notfall 11" => 0];
  }

  // Berechne alle Schichten vom ersten bis zum letzten Patienten
  function calculateShiftData($patienten, $key="ZEIT_EINGANG") {
    $schicht_beginn_zeiten = array();
    $schicht = NULL;
    if (count($patienten) > 0) {
      // Erster Patient
      $first_ts = strtotime($patienten[0][$key]);
      $start_date = date("Y-m-d", $first_ts);
      $start_time = date("H:i:s", $first_ts);

      // Letzter Patient
      $last_ts = strtotime($patienten[count($patienten)-1][$key]);

      // Finde die erste Korrespondierende Schicht, in der der Patient eingegangen ist
      if (strtotime($start_time) < strtotime("20:00:00")) {
        if (strtotime($start_time) < strtotime("08:00:00")) {
          $schicht = "Nachtschicht";
          $schichtbeginn = (strtotime($start_date. " 20:00") - (60*60*24));
        } else {
          // Heutiger Tag, nach 08:00
          $schicht = "Tagschicht";
          $schichtbeginn = strtotime($start_date. " 08:00");
        }
      } else {
        // Heutiger Tag, nach 20:00 Uhr
        $schicht = "Nachtschicht";
        $schichtbeginn = strtotime($start_date. " 20:00");
      }

      // Generiert alle Schichten zwischen dem ersten Patient und dem letzten Patienten
      while ($schichtbeginn <= $last_ts) {
        array_push($schicht_beginn_zeiten, $schichtbeginn);
        $schichtbeginn = $schichtbeginn + (60*60*12); # 12 Stunden zur nächsten Schicht
      }
    }
    return [$schicht, $schicht_beginn_zeiten];
  }

  function pivotData($uhsen, $patienten, $transporte) {
    // Berechne alle Schichten
    $pat_schichten = calculateShiftData($patienten);
    $schicht_name = $pat_schichten[0];
    $pat_schichten = $pat_schichten[1];

    // Initialisierung der Datensätze
    $sk_gesamt = newSKRow();
    $nach_sk = [];
    $sk_platztransport_gesamt = newSKRow();
    $nach_sk_platztransport = [];
    $uhs_gesamt = newUHSRow($uhsen);
    $nach_uhs = [];
    $transport_gesamt = newTransportRow();
    $nach_transport = [];

    // Initialisierung der Zeilen
    $pat_index = 0;
    $pat_count = count($patienten);
    $pat_transp_count = count($transporte);
    $pat_transp_index = 0;
    $shift_increment = 60*60*12; # 12 Stunden

    // Datensätze werden ausgewertet;
    foreach ($pat_schichten as $schicht) {
      $schicht_ident = date("d.m.Y", $schicht) . "<br>" . $schicht_name;

      // Zeileninitialisierung
      $sk_row = newSKRow();
      $sk_platz_row = newSKRow();
      $uhs_row = newUHSRow($uhsen);
      $transport_row = newTransportRow();

      ### Daten der regulären Patienten auslesen
      while ($pat_index < $pat_count) {
        $pat_time = strtotime($patienten[$pat_index]["ZEIT_EINGANG"]);
        // Prüfen ob Patient in der aktuellen Schicht liegt.
        if ($pat_time < $schicht || $pat_time >= ($schicht + $shift_increment)) {break;}
        // Patientendaten eintragen

        // Nach Sichtungskategorie
        $sk_row[$patienten[$pat_index]["SICHTUNGSKATEGORIE"]]++;
        $sk_row["Gesamt"]++;
        $sk_gesamt[$patienten[$pat_index]["SICHTUNGSKATEGORIE"]]++;
        $sk_gesamt["Gesamt"]++;

        // Nach UHS
        if ($patienten[$pat_index]["INFEKT"] == 1) {
          $uhs_row["Infektion"]++;
          $uhs_gesamt["Infektion"]++;
        }
        if($patienten[$pat_index]["UHST_ID"] != NULL){
          $uhs_row[strval($patienten[$pat_index]["UHST_ID"])]++;
          $uhs_gesamt[strval($patienten[$pat_index]["UHST_ID"])]++;
        }

        // Eingang von W:R:S, Nach Sichtungskategorie
        if ($patienten[$pat_index]["EINGANGSART"] == 2) {
          $sk_platz_row[$patienten[$pat_index]["SICHTUNGSKATEGORIE"]]++;
          $sk_platz_row["Gesamt"]++;
          $sk_platztransport_gesamt[$patienten[$pat_index]["SICHTUNGSKATEGORIE"]]++;
          $sk_platztransport_gesamt["Gesamt"]++;
        }
        // Index erhöhen
        $pat_index++;
      }

      ### Patiententransporte werten
      while ($pat_transp_index < $pat_transp_count) {
        $pat_transp_time = strtotime($transporte[$pat_transp_index]["ZEIT_ENTLASSUNG"]);
        // Prüfen ob Patient in der aktuellen Schicht liegt.
        if ($pat_transp_time < $schicht || $pat_transp_time >= ($schicht + $shift_increment)) {break;}

        // Nach Sichtungskategorie
        $transport_row[$transporte[$pat_transp_index]["TRANSPORTKATEGORIE"]]++;
        $transport_row["Gesamt"]++;
        $transport_gesamt[$transporte[$pat_transp_index]["TRANSPORTKATEGORIE"]]++;
        $transport_gesamt["Gesamt"]++;

        // Index erhöhen
        $pat_transp_index++;
      }

      // Schichten einfügen, wenn sie Patienten enthalten
      if ($sk_row["Gesamt"] > 0) {array_push($nach_sk, [$schicht_ident, $sk_row]);}
      if ($sk_platz_row["Gesamt"] > 0) {array_push($nach_sk_platztransport , [$schicht_ident, $sk_platz_row]);}
      if (array_sum($uhs_row) > 0) {array_push($nach_uhs , [$schicht_ident, $uhs_row]);}
      if ($transport_row["Gesamt"] > 0) {array_push($nach_transport , [$schicht_ident, $transport_row]);}

      // Schichtname wechseln
      if ($schicht_name == "Nachtschicht") {$schicht_name = "Tagschicht";}
      else {$schicht_name = "Nachtschicht";}
    }
    return [$sk_gesamt, $sk_platztransport_gesamt, $uhs_gesamt, $transport_gesamt,
            $nach_sk, $nach_sk_platztransport, $nach_uhs, $nach_transport];
  }
 ?>

 <?php
  $stats = pivotData($uhsen, $patient_data, $transport_data);

  $sk_gesamt = $stats[0];
  $nach_sk = $stats[4];

  $sk_platztransport_gesamt = $stats[1];
  $nach_sk_platztransport = $stats[5];

  $uhs_gesamt = $stats[2];
  $nach_uhs = $stats[6];

  $transport_gesamt = $stats[3];
  $nach_transport = $stats[7];
  ?>





  <?php
    include_once '../config.php';
    $PAGE_TITLE = "Statistik";
  ?>


  <html lang="de" dir="ltr">
    <head>
      <title> <?php echo $PAGE_TITLE . " | " . $ORG_NAME; ?> </title>
      <meta charset="utf-8">
      <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
      <link rel="stylesheet" href="../styles/metro-all.min.css">
      <script src="../scripts/metro.min.js" charset="utf-8"></script>
      <link rel="stylesheet" href="../styles/page.css">
      <link rel="stylesheet" href="../styles/patliste.css">
    </head>
    <body>
      <div class="page-wrapper">
        <?php include_once '../modules/header.php'; ?>



        <!-- Patienten nach Abschnitt
             Initial aufgeführrt: BHP, Wackinger, RKISH, Infektion
        -->
        <h4 class="pr-5 pl-5">
          Patienten nach Sichtungskategorie
          <button type="button" class="button place-right no-print" onclick="print();">Report drucken</button>
        </h4>
        <h4 class="pr-5 pl-5"><small>
          Selbstständig eingetroffen: <?php if (count($eingang) > 1){echo $eingang[1]["CT"];}else {echo "-";} ?>
          | Von W:R:S zugeführt: <?php if (count($eingang) > 2){echo $eingang[2]["CT"];}else {echo "-";} ?>
          | Unbekannt: <?php if (count($eingang) > 0){echo $eingang[0]["CT"];}else {echo "-";} ?>
        </small></h4>
        <div class="grid pr-5 pl-5 mb-8 patliste-liste rkish-table">
          <div class="row header-row fg-light" style="background-color: #024ea4;">
            <div class="cell text-center"></div>
            <div class="cell text-center"><b>&Sigma;</b></div>
            <div class="cell text-center"><b>Sofort</b></div>
            <div class="cell text-center"><b>Sehr Dringend</b></div>
            <div class="cell text-center"><b>Dringend</b></div>
            <div class="cell text-center"><b>Normal</b></div>
            <div class="cell text-center"><b>Nicht Dringend</b></div>
          </div>
          <div class="row data-row">
            <div class="cell text-center vert-center"><b>Gesamt</b></div>
            <?php foreach ($sk_gesamt as $value): ?>
              <div class="cell text-center"><b><?php echo $value; ?></b></div>
            <?php endforeach; ?>
          </div>
          <?php foreach ($nach_sk as $element) :?>
            <div class="row data-row">
              <div class="cell text-center">
                <?php echo $element[0] ?>
              </div>
              <?php foreach ($element[1] as $value): ?>
                <div class="cell text-center"><?php echo $value; ?></div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>



        <!-- Nur Platztransporte-->
        <h4 class="pr-5 pl-5">
          Platztransporte / Patienten von W:R:S
        </h4>
        <div class="grid pr-5 pl-5 mb-8 patliste-liste rkish-table">
          <div class="row header-row fg-light" style="background-color: #024ea4;">
            <div class="cell text-center"></div>
            <div class="cell text-center"><b>&Sigma;</b></div>
            <div class="cell text-center"><b>Sofort</b></div>
            <div class="cell text-center"><b>Sehr Dringend</b></div>
            <div class="cell text-center"><b>Dringend</b></div>
            <div class="cell text-center"><b>Normal</b></div>
            <div class="cell text-center"><b>Nicht Dringend</b></div>
          </div>
          <div class="row data-row">
            <div class="cell text-center vert-center"><b>Gesamt</b></div>
            <?php foreach ($sk_platztransport_gesamt as $value): ?>
              <div class="cell text-center"><b><?php echo $value; ?></b></div>
            <?php endforeach; ?>
          </div>
          <?php foreach ($nach_sk_platztransport as $element) :?>
            <div class="row data-row">
              <div class="cell text-center">
                <?php echo $element[0] ?>
              </div>
              <?php foreach ($element[1] as $value): ?>
                <div class="cell text-center"><?php echo $value; ?></div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>




        <!-- Patienten nach Abschnitt
             Initial aufgeführrt: BHP, Wackinger, RKISH, Isolation
        -->
        <h4 class="pr-5 pl-5">Patienten nach Abschnitt</h4>
        <div class="grid pr-5 pl-5 mb-8 patliste-liste rkish-table">
          <div class="row header-row fg-light" style="background-color: #024ea4;">
            <div class="cell text-center"></div>
            <div class="cell text-center"><b>Infektion</b></div>
            <?php foreach ($uhsen as $uhs): ?>
              <div class="cell text-center"><b>
                <?php echo $uhs["NAME"]; ?>
              </b></div>
            <?php endforeach; ?>
          </div>
          <div class="row data-row">
            <div class="cell text-center vert-center"><b>Gesamt</b></div>
            <div class="cell text-center"><b>
              <?php echo $uhs_gesamt["Infektion"]; ?>
            </b></div>
            <?php foreach ($uhsen as $uhs): ?>
              <div class="cell text-center"><b>
                <?php echo $uhs_gesamt[strval($uhs["UHST_ID"])]; ?>
              </b></div>
            <?php endforeach; ?>
          </div>
          <?php foreach ($nach_uhs as $element) :?>
            <div class="row data-row">
              <div class="cell text-center">
                <?php echo $element[0]; ?>
              </div>
              <?php foreach ($element[1] as $wert): ?>
                <div class="cell text-center"><?php echo $wert; ?></div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>



        <!--
          Transporte nach Kategorie
        -->
        <h4 class="pr-5 pl-5">Transporte</h4>
        <div class="grid pr-5 pl-5 mb-8 patliste-liste rkish-table no-page-break">
          <div class="row header-row fg-light" style="background-color: #024ea4;">
            <div class="cell text-center"></div>
            <div class="cell text-center"><b>&Sigma;</b></div>
            <div class="cell text-center"><b>KBF</b></div>
            <div class="cell text-center"><b>Notfall K</b></div>
            <div class="cell text-center"><b>Notfall 01</b></div>
            <div class="cell text-center"><b>Notfall 11</b></div>
          </div>
          <div class="row data-row">
            <div class="cell text-center vert-center"><b>Gesamt</b></div>
            <?php foreach ($transport_gesamt as $value): ?>
              <div class="cell text-center"><b><?php echo $value; ?></b></div>
            <?php endforeach; ?>
          </div>


          <?php foreach ($nach_transport as $element) :?>
            <div class="row data-row">
              <div class="cell text-center">
                <?php echo $element[0]; ?>
              </div>
              <?php foreach ($element[1] as $value): ?>
                <div class="cell text-center"><?php echo $value; ?></div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>


        <?php include_once '../modules/footer.php'; ?>
      </div>
    </body>
  </html>
