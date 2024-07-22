<?php
  // Dieser Endpoint nimmt Datenpakete von der Admin-Seite (Benutzerverwaltung)
  //    an und versucht diese in die Datenbank zu schreiben.
  // Der Zugriff auf den Endpoint ist selbstverständlich nur Admins erlaubt.
  include_once '../backend/sessionmanagement.php';

  if (!in_array("PERM_USER_ADMINISTRATION", $_SESSION["PERMISSIONS"], true)) {
    echo "Zugriff verweigert.";
    exit();
  }

  // Die Verarbeitungsformen in diesem File sind geteilt, der Monitor nutzt (noch) ein JSON-Request,
  //    und verarbeitet die Rückgabe Client-seitig. Die Benutzerverwaltung läuft mittlerweile
  //    über HTML-Forms und wird deshalb aus $_POST geladen.
  // Die Teilung ist historisch und wird irgendwann überarbeitet - gerade fehlt mir dazu leider die Kapazität.
  include_once 'db.php';
  $postdata = json_decode(file_get_contents('php://input'), true);

  // Monitor-Nachricht verändern
  if($postdata) {
    if (array_key_exists("MONITOR_NACHRICHT", $postdata)) {
      $query = safeQuery($conn, "SELECT * FROM MONITOR_STRINGS ORDER BY UID;");
      if (count($query) > 0) {
        safeExecute($conn, "UPDATE MONITOR_STRINGS SET ITEM = ? WHERE UID = ?;", [$postdata["MONITOR_NACHRICHT"], $query[0]["UID"]]);
      } else { // Fallback, wenn noch kein Eintrag existiert
        safeExecute($conn, "INSERT INTO MONITOR_STRINGS(ITEM) VALUES (?)", [$postdata["MONITOR_NACHRICHT"]]);
      }
      exit();
    }
  }




  // Eingeschränkte User-Rollen berechnen.
  $restr_roles = safeQuery($conn, "SELECT ROLE_ID FROM ROLES_X_PERMISSIONS rp
                                  LEFT JOIN PERMISSIONS p on rp.PERMISSION_ID = p.PERMISSION_ID
                                  WHERE p.NAME = 'PERM_PERMISSION_ADMINISTRATION'");
  $admin_roles = safeQuery($conn, "SELECT ROLE_ID FROM ROLES_X_PERMISSIONS rp
                                  LEFT JOIN PERMISSIONS p on rp.PERMISSION_ID = p.PERMISSION_ID
                                  WHERE p.NAME = 'PERM_USER_ADMINISTRATION'");
  $restr_roles = array_map(fn($x) => $x["ROLE_ID"], $restr_roles);
  $admin_roles = array_map(fn($x) => $x["ROLE_ID"], $admin_roles);

  // #### Neuen Benutzer anlegen ####
  if ($_POST["bNewUser"] == "true") {
    // Eingabe prüfen (Username und Passwort müssen angegeben sein)
    if (!array_key_exists("iUsername", $_POST) || !array_key_exists("iPassword", $_POST)) {
      exit("Username und Passwort müssen bei einem neuen Benutzer angegeben werden.");
    }

    // Username auf "sichere" Zeichen reduzieren und auf Duplikate prüfen
    $_POST["iUsername"] = safeCharsOnly($_POST["iUsername"]);
    $tmp = safeQuery($conn, "SELECT USERNAME FROM USER WHERE USERNAME = ?;", [$_POST["iUsername"]]);
    if (count($tmp) > 0) {
      exit("Ein Benutzer mit diesem Namen existiert bereits.");
    }

    // Wenn Eingeschränkte Rollen vergeben werden sollen, müssen SU-Admin Rechte vorhanden sein.
    $contains_restricted = array_reduce($_POST["bUserrole"] ?? array(),
                                        fn($acc, $val) => ($acc || in_array($val, $restr_roles)),
                                        false);
    if ($contains_restricted && !in_array("PERM_PERMISSION_ADMINISTRATION", $_SESSION["PERMISSIONS"], true)) {
      exit("Eine oder mehrere Rollen mit SU-Rechten wurden vergeben. Dies ist nur Nutzern mit SU-Rechten erlaubt.");
    }

    // Benutzer wird angelegt.
    safeExecute($conn, "INSERT INTO USER (USERNAME, PW_HASH, UHST_ID, ACTIVE)
                                    VALUES (?,?,?,?)", [
                                      $_POST["iUsername"],
                                      password_hash($_POST["iPassword"], PASSWORD_DEFAULT),
                                      (($_POST["sUHS"] != "") ? $_POST["sUHS"] : NULL),
                                      array_key_exists("bActive", $_POST) ? 1:0
                                    ]);

    // Rollen werden angelegt.
    foreach ($_POST["bUserrole"] as $k => $v) {
      safeExecute($conn, "INSERT INTO USER_X_ROLES(USERNAME, ROLE_ID) VALUES (?,?)",
                  [$_POST["iUsername"], $v]);
    }

    header('Location: ../frontend/adminpage.php');
    exit();
  }



  // #### Benutzer bearbeiten ####
  if ($_POST["bNewUser"] == "false") {
    // Eingabe prüfen
    if (!array_key_exists("iUsername", $_POST)) {
      exit("Username muss beim Bearbeiten des Benutzers angegeben werden.");
    }

    // Bisherige Daten des Benutzers abfragen
    $user_data = safeQuery($conn, "SELECT * FROM USER WHERE USERNAME=?", [$_POST["iUsername"]]);
    $user_roles = safeQuery($conn, "SELECT * FROM USER_X_ROLES WHERE USERNAME=?", [$_POST["iUsername"]]);
    $user_roles = array_map(fn($x) => $x["ROLE_ID"], $user_roles);

    if (count($user_data) == 0) {
      exit("Die angegebene Benutzerrolle existiert nicht.");
    }

    // Prüfe ob in den "neuen" Benutzerrollen SU-Rollen enthalten sind
    $contains_restricted = array_reduce($_POST["bUserrole"] ?? array(),
                                        fn($acc, $val) => ($acc || in_array($val, $restr_roles)),
                                        false);
    // Prüfe ob in den bisherigen Benutzerrollen SU-Rollen enthalten sind
    $contained_restricted_before = array_reduce($user_roles,
                                                fn($acc, $val) => ($acc || in_array($val, $restr_roles)),
                                                false);

    // Passwortwechsel
    if (array_key_exists("iPassword", $_POST) && ($_POST["iPassword"] != "")) {
      if ($contained_restricted_before) {
        if (!in_array("PERM_PERMISSION_ADMINISTRATION", $_SESSION["PERMISSIONS"], true)) {
          exit("Das Ändern von SU-Rollen ist nur Super-Usern gestattet.");
        }
      }
      safeExecute($conn, "UPDATE USER SET PW_HASH = ? WHERE USERNAME = ?;",
                  [password_hash($_POST["iPassword"], PASSWORD_DEFAULT), $_POST["iUsername"]]);
    }

    // Verhindert, dass sich der Admin selbst aussperrt, indem er seinen Account deaktiviert oder "degradiert"
    // Bezieht sich nur auf den eigenen Account. Sollten sich zwei Admins wechselseitig degradieren,
    //    könnte es hier zu Problemen kommen. Da tut es mir dann aber leid, das fange ich jetzt nicht ab.
    if ($_POST["iUsername"] == $_SESSION["USER_ID"]) {
      if (!array_key_exists("bActive", $_POST)) {
        exit("Error: Der eigene Account darf nicht deaktiviert werden! (Aussperr-Gefahr)");
      }

      $contains_admin = array_reduce($_POST["bUserrole"] ?? array(),
                                          fn($acc, $val) => ($acc || in_array($val, $admin_roles)),
                                          false);

      if (!$contains_admin) {
        exit("Error: Dem eigenen Account dürfen nicht die Admin-Berechtigungen entzogen werden! (Aussperr-Gefahr)");
      }
      if ($contained_restricted_before && !$contains_restricted) {
        exit("Error: Dem eigenen Account dürfen nicht die SuperUser-Berechtigungen entzogen werden! (Aussperr-Gefahr)");
      }
    }

    // Änderungen an SU-Accounts dürfen nur von Super-Usern durchgeführt werden (SU hinzufügen oder entfernen)
    if (($contained_restricted_before && !$contains_restricted)
        || (!$contained_restricted_before && $contains_restricted)){
          if (!in_array("PERM_PERMISSION_ADMINISTRATION", $_SESSION["PERMISSIONS"], true)) {
            exit("Das Ändern von SU-Rollen ist nur Super-Usern gestattet.");
          }
        }

    // User-Daten verändern
    // Der Username kann nicht geändert werden, da es sonst ggf. zu
    //    Fremdschlüsselproblemen kommt.
    safeExecute($conn, "UPDATE USER SET UHST_ID = ?, ACTIVE = ? WHERE USERNAME = ?", [
                                      (($_POST["sUHS"] != "") ? $_POST["sUHS"] : NULL),
                                      array_key_exists("bActive", $_POST) ? 1:0,
                                      $_POST["iUsername"]
                                    ]);

    // Benutzerrollen aktualisieren
    safeExecute($conn, "DELETE FROM USER_X_ROLES WHERE USERNAME = ?", [$_POST["iUsername"]]);
    foreach ($_POST["bUserrole"] as $k => $v) {
      safeExecute($conn, "INSERT INTO USER_X_ROLES(USERNAME, ROLE_ID) VALUES (?,?)",
                  [$_POST["iUsername"], $v]);
    }
  }

  header("Location: ../frontend/adminpage.php");
  exit();
 ?>
