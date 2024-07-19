<?php
  include_once '../backend/sessionmanagement.php';

  if (!in_array("PERM_PERMISSION_ADMINISTRATION", $_SESSION["PERMISSIONS"], true)) {
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../backend/db.php';
  $role_list = safeQuery($conn, "SELECT * FROM USER_ROLES WHERE IS_EDITABLE = 1 ORDER BY NAME ASC;");
  $role_x_perm = safeQuery($conn, "SELECT * FROM ROLES_X_PERMISSIONS;");
  $permission_list = safeQuery($conn, "SELECT * FROM PERMISSIONS");
 ?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "Berechtigungs-Verwaltung";
 ?>


<!DOCTYPE html>
<html lang="de" dir="ltr">
  <head>
    <title> <?php echo $PAGE_TITLE . " | " . $ORG_NAME; ?> </title>
    <meta charset="utf-8">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="../styles/metro-all.min.css">
    <script src="../scripts/metro.min.js" charset="utf-8"></script>
    <script src="../scripts/permissionpage.js" charset="utf-8"></script>
    <link rel="stylesheet" href="../styles/page.css">
    <link rel="stylesheet" href="../styles/patliste.css">
    <script type="text/javascript">
      const role_list = JSON.parse(`<?php echo json_encode($role_list); ?>`);
      const permission_list = JSON.parse(`<?php echo json_encode($permission_list); ?>`);
      const role_x_perm = JSON.parse(`<?php echo json_encode($role_x_perm); ?>`);
    </script>
  </head>
  <body>
    <div class="page-wrapper">
      <?php include_once '../modules/header.php'; ?>

      <!-- Eingabeblock zum Ändern der aktuellen Nachricht auf dem Monitor -->
      <div class="p-5 mb-5">
        <div class="row">
          <div class="cell">
            <h4 class="pr-0">Benutzerrollen</h4>
          </div>
          <div class="cell" style="padding-right: 1px;">
            <button type="button" class="button primary place-right"onclick="openDlg('', true);">
              <span class="mif-plus icon" title="Neue Rolle"></span></button>
          </div>
        </div>
        <div class="grid patliste-liste">
          <div class="row header-row fg-light" style="background-color: #024ea4;">
            <div class="cell-3 text-center"><b>Name</b></div>
            <div class="cell text-center"><b>Beschreibung</b></div>
          </div>

          <!-- Wenn noch kein zusätzlichen Rollen vorhanden sind, wird das angezeigt. -->
          <?php if (count($role_list) == 0) : ?>
          <div class="row text-center fg-light" style="background-color: #999999;">
            <div class="cell p-0 pb-1">Keine Rollen angelegt.</div>
          </div>
          <?php endif; ?>

          <!-- Benutzer werden nach Rollen, Aktivität und Benutzernamen sortiert aufgelistet -->
          <?php if (count($role_list) > 0) : ?>
            <?php foreach ($role_list as $role) :?>

              <div class="row data-row pt-2 pb-2" onclick="openDlg('<?php echo $role["ROLE_ID"]; ?>')">
                <div class="cell-3 text-center">
                    <b><?php echo $role["NAME"]; ?></b>
                </div>
                <div class="cell" lang="de">
                  <?php echo $role["DESCRIPTION"]; ?>
                </div>
              </div>
            <?php endforeach ?>
          <?php endif; ?>

        </div>
      </div>


      <!-- Dialog Nutzer-Bearbeiten -->
      <div class="dialog" data-role="dialog" id="editDialog" style="max-height: 75vh;">
        <form action="../backend/process_permission_page.php" method="post" id="permissionForm">
          <input type="hidden" name="bNewPerm" value="false">
          <input type="hidden" name="iRoleID" value="false">
          <div class="dialog-title" id="dlgTitle"> ### </div>
          <div class="dialog-content" style="overflow-y: auto; max-height: 55vh;">
            <div class="grid">
              <div class="row">
                <div class="cell"><b>Name:</b></div>
              </div>
              <div class="row">
                <div class="cell"><input type="text" data-role="input" name="iName"></div>
              </div>
              <div class="row">
                <div class="cell"><b>Beschreibung:</b></div>
              </div>
              <div class="row">
                <div class="cell"><input type="text" data-role="input" name="iDescription"></div>
              </div>
              <div class="row">
                <div class="cell"><b>Berechtigungen:</b></div>
              </div>
              <div class="row">
                <div class="cell">
                  <?php foreach ($permission_list as $perm) :?>
                    <input type="checkbox" data-role="checkbox"
                    data-caption="<?php echo $perm['NAME'] ?>"
                    title="<?php echo $perm['DESCRIPTION'] ?>"
                    name="bPerm[]" value="<?php echo $perm['PERMISSION_ID'] ?>"><br>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="dialog-actions pr-6">
            <input type="submit" class="button success w-100 mb-2" value="Speichern">
            <button type="button" class="button w-100 js-dialog-close">Abbrechen</button>
          </div>
        </form>
      </div>


      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
