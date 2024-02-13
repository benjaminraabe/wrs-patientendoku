<?php
  // Abgesetzte Arztseite für einen Patienten mit einer gegebenen Kennung.
  // Personalien und Vitalwerte können nicht gelesen oder verändert werden,
  //    stattdessen kann der Arzt eine Visite zum aktuellen Zeitpunkt dokumentieren.

  
  include_once '../backend/sessionmanagement.php';

  $accessible_to = array("ARZT"); // Whitelist für Benutzerrollen

  if (!in_array($_SESSION["USER_ROLE"], $accessible_to, true)) { // Aktiver strict-mode!
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include '../backend/db.php';

  $pat_id = $_GET["id"];
  $error = false;
  $error_msg = "";

  $patientendaten = safeQuery($conn, "SELECT ZEIT_EINGANG FROM PATIENTEN
                                      WHERE PATIENTEN_ID = ?",
                             [$pat_id]);

  # Patient existiert noch nicht. Eine Arztvisite ist nicht möglich.
  if (count($patientendaten) == 0) {
    $error = true;
    $error_msg = "Der Patient wurde nicht gefunden. Möglicherweise ist er noch nicht gesichtet worden?";
  }
  if(count($patientendaten) > 0) {
    // Arztvisite eintragen
    $patientendaten = $patientendaten[0];

    $timediff = floor((strtotime(date("Y-m-d H:i:s")) - strtotime(($patientendaten["ZEIT_EINGANG"]))) / 60);
    safeExecute($conn, "INSERT INTO PATIENTENVERLAUF(PATIENTEN_ID, USERNAME, EINTRAG)
                        VALUES(?, ?, ?)",
               [$pat_id, $_SESSION['USER_ID'], "Arztvisite nach ".$timediff." Minuten Wartezeit."]);
  }
?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "Patient #".str_pad($pat_id, 5, "0", STR_PAD_LEFT);
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
    <div class="page-wrapper">
      <?php include_once '../modules/header.php'; ?>

      <!-- Erfolg-Fall -->
      <?php if(!$error) : ?>
        <div class="pl-5 mb-5 text-center">
          <h4>Arztvisite eingetragen</h4>
          <p>
            Wartezeit: <?php echo $timediff; ?> Minuten
          </p>

          <button type="button" class="button success mt-5 w-50" name="button" onclick="history.back();">Zurück</button>
        </div>
      <?php endif; ?>

      <!-- Fehler-Fall -->
      <?php if($error) : ?>
        <div class="pl-5 mb-5 text-center">
          <h4>Fehler</h4>
          <p>
            <?php echo $error_msg; ?>
          </p>

          <button type="button" class="button alert mt-5 w-50" name="button" onclick="history.back();">Zurück</button>
        </div>
      <?php endif; ?>

      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
