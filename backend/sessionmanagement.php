<?php
  // Implementiert einen "Login-Check", den alle Unter-Seiten aufrufen (sollen).
  //    Prüft generalisiert nur, ob ein Client eingeloggt ist, NICHT in welcher Rolle dieser eingeloggt ist.
  //    Dies müssen die Seiten nach Bedarf selbst implementieren.

  $CST_SESSION_MAX_IDLETIME = 12 * 60 * 60; # 12 Stunden
  session_start();

  # Wenn kein Login verzeichnet ist, wird der User auf die Login-Seite umgeleitet.
  if (!isset($_SESSION['USER_ID'])) {
    header('Location: login.php');
    exit();
  }

  # Wenn die Session Serverseitig abgelaufen ist, wird der User auf die Login-Seite umgeleitet.
  if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $CST_SESSION_MAX_IDLETIME)) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
  }
  $_SESSION['LAST_ACTIVITY'] = time();

  // Wenn der Benutzer deaktiviert wurde, wird die Session zerstört und der User auf die Login-Seite umgeleitet.
  //    Das verhindert, dass ein Benutzer weiter eingeloggt bleiben kann, nachdem ihm der Zugriff entzogen wurde
  try {
    include_once '../backend/db.php';
    $logindata = safeQuery($conn, "SELECT * FROM USER WHERE USERNAME = ? AND ACTIVE = 1;", [$_SESSION['USER_ID']]);
  } catch (\Exception $e) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
  }
  if (count($logindata) != 1) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
  }

  // Aktualisiert die vergebenen Berechtigungen für einen Benutzer
  //    Das führt dazu, dass neu vergebenen oder entfernte Berechtigungen auch ohne ein neues
  //    Login wirksam werden.
  try {
    $permissiondata = safeQuery($conn, "SELECT DISTINCT p.NAME from USER_X_ROLES rx
                                        LEFT JOIN ROLES_X_PERMISSIONS px on rx.ROLE_ID = px.ROLE_ID
                                        LEFT JOIN PERMISSIONS p on px.PERMISSION_ID = p.PERMISSION_ID
                                        WHERE USERNAME = ?", [$_SESSION['USER_ID']]);
    $permissiondata = array_map(fn($p) => $p["NAME"], $permissiondata);
    $_SESSION['PERMISSIONS'] = $permissiondata;
  } catch (\Exception $e) {
    session_unset();
    session_destroy();
    header('Location: login.php?errmsg=dberror');
    exit();
  }
 ?>
