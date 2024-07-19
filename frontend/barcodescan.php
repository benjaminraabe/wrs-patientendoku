<?php
  // Implementiert das Auslesen eines gegebenen Barcodes mit einer Patienten-Kennung
  //    mit der zXing-Library.
  // Ärzte und Sichter werden dann an unterschiedliche Seiten weitergeleitet.


  include_once '../backend/sessionmanagement.php';

  if (!in_array("PERM_READ_PATIENTS", $_SESSION["PERMISSIONS"], true) &&
      !in_array("PERM_ARZTVISITE", $_SESSION["PERMISSIONS"], true)) {
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "Barcode Scannen";
?>



<!DOCTYPE html>
<html lang="de" dir="ltr">
  <head>
    <title> <?php echo $PAGE_TITLE . " | " . $ORG_NAME; ?> </title>
    <meta charset="utf-8">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="../styles/metro-all.min.css">
    <script src="../scripts/metro.min.js" charset="utf-8"></script>
    <script src="../scripts/zxing.min.js" charset="utf-8"></script>
    <link rel="stylesheet" href="../styles/page.css">
    <link rel="stylesheet" href="../styles/barcodescan.css">
  </head>
  <body>
    <div class="page-wrapper">
      <?php include_once '../modules/header.php'; ?>

      <div class="barcode-scan-wrapper">
        <div class="videowrapper">
          <video id="video"></video>
        </div>

        <div id="sourceSelectPanel" style="display:none">
          <label for="sourceSelect">Videoeingang:</label>
          <select id="sourceSelect" style="max-width:400px">
          </select>
        </div>

        <div class="scan-result text-center mt-5">
            <!-- Arzt-Visite und Nicht-Arzt werden zu unterschiedlichen Seiten geleitet. -->
            <?php if(isset($_GET["arzt"])): ?>
              <form id="codescan-form" action="patient_arztseite.php" method="get">
            <?php else: ?>
              <form id="codescan-form" action="patient.php" method="get">
            <?php endif; ?>

            <input id="barcodeScanResult" data-role="input" type="text" name="id" value="" placeholder="Patientennummer" onkeyup="toggleFormSend(this);" autofocus>
            <button class="button primary mt-4" id="codescan-submit" type="submit" disabled>Bestätigen</button>
          </form>
        </div>

        <div class="scan-error">
          <span id="barcodeScanError"></span>
        </div>
      </div>

      <script src="../scripts/barcodescan.js" charset="utf-8"></script>

      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
