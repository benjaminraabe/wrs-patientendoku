<?php
  // Startseite für eingeloggte Benutzer, "Monitor"-Benutzer werden direkt auf
  //    die Monitor-Seite weitergeleitet.
  // Es werden nur Funktionen eingeblendet, auf die der Benutzer zugreifen darf.


  include_once '../backend/sessionmanagement.php';

  if (count($_SESSION["PERMISSIONS"]) < 1) {
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
        <?php if (in_array("PERM_READ_PATIENTS", $_SESSION["PERMISSIONS"], true)): ?>
          <a class="shortcut primary" href="barcodescan.php">
            <span class="caption">Scannen</span>
            <span class="mif-barcode icon"></span>
          </a>
        <?php endif; ?>
        <?php if (in_array("PERM_ARZTVISITE", $_SESSION["PERMISSIONS"], true)): ?>
          <a class="shortcut primary" href="barcodescan.php?arzt">
            <span class="caption">Arztvisite</span>
            <span class="mif-stethoscope icon"></span>
          </a>
        <?php endif; ?>
        <?php if (in_array("PERM_LIST_PATIENTS", $_SESSION["PERMISSIONS"], true)): ?>
          <a class="shortcut primary outline" href="patientenliste.php">
            <span class="caption">Pat. Liste</span>
            <span class="mif-list-numbered icon"></span>
          </a>
        <?php endif; ?>
      </div>

      <!-- Öffentliche Tools -->
      <div class="pl-5 mb-5 pt-3">
        <h4>Statistiken</h4>
        <?php if (in_array("PERM_PUBLIC_MONITOR", $_SESSION["PERMISSIONS"], true)): ?>
          <a class="shortcut primary outline" href="monitor.php">
            <span class="caption">Monitor</span>
            <span class="mif-chart-bars icon"></span>
          </a>
        <?php endif; ?>

        <!-- Private Tools -->
        <?php if (in_array("PERM_GENERAL_STATISTICS", $_SESSION["PERMISSIONS"], true)): ?>
          <a class="shortcut primary outline" href="statistik.php">
            <span class="caption">Statistik</span>
            <span class="mif-document-file-pdf icon"></span>
          </a>
        <?php endif; ?>
        <?php if (in_array("PERM_TRANSPORT_STATISTICS", $_SESSION["PERMISSIONS"], true)): ?>
          <a class="shortcut primary outline" href="transportstatistik.php">
            <span class="caption">Transporte</span>
            <span class="mif-ambulance icon"></span>
          </a>
        <?php endif; ?>
        <?php if (in_array("PERM_OPEN_TRANSPORT_MONITOR", $_SESSION["PERMISSIONS"], true)): ?>
          <a class="shortcut primary outline" href="offeneTransporte.php?lastQuittance=-1">
            <span class="caption">Anforderung</span>
            <span class="mif-chat icon"></span>
          </a>
        <?php endif; ?>
      </div>




      <div class="pl-5 mb-5 pt-3">
        <h4>Werkzeuge</h4>
        <?php if (in_array("PERM_SEARCH_PATIENTS", $_SESSION["PERMISSIONS"], true)): ?>
          <a class="shortcut alert outline" href="patientensuche.php">
            <span class="caption">Pat. Suche</span>
            <span class="mif-search icon"></span>
          </a>
        <?php endif; ?>
        <!-- Nachtragezugang -->
        <!-- Berechtigungen zum Schreiben der Patienten und zum Nachtragen müssen vergeben sein, sonst entsteht Unsinn. -->
        <?php if (in_array("PERM_LATE_ENTER_PATIENTS", $_SESSION["PERMISSIONS"], true)): ?>
          <?php if (in_array("PERM_WRITE_PATIENTS", $_SESSION["PERMISSIONS"], true)): ?>
            <a class="shortcut alert outline" href="nachtrageseite.php">
              <span class="caption">Nachtragen</span>
              <span class="mif-alarm icon"></span>
            </a>
          <?php endif; ?>
        <?php endif; ?>
        <!-- Admin-Werkzeuge -->
        <?php if (in_array("PERM_USER_ADMINISTRATION", $_SESSION["PERMISSIONS"], true)): ?>
          <a class="shortcut alert outline"  href="adminpage.php">
            <span class="caption">Admin</span>
            <span class="mif-wrench icon"></span>
          </a>
        <?php endif; ?>
        <?php if (in_array("PERM_PERMISSION_ADMINISTRATION", $_SESSION["PERMISSIONS"], true)): ?>
          <a class="shortcut alert outline"  href="permissionpage.php">
            <span class="caption">Rechte</span>
            <span class="mif-database icon"></span>
          </a>
        <?php endif; ?>
      </div>

      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
