<?php
  include_once '../backend/sessionmanagement.php';

  $accessible_to = array("ADMIN"); // Whitelist für Benutzerrollen

  if (!in_array($_SESSION["USER_ROLE"], $accessible_to, true)) { // Aktiver strict-mode!
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../backend/db.php';
  $user_list = safeQuery($conn, "SELECT USERNAME, ACTIVE, USER_ROLE, CAN_LATE_EDIT, CAN_SEARCH_PATIENTS, CAN_BACKDATE_PROTOCOL,
                                 u.UHST_ID, d.NAME as UHS
                                 FROM USER u
                                 LEFT JOIN UHS_DEFINITION d on u.UHST_ID = d.UHST_ID
                                 ORDER BY USER_ROLE, ACTIVE DESC, USERNAME");

  $monitor_nachricht = safeQuery($conn, "SELECT ITEM FROM MONITOR_STRINGS;");
  if (count($monitor_nachricht) > 0) {
    $monitor_nachricht = $monitor_nachricht[0]["ITEM"];
  } else {
    $monitor_nachricht = "";
  }

  $uhsen = safeQuery($conn, "SELECT * FROM UHS_DEFINITION");
 ?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "Admin-Seite";
 ?>






<!DOCTYPE html>
<html lang="de" dir="ltr">
  <head>
    <title> <?php echo $PAGE_TITLE . " | " . $ORG_NAME; ?> </title>
    <meta charset="utf-8">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="../styles/metro-all.min.css">
    <script src="../scripts/metro.min.js" charset="utf-8"></script>
    <script src="../scripts/adminpage.js" charset="utf-8"></script>
    <link rel="stylesheet" href="../styles/page.css">
    <link rel="stylesheet" href="../styles/patliste.css">
  </head>
  <body>
    <div class="page-wrapper">
      <?php include_once '../modules/header.php'; ?>

      <!-- Eingabeblock zum Ändern der aktuellen Nachricht auf dem Monitor -->
      <div class="p-5">
        <h4>Monitor-Nachricht</h4>
        <div class="grid">
          <div class="row">
            <div class="cell">
              <input id="formMonitorNachricht" type="text" data-role="input" value="<?php echo $monitor_nachricht; ?>">
            </div>
            <div class="cell-2">
              <button type="button" class="button w-100" onclick="updateMonitor();">Speichern</button>
            </div>
          </div>
        </div>
      </div>

      <div class="p-5 mb-5">
        <div class="row">
          <div class="cell">
            <h4 class="pr-0">Benutzerverwaltung</h4>
          </div>
          <div class="cell" style="padding-right: 1px;">
            <button type="button" class="button primary place-right"onclick="openDlg('', {}, false);">
              <span class="mif-plus icon" title="Neuer Benutzer" ></span></button>
          </div>
        </div>
        <div class="grid patliste-liste">
          <div class="row header-row fg-light" style="background-color: #024ea4;">
            <div class="cell-2 text-center"><b>Aktiv</b></div>
            <div class="cell"><b>Username</b></div>
            <div class="cell"><b>UHS</b></div>
            <div class="cell"><b>Berechtigungen</b></div>
          </div>

          <!-- Wenn noch kein Benutzer vorhanden ist, wird dies gemeldet.
                Das ist ein Fallback und sollte im realen Betrieb nicht vorkommen. -->
          <?php if (count($user_list) == 0) : ?>
          <div class="row text-center fg-light" style="background-color: #999999;">
            <div class="cell p-0 pb-1">Bisher keine Patienten.</div>
          </div>
          <?php endif; ?>

          <!-- Benutzer werden nach Rollen, Aktivität und Benutzernamen sortiert aufgelistet -->
          <?php if (count($user_list) > 0) : ?>
            <?php $lastcategory = ""; ?>
            <?php foreach ($user_list as $user) :?>

              <!-- Ggf. Sektionstrenner schreiben -->
              <?php if ($lastcategory != $user["USER_ROLE"]) : ?>
                <?php $lastcategory = $user["USER_ROLE"]; ?>
                <div class="row divider-row text-center fg-light" style="background-color: #999999;">
                  <div class="cell-2 p-0 pb-1"><?php echo $user["USER_ROLE"]; ?></div>
                </div>
              <?php endif; ?>

              <div class="row data-row pt-2 pb-2" onclick="openDlg('<?php echo $user["USERNAME"]; ?>',
                {'role': '<?php echo $user["USER_ROLE"]; ?>',
                 'uhs' : '<?php echo $user["UHST_ID"]; ?>',
                 'active' : '<?php echo $user["ACTIVE"]; ?>',
                 'late_edit' : '<?php echo $user["CAN_LATE_EDIT"]; ?>',
                 'backdate_protocol' : '<?php echo $user["CAN_BACKDATE_PROTOCOL"]; ?>',
                 'pat_search' : '<?php echo $user["CAN_SEARCH_PATIENTS"]; ?>'})">
                <div class="cell-2 text-center cell-deactivation">
                  <?php if ($user["ACTIVE"] == 0) : ?>
                    <span class="mif-cross icon" title="Benutzer ist deaktiviert"></span>
                  <?php endif; ?>
                </div>
                <div class="cell" lang="de">
                  <?php echo $user["USERNAME"]; ?>
                </div>
                <div class="cell"><?php echo $user["UHS"]; ?></div>
                <div class="cell cell-permissions">
                  <?php if ($user["CAN_LATE_EDIT"] == 1) : ?>
                    <span class="mif-pencil icon" title="Korrekturzugang freigeschaltet"></span>
                  <?php endif; ?>
                  <?php if ($user["CAN_SEARCH_PATIENTS"] == 1) : ?>
                    <span class="mif-search icon" title="Patientensuche freigeschaltet"></span>
                  <?php endif; ?>
                  <?php if ($user["CAN_BACKDATE_PROTOCOL"] == 1) : ?>
                    <span class="mif-alarm icon" title="Rückdatierung von Protokollen freigeschaltet"></span>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach ?>
          <?php endif; ?>

        </div>
      </div>


      <!-- Dialog Nutzer-Bearbeiten -->
      <div class="dialog" data-role="dialog" id="editDialog" style="max-height: 75vh;">
        <div class="dialog-title" id="dlgTitle"> ### </div>
        <div class="dialog-content" style="overflow-y: auto;">
          <div class="grid">
            <div class="row">
              <div class="cell">Benutzername:</div>
              <div class="cell"><input type="text" data-role="input" id="formUsername"></div>
            </div>
            <div class="row">
              <div class="cell">Passwort:</div>
              <div class="cell"><input type="password" data-role="input" id="formPassword"></div>
            </div>
            <div class="row">
              <div class="cell">Benutzerrolle:</div>
              <div class="cell">
                <!-- Hier ist von alphabetischer Sortierung abgesehen worden, da sonst
                        "Admin" die Standardauswahl wäre.
                     Stattdessen wird nach "Berechtigungsleveln" sortiert. Damit
                        wird hoffentlich vermieden, dass versehentlich ein zu hohes
                        Permission-Level vergeben wird.-->
                <select class="" data-role="select" data-filter="false" id="formRoleSelect">
                  <option value="MONITOR">Monitor</option>
                  <option value="ARZT">Arzt</option>
                  <option value="SICHTER">Sichter</option>
                  <option value="TEL">TEL</option>
                  <option value="ADMIN">Admin</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="cell">Datenbeschränkung:</div>
              <div class="cell">
                <select class="" data-role="select" data-filter="false" id="formUhstSelect">
                  <option value="">Alle Daten</option>
                  <?php foreach ($uhsen as $uhs) :?>
                    <option value="<?php echo $uhs["UHST_ID"]; ?>"><?php echo $uhs["NAME"] ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="cell">Account:</div>
              <div class="cell">
                <input type="checkbox" id="formActive" data-role="checkbox" data-caption="Aktiviert">
              </div>
            </div>
            <div class="row">
              <div class="cell">&nbsp;</div>
            </div>
            <div class="row">
              <div class="cell">&nbsp;</div>
            </div>
            <div class="row">
              <div class="cell">
                <input type="checkbox" id="formLateEdit" data-role="checkbox" data-caption="Korrekturzugang">
              </div>
              <div class="cell">
                <input type="checkbox" id="formPatSearch" data-role="checkbox" data-caption="Patientensuche">
              </div>
            </div>
            <div class="row">
              <div class="cell">
                <input type="checkbox" id="formBackdate" data-role="checkbox" data-caption="Protokolle rückdatieren (Nachtragezugang)">
              </div>
            </div>

          </div>
        </div>
        <div class="dialog-actions pr-6">
            <button id="formBtnNew" class="button success w-100 mb-2" onclick="updateUserData(false)">Speichern</button>
            <button id="formBtnEdit" class="button success w-100 mb-2" onclick="updateUserData()">Speichern</button>
            <button class="button w-100 js-dialog-close">Abbrechen</button>
        </div>
      </div>


      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
