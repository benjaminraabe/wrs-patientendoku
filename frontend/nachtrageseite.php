<?php
  // Spezielle Eingabemaske um Patienten nachzutragen. Erlaubt es eine bestimmte
  //    Eingangszeit für den Patienten zu vergeben.
  // Der Zugriff auf diese Seite muss explizit für einen Benutzer freigegeben werden.


  include_once '../backend/sessionmanagement.php';

  if (!in_array("PERM_LATE_ENTER_PATIENTS", $_SESSION["PERMISSIONS"], true) &&
      !in_array("PERM_WRITE_PATIENTS", $_SESSION["PERMISSIONS"], true) &&
      !in_array("PERM_READ_PATIENTS", $_SESSION["PERMISSIONS"], true)) {
    echo "Zugriff verweigert. Es fehlt die Berechtigung zum Nachtragen und/oder der Lese/Schreibzugriff.";
    exit();
  }
?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "Protokoll nachtragen";
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

      <form action="patient.php" method="get">
        <div class="grid p-5">
          <div class="row">
            <div class="cell">Protokollnummer:</div>
            <div class="cell">
              <input type="text" name="nachtrPatID" data-role="input" data-clear-button="false" autofocus required>
            </div>
          </div>
          <div class="row">
            <div class="cell">
              <div class="grid">
                <div class="row">
                  <div class="cell">
                    Eingang:
                  </div>
                </div>
              </div>
            </div>
            <div class="cell">
              <div class="grid">
                <div class="row">
                  <div class="cell">
                    <input type="date" name="nachtrEinDatum" data-role="input" data-clear-button="false" required>
                  </div>
                  <div class="cell">
                    <input type="time" name="nachtrEinZeit" data-role="input" data-clear-button="false" required>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- <div class="row">
            <div class="cell">Ausgang:</div>
            <div class="cell">
              <div class="grid m-0 p-0">
                <div class="row">
                  <div class="cell">
                    <input type="date" name="nachtrAusDatum" data-role="input" data-clear-button="false">
                  </div>
                  <div class="cell">
                    <input type="time" name="nachtrAusZeit" data-role="input" data-clear-button="false">
                  </div>
                </div>
              </div>
            </div>
          </div> -->
          <div class="row">
            <div class="cell"></div>
            <div class="cell">
              <button type="submit" class="button success w-100">Anlegen</button>
            </div>
          </div>
        </div>

      </form>


      <?php include_once '../modules/footer.php'; ?>
    </div>

  </body>
</html>
