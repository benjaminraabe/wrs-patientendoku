<?php
  // Allgemeine Login-Maske, Startseite für alle nicht-eingeloggten Benutzer.
  // Aktive Benutzer können gewählt werden, zusätzlich können die verfügbaren
  //    Daten noch zusätzlich eingeschränkt werden.

  include_once '../config.php';
  $PAGE_TITLE = "Login";
?>

<?php
  # Vorhandene Nutzer und UHSen anzeigen.
  include_once '../backend/db.php';
  $users = safeQuery($conn, "SELECT USERNAME, USER_ROLE FROM USER WHERE ACTIVE = 1 ORDER BY USERNAME;", []);
  $uhs = safeQuery($conn, "SELECT * FROM UHS_DEFINITION ORDER BY NAME;", []);
?>

<?php
  # Mögliche Fehler aus dem Login parsen und anzeigen.
  $errmsg = '';
  if (isset($_GET["errmsg"])) {
    $err_code = $_GET["errmsg"];
    switch ($err_code) {
      case 'invalid':
        $errmsg = "Fehler: Der Nutzername ist nicht gültig.";
        break;
      case 'dbfehler':
        $errmsg = "Bei der Datenbankabfrage ist ein Fehler aufgetreten. Bitte wenden Sie sich an den Support.";
        break;
      case 'pwwrong':
        $errmsg = "Fehler: Das Passwort ist nicht gültig.";
        break;
      default:
        $errmsg = "Ein unbekannter Fehler ist aufgetreten. Bitte wenden Sie sich an den Support.";
        break;
    }
  }
?>





<!DOCTYPE html>
<html lang="en" dir="ltr">
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
      <div class="login-wrapper">
        <form class="" action="../backend/process_login.php" method="post">

          <div class="grid center p-5 pt-10">
            <?php if ($errmsg != '') {echo "<div class='error mb-3'>".$errmsg."</div>";} ?>
            <div class="row">
              <div class="cell text-center">
                <h2>Patientenverwaltung <br> <?php echo $ORG_NAME ?></h2>
              </div>
            </div>
            <div class="row mt-4">
              <div class="cell">
                <label>Benutzer</label>
                <select class="" name="lg_username" data-role="select">
                  <?php
                    # Sortiert die User-Accounts nach Gruppen und fügt sie in die entsprechende Opt-Group hinzu
                    $roles = array("Arzt", "Sichter", "Monitor", "TEL", "Admin");
                    $user_in_groups = array(array(),array(),array(),array(),array());

                    foreach ($users as $user) {
                      foreach ($roles as $idx => $role) {
                        if ($user["USER_ROLE"] == strtoupper($role)) {
                          array_push($user_in_groups[$idx], $user);
                        }
                      }
                    }

                    foreach ($user_in_groups as $idx => $usergroup) {
                      if (count($usergroup) == 0) {continue;}
                      echo "<optgroup label='$roles[$idx]'>";
                      foreach ($usergroup as $user) {
                        // Wenn der Nutzer in der URL übergeben wurde, wird dieser ausgewählt
                        $selected = '';
                        if (isset($_GET["u"]) && ($user["USERNAME"] == $_GET["u"])) {
                          $selected = 'selected';
                        }
                        echo "<option value='" . $user["USERNAME"] . "'".$selected.">" . $user["USERNAME"] . "</option>";
                      }
                      echo "</optgroup>";
                    }

                   ?>
                </select>
              </div>
            </div>

            <div class="row">
              <div class="cell">
                <label>Passwort</label>
                <input type="password" name="lg_password" value="" data-role="input">
              </div>
            </div>

            <div class="row">
              <div class="cell">
                <label>Abteilung</label>
                <select class="" name="lg_uhs" data-role="select">
                  <option value="" selected>Alle verfügbaren Daten</option>

                  <optgroup label="Sonderauswahl">
                    <?php foreach ($uhs as $row):?>
                      <option value="<?php echo $row["UHST_ID"]; ?>"><?php echo $row["NAME"]; ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                </select>
              </div>
            </div>

            <div class="row mt-5">
              <div class="cell text-center">
                <input type="submit" name="" value="Anmelden" class="button primary">
              </div>
            </div>
          </div>
        </form>
      </div>
      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
