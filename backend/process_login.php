<?php
  # CAVE (!): Dieser Code leakt durch die Fehlermeldungen ob ein Account existiert.
  #     Das ist hier nicht relevant, da Accounts sowieso ausgewählt werden, muss aber
  #     beachtet werden, wenn blind ge-Copy-Pasted wird!
  include_once 'db.php';

  $CST_SESSION_LIFETIME = 12 * 60 * 60; # 12 Stunden
  session_start(['cookie_lifetime' => $CST_SESSION_LIFETIME]);

  $username = $_POST["lg_username"];
  $password = $_POST["lg_password"];
  $user_set_uhs = $_POST["lg_uhs"];

  try {
    $logindata = safeQuery($conn, "SELECT * FROM USER WHERE USERNAME = ? AND ACTIVE = 1;", [$username]);
  } catch (\Exception $e) {
    header('Location: ../frontend/login.php?errmsg=dbfehler');
    exit();
  }

  # Wenn kein Eintrag gefunden wird, war der Username nicht gültig. Abbruch.
  #   Sollte nie eintreten, ist ein Hinweis auf Schindluder oder Fehler im Frontend.
  if (count($logindata) < 1) {
    header('Location: ../frontend/login.php?errmsg=invalid');
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
    $_SESSION['USER_ID'] = $username;
    $_SESSION['UHS'] = $uhs;
    $_SESSION['USER_ROLE'] = $user_data["USER_ROLE"];
    $_SESSION['LAST_ACTIVITY'] = time();
    $_SESSION['CAN_LATE_EDIT'] = $user_data["CAN_LATE_EDIT"];
    $_SESSION['CAN_SEARCH_PATIENTS'] = $user_data["CAN_SEARCH_PATIENTS"];
    $_SESSION['CAN_BACKDATE_PROTOCOL'] = $user_data["CAN_BACKDATE_PROTOCOL"];
    if ($user_data["USER_ROLE"] == "MONITOR") {
      header('Location: ../frontend/monitor.php');
    } else {
      header('Location: ../frontend/index.php');
    }
    exit();
  } else {
    $user_ip = "Unknown.";
    try {
      $user_ip = $_SERVER['REMOTE_ADDR'];
    } catch (\Exception $e) {}
    error_log("[LOGIN-ERROR] Fehlgeschlagener Login (".$username.") von der IP: ".$user_ip);
    header('Location: ../frontend/login.php?errmsg=pwwrong');
    exit();
  }
 ?>
