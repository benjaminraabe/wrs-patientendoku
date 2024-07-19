<?php
  // Bietet eine Maske um einen aktiven oder bereits entlassenen Patienten nach
  //    gewissen Parametern zu suchen. (Kennung, Name, Zeitraum, ...).
  // Die Daten von bereits entlassenen Patienten können nur mit besonderer
  //    Berechtigung verändert werden - und die Nachträge werden gesondert
  //    im Patientenverlauf vermerkt.


  include_once '../backend/sessionmanagement.php';

  if (!in_array("PERM_SEARCH_PATIENTS", $_SESSION["PERMISSIONS"], true)) {
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../backend/db.php';

  // Datenlage wird ggf. nach UHS eingeschränkt.
  $patienten = safeQuery($conn, "SELECT
                              	PATIENTEN_ID AS ID,
                              	p.NAME,
                              	p.VORNAME,
                              	DATE_FORMAT(DOB, '%d.%m.%y') AS DOB,
                              	DATE_FORMAT(ZEIT_EINGANG, '%d.%m.%y') AS EIN_DATUM,
                              	DATE_FORMAT(ZEIT_EINGANG, '%H:%i Uhr') AS EIN_ZEIT,
                              	COALESCE(DATE_FORMAT(ZEIT_ENTLASSUNG, '%d.%m.%y'), ' ') AS AUS_DATUM,
                              	COALESCE(DATE_FORMAT(ZEIT_ENTLASSUNG, '%H:%i Uhr'), ' ') AS AUS_ZEIT,
                              	IF (ZEIT_ENTLASSUNG IS NULL, ' ', IF (TRANSPORT_RUFNAME IS NOT NULL, COALESCE(KLINIK_NAME, 'Unbekannt'), 'Festival')) AS ENTLASSUNG
                              FROM PATIENTEN p
                              LEFT JOIN KLINIKEN k ON k.KLINIK_ID = p.ZIELKLINIK
                              LEFT JOIN BEREICHE b ON p.BEREICH_ID = b.BEREICH_ID
                              WHERE ((b.UHST_ID = ? OR NULL <=> ?) OR p.BEREICH_ID IS NULL)
                              ORDER BY ID ASC", [$_SESSION['UHS'], $_SESSION['UHS']]);
 ?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "Patientensuche";
?>






<!DOCTYPE html>
<html lang="de" dir="ltr">
  <head>
    <title> <?php echo $PAGE_TITLE . " | " . $ORG_NAME; ?> </title>
    <meta charset="utf-8">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="../styles/metro-all.min.css">
    <script src="../scripts/metro.min.js" charset="utf-8"></script>
    <script src="../scripts/patientensuche.js" charset="utf-8"></script>
    <link rel="stylesheet" href="../styles/page.css">
    <link rel="stylesheet" href="../styles/patliste.css">
  </head>
  <body>
    <div class="page-wrapper">
      <?php include_once '../modules/header.php'; ?>

      <div class="grid pr-2 pl-2 patliste-liste">
        <div class="row header-row fg-light" style="background-color: #024ea4;">
          <div class="cell-2 text-center"><b>#</b></div>
          <div class="cell"><b>Name</b></div>
          <div class="cell"><b>Geb. Datum</b></div>
          <div class="cell"><b>Eingang</b></div>
          <div class="cell"><b>Ausgang</b></div>
          <div class="cell"><b>Entlassen</b></div>
        </div>

        <div class="row header-row fg-light" style="">
          <div class="cell-2"><input type="text" onkeyup="suche()" id="iID" data-role="input" data-clear-button="false"></div>
          <div class="cell"><input type="text" onkeyup="suche()" id="iName" data-role="input" data-clear-button="false"></div>
          <div class="cell"><input type="date" onkeyup="suche()" id="iDOB" data-role="input" data-clear-button="false"></div>
          <div class="cell"><input type="date" onkeyup="suche()" id="iEingang" data-role="input" data-clear-button="false"></div>
          <div class="cell"><input type="date" onkeyup="suche()" id="iAusgang" data-role="input" data-clear-button="false"></div>
          <div class="cell"><button type="button" class="button secondary" onclick="suche_reset()">Filter zurücksetzen</button></div>
        </div>

        <!-- Wenn noch kein Patient vorhanden ist, wird dies gemeldet. -->
        <?php if (count($patienten) == 0) : ?>
        <div class="row text-center fg-light" style="background-color: #999999;">
          <div class="cell p-0 pb-1">Bisher keine Patienten.</div>
        </div>
        <?php endif; ?>

        <!-- Patienten werden nach ihrer Nummer sortiert aufgelistet -->
        <?php if (count($patienten) > 0) : ?>
          <?php foreach ($patienten as $pat) :?>

            <div class="row data-row pt-2 pb-2" onclick="window.location.href = 'patient.php?id=<?php echo $pat["ID"] ?>'">
              <div class="cell-2 text-center" style="line-height: 3em;"><?php echo $pat["ID"]; ?></div>
              <div class="cell-2" lang="de">
                <?php echo $pat["VORNAME"]; ?> <br>
                <?php echo $pat["NAME"]; ?>
              </div>
              <div class="cell-2"><?php echo $pat["DOB"]; ?></div>
              <div class="cell-2">
                <?php echo $pat["EIN_DATUM"]; ?><br>
                <?php echo $pat["EIN_ZEIT"]; ?>
              </div>
              <div class="cell-2">
                <?php echo $pat["AUS_DATUM"]; ?><br>
                <?php echo $pat["AUS_ZEIT"]; ?>
              </div>
              <div class="cell-2" style="line-height: 3em;"><?php echo $pat["ENTLASSUNG"]; ?></div>
            </div>
          <?php endforeach ?>
        <?php endif; ?>
      </div>


      <?php include_once '../modules/footer.php'; ?>
    </div>

  </body>
</html>
