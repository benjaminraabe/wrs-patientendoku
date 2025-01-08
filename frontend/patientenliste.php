<?php
  // Diese Seite implementiert eine Übersicht über alle aktiven (!) Patienten
  //    die in der ausgewählten UHS aktuell behandelt werden.
  // Zusätzlich werden Daten wie Behandlungszeit und Zeit bis zur Arztvisite vermerkt.
  // Wird ein Patient entlassen, entfällt er aus dieser Ansicht.


  include_once '../backend/sessionmanagement.php';

  if (!in_array("PERM_LIST_PATIENTS", $_SESSION["PERMISSIONS"], true)) {
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../backend/db.php';
  include_once '../backend/utils.php';
  include_once '../backend/util_patientenverlauf.php';

  $patienten = safeQuery($conn, "SELECT p.SICHTUNGSKATEGORIE, p.ZEIT_EINGANG,  p.PATIENTEN_ID, p.PZC, pzc.DESCRIPTION, b.NAME, a.ARZTVISITE FROM PATIENTEN as p
                                  LEFT JOIN BEREICHE AS b
                                    on p.BEREICH_ID = b.BEREICH_ID
                                  LEFT JOIN PZC AS pzc
                                    on pzc.PZC = p.PZC
                                  LEFT JOIN (SELECT PATIENTEN_ID, TIMESTAMP AS ARZTVISITE
                                            FROM PATIENTENVERLAUF v
                                        		WHERE ART = ".ENTRY_TYPE_ARZTVISITE."
                                        		GROUP BY PATIENTEN_ID) AS a
                              	    on a.PATIENTEN_ID = p.PATIENTEN_ID
                                  WHERE ((b.UHST_ID = ? OR NULL <=> ?) OR p.BEREICH_ID IS NULL) AND p.Aktiv = 1
                                  ORDER BY p.BEREICH_ID ASC, p.PATIENTEN_ID ASC;",
                        [$_SESSION['UHS'], $_SESSION['UHS']]);

  $showFirstAbs = true;
  if (count($patienten) == 0) {
    $showFirstAbs = false;
  } else {
    if (!is_null($patienten[0]["NAME"])) {
      $showFirstAbs = false;
    }
  }
 ?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "Patientenliste";
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

      <!-- Legende und Patientenzähler. -->
      <div class="grid pr-5 pb-2 pl-5">
        <div class="row">
          <div class="cell text-left">
            <?php echo count($patienten) ?> Patient<?php if(count($patienten) != 1): ?>en <?php endif ?> in Behandlung
          </div>
          <div class="cell text-right">
            <span class="mif-enter"></span> Eingangszeit
            <span class="mif-stethoscope pl-2"></span> Arztvisite
            <span class="mif-watch pl-2"></span> Behandlungszeit
          </div>
        </div>
      </div>


      <div class="grid pl-5 pr-5 patliste-liste">
        <div class="row header-row text-center fg-light" style="background-color: #024ea4;">
          <div class="cell-2"><b>#</b></div>
          <div class="cell"><b>Informationen</b></div>
          <div class="cell"><b>Zeitraum</b></div>
        </div>

        <!-- Wenn noch kein Patient vorhanden ist, wird dies gemeldet. -->
        <?php if (count($patienten) == 0) : ?>
        <div class="row divider-row text-center fg-light" style="background-color: #999999;">
          <div class="cell p-0 pb-1">Aktuell keine Patienten.</div>
        </div>
        <?php endif; ?>

        <!-- Überschrift "Ohne Abschnitt" wird nur angezeigt, wenn dort auch ein Patient ist. -->
        <?php if ($showFirstAbs) : ?>
        <div class="row divider-row text-center fg-light" style="background-color: #999999;">
          <div class="cell-2 p-0 pb-1">Ohne Bereich</div>
        </div>
        <?php endif; ?>

        <?php $lastcategory = ""; ?>
        <?php foreach ($patienten as $patient) :?>
          <!-- Ggf. Sektionstrenner schreiben -->
          <?php if ($lastcategory != $patient["NAME"]) : ?>
            <?php $lastcategory = $patient["NAME"]; ?>
            <div class="row divider-row text-center fg-light" style="background-color: #999999;">
              <div class="cell-2 p-0 pb-1"><?php echo $patient["NAME"]; ?></div>
            </div>
          <?php endif; ?>
          <div class="row data-row pt-2 pb-2" onclick="window.location.href = 'patient.php?id=<?php echo $patient["PATIENTEN_ID"] ?>'">
            <div class="cell-2 text-center" style="line-height: 3em;">
              <?php echo $patient["PATIENTEN_ID"] ?>
            </div>
            <div class="cell">
              <?php echo $patient["SICHTUNGSKATEGORIE"] ?> <br>
              <?php echo $patient["PZC"] ?> - <?php echo $patient["DESCRIPTION"] ?>
            </div>
            <div class="cell">
              <span class="mif-enter"></span>               <?php echo timeToClock($patient["ZEIT_EINGANG"]) ?>
              <span class="mif-stethoscope ml-10"></span>   <?php echo timeToClock($patient["ARZTVISITE"]) ?> <br>
              <span class="mif-watch"></span>               <?php echo secondsToStr(time() - strtotime($patient["ZEIT_EINGANG"]))?>
            </div>
          </div>
        <?php endforeach ?>

      </div>


      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
