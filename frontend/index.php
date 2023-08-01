<?php
  include_once '../backend/sessionmanagement.php';

  $accessible_to = array("ADMIN", "TEL", "SICHTER", "ARZT"); // Whitelist für Benutzerrollen

  if (!in_array($_SESSION["USER_ROLE"], $accessible_to, true)) { // Aktiver strict-mode!
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "Patientenverwaltung";
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
  </head>
  <body>
    <div class="page-wrapper wrs-background-image">
      <?php include_once '../modules/header.php'; ?>

      <!-- Code scannen -->
      <div class="pl-5 mb-5">
        <h4>Patientenverwaltung</h4>
        <a class="shortcut primary" href="barcodescan.php">
          <span class="caption">Scannen</span>
          <span class="mif-barcode icon"></span>
        </a>
        <?php if (in_array($_SESSION["USER_ROLE"], array("ADMIN", "SICHTER", "TEL"), true)): ?>
        <a class="shortcut primary outline" href="patientenliste.php">
          <span class="caption">Pat. Liste</span>
          <span class="mif-list-numbered icon"></span>
        </a>
        <?php endif; ?>
      </div>

      <!-- Öffentliche Tools -->
      <div class="pl-5 mb-5 pt-3">
        <h4>Statistiken</h4>
        <a class="shortcut primary outline" href="monitor.php">
          <span class="caption">Monitor</span>
          <span class="mif-chart-bars icon"></span>
        </a>
        <!-- Private Tools -->
        <?php if (in_array($_SESSION["USER_ROLE"], array("ADMIN", "TEL"), true)): ?>
          <a class="shortcut primary outline" href="statistik.php">
            <span class="caption">Statistik</span>
            <span class="mif-document-file-pdf icon"></span>
          </a>
          <a class="shortcut primary outline" href="transportstatistik.php">
            <span class="caption">Transporte</span>
            <span class="mif-ambulance icon"></span>
          </a>
          <a class="shortcut primary outline" href="offeneTransporte.php">
            <span class="caption">Anforderung</span>
            <span class="mif-chat icon"></span>
          </a>
        <?php endif; ?>
      </div>




        <div class="pl-5 mb-5 pt-3">
          <h4>Werkzeuge</h4>
          <?php if ($_SESSION["CAN_SEARCH_PATIENTS"] == 1): ?>
          <a class="shortcut alert outline" href="patientensuche.php">
            <span class="caption">Pat. Suche</span>
            <span class="mif-search icon"></span>
          </a>
          <?php endif; ?>
          <!-- Nachtragezugang -->
          <!-- Zugriff haben Sichter und Admin mit Sonderberechtigung. TEL ist ausgeschlossen, da diese nur Leseberechtigungen
                  am Patienten haben und das ein ganz unglückliches Durcheinander gibt. -->
          <?php if (in_array($_SESSION["USER_ROLE"], array("SICHTER", "ADMIN"), true)): ?>
            <?php if ($_SESSION["CAN_BACKDATE_PROTOCOL"] == 1): ?>
              <a class="shortcut alert outline" href="nachtrageseite.php">
                <span class="caption">Nachtragen</span>
                <span class="mif-alarm icon"></span>
              </a>
            <?php endif; ?>
          <?php endif; ?>
          <!-- Admin-Werkzeuge -->
          <?php if (in_array($_SESSION["USER_ROLE"], array("ADMIN"), true)): ?>
            <a class="shortcut alert outline"  href="adminpage.php">
              <span class="caption">Admin</span>
              <span class="mif-wrench icon"></span>
            </a>
          <?php endif; ?>
        </div>

      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
