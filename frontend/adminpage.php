<?php
  include_once '../backend/sessionmanagement.php';
  if (!in_array("PERM_USER_ADMINISTRATION", $_SESSION["PERMISSIONS"], true)) {
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../backend/db.php';
  $user_list = safeQuery($conn, "SELECT USERNAME, ACTIVE,
                                 u.UHST_ID, d.NAME as UHS
                                 FROM USER u
                                 LEFT JOIN UHS_DEFINITION d on u.UHST_ID = d.UHST_ID
                                 ORDER BY USERNAME DESC");
  $user_x_roles = safeQuery($conn, "SELECT USERNAME, r.NAME from USER_X_ROLES rx
                                        LEFT JOIN USER_ROLES r on rx.ROLE_ID = r.ROLE_ID ORDER BY USERNAME DESC");
  $user_roles = safeQuery($conn, "SELECT r.NAME, r.ROLE_ID, ISNULL(j.NAME) as UNRESTRICTED FROM USER_ROLES r
                                  LEFT JOIN (SELECT ROLE_ID, NAME FROM ROLES_X_PERMISSIONS rp
                                             LEFT JOIN PERMISSIONS p on rp.PERMISSION_ID = p.PERMISSION_ID
                                             WHERE p.NAME = 'PERM_PERMISSION_ADMINISTRATION') j
                                  ON r.ROLE_ID = j.ROLE_ID;");

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
    <script type="text/javascript">
      const user_roles = JSON.parse(`<?php echo json_encode($user_x_roles); ?>`);
      const user_list = JSON.parse(`<?php echo json_encode($user_list); ?>`);
    </script>
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
            <button type="button" class="button primary place-right"onclick="openDlg('', true);">
              <span class="mif-plus icon" title="Neuer Benutzer" ></span></button>
          </div>
        </div>
        <div class="grid patliste-liste">
          <div class="row header-row fg-light" style="background-color: #024ea4;">
            <div class="cell-2 text-center"><b>Aktiv</b></div>
            <div class="cell"><b>Username</b></div>
            <div class="cell"><b>UHS</b></div>
            <div class="cell"><b>Rollen</b></div>
          </div>

          <!-- Wenn noch kein Benutzer vorhanden ist, wird dies gemeldet.
                Das ist ein Fallback und sollte im realen Betrieb nicht vorkommen. -->
          <?php if (count($user_list) == 0) : ?>
          <div class="row text-center fg-light" style="background-color: #999999;">
            <div class="cell p-0 pb-1">Bisher keine Benutzer.</div>
          </div>
          <?php endif; ?>

          <!-- Benutzer werden nach Rollen, Aktivität und Benutzernamen sortiert aufgelistet -->
          <?php if (count($user_list) > 0) : ?>
            <?php foreach ($user_list as $user) :?>

              <div class="row data-row pt-2 pb-2" onclick="openDlg('<?php echo $user["USERNAME"]; ?>')">
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
                  <?php foreach ($user_x_roles as $urole) :?>
                    <?php if ($urole["USERNAME"] == $user["USERNAME"]): ?>
                      <?php echo $urole["NAME"] ?> <br>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach ?>
          <?php endif; ?>

        </div>
      </div>


      <!-- Dialog Nutzer-Bearbeiten -->
      <div class="dialog" data-role="dialog" id="editDialog" style="max-height: 75vh;">
        <form action="../backend/process_admin_page.php" method="post" id="userForm">
          <input type="hidden" name="bNewUser" value="false">
          <div class="dialog-title" id="dlgTitle"> ### </div>
          <div class="dialog-content" style="overflow-y: auto;">
            <div class="grid">
              <div class="row">
                <div class="cell">Benutzername:</div>
                <div class="cell"><input type="text" data-role="input" name="iUsername"></div>
              </div>
              <div class="row">
                <div class="cell">Passwort:</div>
                <div class="cell"><input type="password" data-role="input" name="iPassword"></div>
              </div>
              <div class="row">
                <div class="cell">Benutzerrolle:</div>
                <div class="cell">
                  <?php foreach ($user_roles as $role) :?>
                    <input type="checkbox" data-role="checkbox"
                           data-caption="<?php echo $role['NAME'] ?>"
                           name="bUserrole[]" value="<?php echo $role['ROLE_ID'] ?>"
                    <?php if ($role["UNRESTRICTED"] == 0 && !in_array("PERM_PERMISSION_ADMINISTRATION", $_SESSION["PERMISSIONS"], true)): ?>
                      disabled<?php endif; ?>> <br>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="row">
                <div class="cell">Datenbeschränkung:</div>
                <div class="cell">
                  <select class="cstSelect" name="sUHS">
                    <option value="" selected>Alle Daten</option>
                    <?php foreach ($uhsen as $uhs) :?>
                      <option value="<?php echo $uhs["UHST_ID"]; ?>"><?php echo $uhs["NAME"] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="cell">Account:</div>
                <div class="cell">
                  <input type="checkbox" data-role="checkbox" data-caption="Aktiviert" name="bActive" checked>
                </div>
              </div>
            </div>
          </div>
          <div class="dialog-actions pr-6">
            <button type="button" class="button success w-100 mb-2" onclick="validateAndSend()">Speichern</button>
            <button type="button" class="button w-100 js-dialog-close">Abbrechen</button>
          </div>
        </form>
      </div>


      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
