<?php
  // Dieser Endpoint handled eingehende Login-Versuche.
  // Bei Erfolg werden Session-Variablen initialisiert und der Nutzer wird
  //    auf die passende Index-Seite weitergeleitet.
  //    Im Fehlerfall wird der Login-Versuch geloggt.
  include_once 'db.php';

  # Bei fehlgeschlagenen Login-Versuchen wird eine Meldung mit der
  #     IP-Adresse des Clients in den Log geschrieben.
  $user_ip = "Unknown.";
  try {
    $user_ip = $_SERVER['REMOTE_ADDR'];
  } catch (\Exception $e) {}


  session_start(['cookie_lifetime' => $CST_SESSION_LIFETIME]);

  $username = $_POST["lg_username"];
  $password = $_POST["lg_password"];
  $user_set_uhs = $_POST["lg_uhs"];

  try {
    $logindata = safeQuery($conn, "SELECT * FROM USER WHERE USERNAME = ? AND ACTIVE = 1;", [$username]);
    $permissiondata = safeQuery($conn, "SELECT DISTINCT p.NAME from USER_X_ROLES rx
                                        LEFT JOIN ROLES_X_PERMISSIONS px on rx.ROLE_ID = px.ROLE_ID
                                        LEFT JOIN PERMISSIONS p on px.PERMISSION_ID = p.PERMISSION_ID
                                        WHERE USERNAME = ?", [$username]);
    $permissions = array();
    foreach ($permissiondata as $perm) {
      $permissions[] = $perm["NAME"];
    }
  } catch (\Exception $e) {
    header('Location: ../frontend/login.php?errmsg=dberror');
    exit();
  }

  # Wenn kein Eintrag gefunden wird, war der Username nicht gültig. Abbruch.
  if (count($logindata) < 1) {
    header('Location: ../frontend/login.php?errmsg=loginerror');
    error_log("[LOGIN-ERROR] Fehlgeschlagener Login (".$username.") von der IP: ".$user_ip);
    exit();
  }
  $user_data = $logindata[0];

  # User kann seine Sicht auf eine bestimmte UHS einschränken. Das ist aber nur erlaubt,
  #     wenn der Nutzer nicht sowieso nur Zugriff auf eine UHS hat.
  #     (Sonst könnte er Daten von UHS sehen, für die der User nicht freigeschaltet ist.)
  if (is_null($user_data["UHST_ID"]) && $user_set_uhs != '') {
    $uhs = $user_set_uhs;
  } else {
    $uhs = $user_data["UHST_ID"];
  }

  # Passwort wird abgeglichen und relevante Session-Daten gesetzt.
  if (password_verify($password, $user_data["PW_HASH"])) {
    $_SESSION['USER_ID']        = $username;
    $_SESSION['UHS']            = $uhs;
    $_SESSION['USER_ROLE']      = $user_data["USER_ROLE"];
    $_SESSION['LAST_ACTIVITY']  = time();
    $_SESSION['PERMISSIONS']    = $permissions;
    if (count($permissions) == 1 && $permissions[0] == "PERM_PUBLIC_MONITOR") {
      # Wenn nur der Monitor freigegeben ist, wird direkt auf diese Seite weitergeleitet.
      header('Location: ../frontend/monitor.php');
    } else {
      header('Location: ../frontend/index.php');
    }
    exit();
  } else {
    error_log("[LOGIN-ERROR] Fehlgeschlagener Login (".$username.") von der IP: ".$user_ip);
    header('Location: ../frontend/login.php?errmsg=loginerror');
    exit();
  }
 ?>
