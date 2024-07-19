<?php
  // Implementiert die "kleine" Statistik über durchgeführte Transporte.
  // Einzelne Transporte werden explizit aufgelistet, damit sie später
  //    z.B. für Abrechnungszwecke verwendet werden können.


  include_once '../backend/sessionmanagement.php';

  if (!in_array("PERM_TRANSPORT_STATISTICS", $_SESSION["PERMISSIONS"], true)) {
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../backend/db.php';

  $transporte = safeQuery($conn, "SELECT
                                  	DATE_FORMAT(ZEIT_ENTLASSUNG, '%a. %d.%m.%y') AS DATUM,
                                  	DATE_FORMAT(ZEIT_ENTLASSUNG, '%H:%i') AS ZEIT,
                                  	TRANSPORTKATEGORIE,
                                  	TRANSPORT_RUFNAME AS RUFNAME,
                                  	COALESCE(KLINIK_NAME, 'Unbekannt') AS ZIEL
                                  FROM PATIENTEN p
                                  LEFT JOIN KLINIKEN k ON k.KLINIK_ID = p.ZIELKLINIK
                                  WHERE TRANSPORT_RUFNAME IS NOT NULL
                                  ORDER BY ZEIT_ENTLASSUNG ASC");
 ?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "Transportstatistik";
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

      <!-- <div class="grid mb-5 pl-5 pr-5">
        <div class="row no-print">
          <div class="cell-3"><input type="date" data-prepend="Von:" data-clear-button="false" data-role="input" value=""></div>
          <div class="cell-3"><input type="date" data-prepend="Bis:" data-clear-button="false" data-role="input" value=""></div>
          <div class="cell-2"><button type="button" class="button">Filtern</button></div>
          <div class="cell"><button type="button" class="button place-right outline" onclick="print();">Drucken</button></div>
        </div>
        <div class="row no-screen">
          <div class="cell-5 bg-light text-center">Beschränkt auf den Zeitraum X - Y.</div>
        </div>
      </div> -->

      <div class="grid pr-5 pl-5 patliste-liste">
        <div class="row header-row fg-light" style="background-color: #024ea4;">
          <div class="cell-2 text-center"><b>#</b></div>
          <div class="cell"><b>Zeit</b></div>
          <div class="cell"><b>Rufname</b></div>
          <div class="cell"><b>Ziel</b></div>
        </div>

        <!-- Wenn noch kein Transport vorhanden ist, wird dies gemeldet. -->
        <?php if (count($transporte) == 0) : ?>
        <div class="row divider-row text-center fg-light" style="background-color: #999999;">
          <div class="cell p-0 pb-1">Bisher keine Transporte.</div>
        </div>
        <?php endif; ?>

        <!-- Trasporte werden Tagesweise nach dem Datum getrennt -->
        <?php if (count($transporte) > 0) : ?>
          <?php $lastdate = ""; ?>
          <?php foreach ($transporte as $nbr => $transport) :?>
            <!-- Ggf. Sektionstrenner schreiben -->
            <?php if ($lastdate != $transport["DATUM"]) : ?>
              <?php $lastdate = $transport["DATUM"]; ?>
              <div class="row divider-row text-center fg-light" style="background-color: #999999;">
                <div class="cell-2 p-0 pb-1"><?php echo $transport["DATUM"]; ?></div>
              </div>
            <?php endif; ?>

            <div class="row data-row pt-2 pb-2">
              <div class="cell-2 text-center" style="line-height: 1em;"><?php echo ($nbr+1); ?></div>
              <div class="cell"><?php echo $transport["ZEIT"]; ?></div>
              <div class="cell"><?php echo $transport["RUFNAME"]; ?></div>
              <div class="cell"><?php echo $transport["ZIEL"]; ?></div>
            </div>
          <?php endforeach ?>
        <?php endif; ?>
      </div>


      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
