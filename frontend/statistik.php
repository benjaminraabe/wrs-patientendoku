<?php
  include_once '../backend/sessionmanagement.php';

  $accessible_to = array("ADMIN", "TEL"); // Whitelist für Benutzerrollen.

  if (!in_array($_SESSION["USER_ROLE"], $accessible_to, true)) { // Aktiver strict-mode!
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../backend/db.php';

  // Konvertiert mehrere Zeilen in einen Eindimensionalen assoc Array
  function table_pivot_sk($rows) {
    $sks = array(
      "SOFORT" => 0,
      "SEHR DRINGEND" => 0,
      "DRINGEND" => 0,
      "NORMAL" => 0,
      "NICHT DRINGEND" => 0,
    );

    foreach ($rows as $row) {
      $sks[$row["SICHTUNGSKATEGORIE"]] = $row["ANZAHL"];
    }
    return $sks;
  }

  function table_pivot_transport($rows) {
    $sks = array(
      "KBF" => 0,
      "Notfall K" => 0,
      "Notfall 01" => 0,
      "Notfall 11" => 0,
      // "Platztransport" => 0,
    );

    foreach ($rows as $row) {
      $sks[$row["TRANSPORTKATEGORIE"]] = $row["ANZAHL"];
    }
    return $sks;
  }
?>

<?php
  // Beginnend ab dem ersten Patienten bis zum aktuellen Zeitpunkt werden alle Schichten generiert.
  $first_patient_date = safeQuery($conn, "SELECT DATE(ZEIT_EINGANG) AS DATUM, TIME(ZEIT_EINGANG) AS ZEIT FROM PATIENTEN ORDER BY ZEIT_EINGANG ASC LIMIT 1");
  $schicht_beginn_zeiten = array();
  $schicht = NULL;
  if (count($first_patient_date) > 0) {
    $start_date = $first_patient_date[0]["DATUM"];
    $start_time = $first_patient_date[0]["ZEIT"];

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

    // Generiert alle Schichten zwischen dem ersten Patient und der aktuellen Zeit
    $now = time();
    while ($schichtbeginn < $now) {
      array_push($schicht_beginn_zeiten, $schichtbeginn);
      $schichtbeginn = $schichtbeginn + (60*60*12); # 12 Stunden zur nächsten Schicht
    }
  }
?>

<?php
  // Eintreff-Art der Patienten
  $pat_eintreff = safeQuery($conn, "SELECT
                                    (SELECT COUNT(PATIENTEN_ID) FROM PATIENTEN WHERE EINGANGSART = 0) AS UNBEKANNT,
                                    (SELECT COUNT(PATIENTEN_ID) FROM PATIENTEN WHERE EINGANGSART = 1) AS SELBST,
                                    (SELECT COUNT(PATIENTEN_ID) FROM PATIENTEN WHERE EINGANGSART = 2) AS SAN;");
  $pat_eintreff = $pat_eintreff[0];

 ?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "Statistik";
?>






<!DOCTYPE html>
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
        Selbstständig eingetroffen: <?php echo $pat_eintreff["SELBST"]; ?> | Von W:R:S zugeführt: <?php echo $pat_eintreff["SAN"]; ?> | Unbekannt: <?php echo $pat_eintreff["UNBEKANNT"]; ?>
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
        <!-- Gesamtanzahl Patienten nach Sichtungskategorie -->
        <?php
          $pat_sichtung_ges = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS ANZAHL, SICHTUNGSKATEGORIE
                                                FROM PATIENTEN GROUP BY SICHTUNGSKATEGORIE");
          $pat_sichtung_ges = table_pivot_sk($pat_sichtung_ges);
        ?>
        <div class="row data-row">
          <div class="cell text-center vert-center"><b>Gesamt</b></div>
          <div class="cell text-center"><b><?php echo array_sum($pat_sichtung_ges); ?></b></div>
          <?php foreach ($pat_sichtung_ges as $value): ?>
            <div class="cell text-center"><b><?php echo $value; ?></b></div>
          <?php endforeach; ?>
        </div>


        <?php foreach ($schicht_beginn_zeiten as $s_beginn) :?>
          <?php
            $schichtdaten = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS ANZAHL, SICHTUNGSKATEGORIE
                                              FROM PATIENTEN
                                              WHERE ZEIT_EINGANG >= ? AND ZEIT_EINGANG < ?
                                              GROUP BY SICHTUNGSKATEGORIE",
                                              [date("Y-m-d H:i:s", $s_beginn), date("Y-m-d H:i:s", $s_beginn + (60*60*12))]);
            $schichtdaten = table_pivot_sk($schichtdaten);
           ?>

          <?php if (array_sum($schichtdaten) > 0): ?>
          <div class="row data-row">
            <div class="cell text-center">
              <?php echo date("d.m.Y", $s_beginn); ?> <br>
              <?php echo $schicht; ?>
            </div>
            <div class="cell text-center"><?php echo array_sum($schichtdaten); ?></div>
            <?php foreach ($schichtdaten as $value): ?>
              <div class="cell text-center"><?php echo $value; ?></div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <?php if ($schicht == "Tagschicht") {$schicht = "Nachtschicht";} else {$schicht = "Tagschicht";} ?>
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
        <!-- Gesamtanzahl Patienten nach Sichtungskategorie -->
        <?php
          $pat_sichtung_ges = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS ANZAHL, SICHTUNGSKATEGORIE
                                                FROM PATIENTEN WHERE EINGANGSART = 2
                                                GROUP BY SICHTUNGSKATEGORIE");
          $pat_sichtung_ges = table_pivot_sk($pat_sichtung_ges);
        ?>
        <div class="row data-row">
          <div class="cell text-center vert-center"><b>Gesamt</b></div>
          <div class="cell text-center"><b><?php echo array_sum($pat_sichtung_ges); ?></b></div>
          <?php foreach ($pat_sichtung_ges as $value): ?>
            <div class="cell text-center"><b><?php echo $value; ?></b></div>
          <?php endforeach; ?>
        </div>


        <?php foreach ($schicht_beginn_zeiten as $s_beginn) :?>
          <?php
            $schichtdaten = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS ANZAHL, SICHTUNGSKATEGORIE
                                              FROM PATIENTEN
                                              WHERE ZEIT_EINGANG >= ? AND ZEIT_EINGANG < ?
                                              AND EINGANGSART = 2
                                              GROUP BY SICHTUNGSKATEGORIE",
                                              [date("Y-m-d H:i:s", $s_beginn), date("Y-m-d H:i:s", $s_beginn + (60*60*12))]);
            $schichtdaten = table_pivot_sk($schichtdaten);
           ?>

          <?php if (array_sum($schichtdaten) > 0): ?>
          <div class="row data-row">
            <div class="cell text-center">
              <?php echo date("d.m.Y", $s_beginn); ?> <br>
              <?php echo $schicht; ?>
            </div>
            <div class="cell text-center"><?php echo array_sum($schichtdaten); ?></div>
            <?php foreach ($schichtdaten as $value): ?>
              <div class="cell text-center"><?php echo $value; ?></div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <?php if ($schicht == "Tagschicht") {$schicht = "Nachtschicht";} else {$schicht = "Tagschicht";} ?>
        <?php endforeach; ?>
      </div>



      <!-- Patienten nach Abschnitt
           Initial aufgeführrt: BHP, Wackinger, RKISH, Isolation
      -->
      <h4 class="pr-5 pl-5">Patienten nach Abschnitt</h4>
      <div class="grid pr-5 pl-5 mb-8 patliste-liste rkish-table">
        <div class="row header-row fg-light" style="background-color: #024ea4;">
          <div class="cell text-center"></div>
          <div class="cell text-center"><b>BHP</b></div>
          <div class="cell text-center"><b>Wackinger</b></div>
          <div class="cell text-center"><b>RKISH</b></div>
          <div class="cell text-center"><b>Infektion</b></div>
        </div>
        <?php
          $pat_uhs_gesamt = safeQuery($conn, "SELECT
	           (SELECT COUNT(PATIENTEN_ID) FROM PATIENTEN p
                LEFT JOIN BEREICHE b on p.BEREICH_ID = b.BEREICH_ID WHERE UHST_ID = 1) AS BHP,
	           (SELECT COUNT(PATIENTEN_ID) FROM PATIENTEN p
                LEFT JOIN BEREICHE b on p.BEREICH_ID = b.BEREICH_ID WHERE UHST_ID = 2) AS WACKINGER,
	           (SELECT COUNT(PATIENTEN_ID) FROM PATIENTEN p
                LEFT JOIN BEREICHE b on p.BEREICH_ID = b.BEREICH_ID WHERE UHST_ID = 3) AS RKISH,
	           (SELECT COUNT(PATIENTEN_ID) FROM PATIENTEN p WHERE INFEKT = 1) AS INFEKT;");
         ?>
        <div class="row data-row">
          <div class="cell text-center vert-center"><b>Gesamt</b></div>
          <div class="cell text-center"><b><?php echo $pat_uhs_gesamt[0]["BHP"]; ?></b></div>
          <div class="cell text-center"><b><?php echo $pat_uhs_gesamt[0]["WACKINGER"]; ?></b></div>
          <div class="cell text-center"><b><?php echo $pat_uhs_gesamt[0]["RKISH"]; ?></b></div>
          <div class="cell text-center"><b><?php echo $pat_uhs_gesamt[0]["INFEKT"]; ?></b></div>
        </div>
        <!-- Patienten nach Abschnitten nach Schichten abfragen -->
        <?php foreach ($schicht_beginn_zeiten as $s_beginn) :?>
          <?php
            $schichtdaten = safeQuery($conn, "SELECT
	             (SELECT COUNT(PATIENTEN_ID) FROM PATIENTEN p
                  LEFT JOIN BEREICHE b on p.BEREICH_ID = b.BEREICH_ID
                  WHERE UHST_ID = 1 AND ZEIT_EINGANG >= ? AND ZEIT_EINGANG < ?) AS BHP,
	             (SELECT COUNT(PATIENTEN_ID) FROM PATIENTEN p
                  LEFT JOIN BEREICHE b on p.BEREICH_ID = b.BEREICH_ID
                  WHERE UHST_ID = 2 AND ZEIT_EINGANG >= ? AND ZEIT_EINGANG < ?) AS WACKINGER,
	             (SELECT COUNT(PATIENTEN_ID) FROM PATIENTEN p
                  LEFT JOIN BEREICHE b on p.BEREICH_ID = b.BEREICH_ID
                  WHERE UHST_ID = 3 AND ZEIT_EINGANG >= ? AND ZEIT_EINGANG < ?) AS RKISH,
	             (SELECT COUNT(PATIENTEN_ID) FROM PATIENTEN p
                  WHERE INFEKT = 1 AND ZEIT_EINGANG >= ? AND ZEIT_EINGANG < ?) AS INFEKT;",
                [date("Y-m-d H:i:s", $s_beginn), date("Y-m-d H:i:s", $s_beginn + (60*60*12)),
                 date("Y-m-d H:i:s", $s_beginn), date("Y-m-d H:i:s", $s_beginn + (60*60*12)),
                 date("Y-m-d H:i:s", $s_beginn), date("Y-m-d H:i:s", $s_beginn + (60*60*12)),
                 date("Y-m-d H:i:s", $s_beginn), date("Y-m-d H:i:s", $s_beginn + (60*60*12))]);
            $schichtdaten = $schichtdaten[0];
           ?>

          <?php if (array_sum($schichtdaten) > 0): ?>
          <div class="row data-row">
            <div class="cell text-center">
              <?php echo date("d.m.Y", $s_beginn); ?> <br>
              <?php echo $schicht; ?>
            </div>
            <div class="cell text-center"><?php echo $schichtdaten["BHP"]; ?></div>
            <div class="cell text-center"><?php echo $schichtdaten["WACKINGER"]; ?></div>
            <div class="cell text-center"><?php echo $schichtdaten["RKISH"]; ?></div>
            <div class="cell text-center"><?php echo $schichtdaten["INFEKT"]; ?></div>
          </div>
          <?php endif; ?>
          <?php if ($schicht == "Tagschicht") {$schicht = "Nachtschicht";} else {$schicht = "Tagschicht";} ?>
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
          <!-- <div class="cell text-center"><b>Platztransport</b></div> -->
        </div>
        <!-- Gesamtanzahl Patienten nach Sichtungskategorie -->
        <?php
          $transport_ges = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS ANZAHL, TRANSPORTKATEGORIE
                                                FROM PATIENTEN
                                                WHERE TRANSPORTKATEGORIE IS NOT NULL
                                                AND TRANSPORTKATEGORIE != 'Platztransport'
                                                AND TRANSPORT_RUFNAME IS NOT NULL
                                                AND ZEIT_ENTLASSUNG IS NOT NULL
                                                GROUP BY TRANSPORTKATEGORIE;");
          // $transport_ges = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS ANZAHL, TRANSPORTKATEGORIE
          //                                       FROM PATIENTEN
          //                                       WHERE TRANSPORTKATEGORIE IS NOT NULL AND TRANSPORT_RUFNAME IS NOT NULL
          //                                       AND ZEIT_ENTLASSUNG IS NOT NULL
          //                                       GROUP BY TRANSPORTKATEGORIE;");
          $transport_ges = table_pivot_transport($transport_ges);
        ?>
        <div class="row data-row">
          <div class="cell text-center vert-center"><b>Gesamt</b></div>
          <div class="cell text-center"><b><?php echo array_sum($transport_ges); ?></b></div>
          <?php foreach ($transport_ges as $value): ?>
            <div class="cell text-center"><b><?php echo $value; ?></b></div>
          <?php endforeach; ?>
        </div>


        <?php foreach ($schicht_beginn_zeiten as $s_beginn) :?>
          <?php
            $schichtdaten = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS ANZAHL, TRANSPORTKATEGORIE
                                              FROM PATIENTEN
                                              WHERE ZEIT_ENTLASSUNG >= ? AND ZEIT_ENTLASSUNG < ?
                                              AND TRANSPORTKATEGORIE IS NOT NULL AND TRANSPORT_RUFNAME IS NOT NULL
                                              GROUP BY TRANSPORTKATEGORIE",
                                              [date("Y-m-d H:i:s", $s_beginn), date("Y-m-d H:i:s", $s_beginn + (60*60*12))]);
            $schichtdaten = table_pivot_transport($schichtdaten);
           ?>

          <?php if (array_sum($schichtdaten) > 0): ?>
          <div class="row data-row">
            <div class="cell text-center">
              <?php echo date("d.m.Y", $s_beginn); ?> <br>
              <?php echo $schicht; ?>
            </div>
            <div class="cell text-center"><?php echo array_sum($schichtdaten); ?></div>
            <?php foreach ($schichtdaten as $value): ?>
              <div class="cell text-center"><?php echo $value; ?></div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <?php if ($schicht == "Tagschicht") {$schicht = "Nachtschicht";} else {$schicht = "Tagschicht";} ?>
        <?php endforeach; ?>
      </div>

      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
