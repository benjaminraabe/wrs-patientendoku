<?php
  // Dieser Endpoint nimmt Datenpakete von der Permission-Seite (Verwaltung von Benutzerrollen)
  //    an und versucht diese in die Datenbank zu schreiben.
  // Der Zugriff auf den Endpoint ist nur Super-Usern erlaubt.

  include_once '../backend/sessionmanagement.php';

  if (!in_array("PERM_PERMISSION_ADMINISTRATION", $_SESSION["PERMISSIONS"], true)) {
    echo "Zugriff verweigert.";
    exit();
  }


  include_once 'db.php';

  // Eingeschränkte User-Rollen berechnen.
  $restr_roles = safeQuery($conn, "SELECT ROLE_ID FROM ROLES_X_PERMISSIONS rp
                                  LEFT JOIN PERMISSIONS p on rp.PERMISSION_ID = p.PERMISSION_ID
                                  WHERE p.NAME = 'PERM_PERMISSION_ADMINISTRATION'");
  $admin_roles = safeQuery($conn, "SELECT ROLE_ID FROM ROLES_X_PERMISSIONS rp
                                  LEFT JOIN PERMISSIONS p on rp.PERMISSION_ID = p.PERMISSION_ID
                                  WHERE p.NAME = 'PERM_USER_ADMINISTRATION'");
  $restr_roles = array_map(fn($x) => $x["ROLE_ID"], $restr_roles);
  $admin_roles = array_map(fn($x) => $x["ROLE_ID"], $admin_roles);



  // #### Neuen Rolle anlegen ####
  if ($_POST["bNewPerm"] == "true") {
    // Eingabe prüfen. Rollenname muss angegeben sein.
    if (!array_key_exists("iName", $_POST)) {
      exit("Der Benutzerrolle muss ein Name vergeben werden.");
    }

    // Name und Beschreibung auf "sichere" Zeichen reduzieren
    $_POST["iDescription"] = (array_key_exists("iDescription", $_POST)) ? safeCharsOnly($_POST["iDescription"]) : "";
    $_POST["iName"] = safeCharsOnly($_POST["iName"]);

    // Rolle wird angelegt.
    safeExecute($conn, "INSERT INTO USER_ROLES (NAME, DESCRIPTION) VALUES (?,?) RETURNING ROLE_ID",
                       [$_POST["iName"], $_POST["iDescription"] ]);

    // ID der angelegten Rolle ermitteln. LAST_INSERTED_ID ist mir hier zu wackelig
    // Das ist etwas spekulativ, aber da (eigentlich!) immer nur mit Auto-Inkrement eingefügt wird,
    //    ist es extrem unwahrscheinlich, dass an dieser Stelle ein Fehler auftritt.
    $last_id = safeQuery($conn, "SELECT ROLE_ID FROM USER_ROLES WHERE NAME = ? ORDER BY ROLE_ID DESC;",
                         [$_POST["iName"]]);
    if (count($last_id) < 1) {
      exit("Ein Fehler ist beim Einfügen der Benutzerrolle aufgetreten.");
    } else {
      $last_id = $last_id[0]["ROLE_ID"];
    }

    // Berechtigungen werden vergeben.
    foreach ($_POST["bPerm"] as $k => $v) {
      safeExecute($conn, "INSERT INTO ROLES_X_PERMISSIONS(ROLE_ID, PERMISSION_ID) VALUES (?,?)",
                  [$last_id, $v]);
    }

    header("Location: ../frontend/permissionpage.php");
    exit();
  }



  // #### Rolle bearbeiten ####
  if ($_POST["bNewPerm"] == "false") {
    // Eingabe prüfen. Rollenname und Nummer müssem angegeben sein.
    if (!array_key_exists("iName", $_POST) || !array_key_exists("iRoleID", $_POST)) {
      exit("Der Benutzerrolle muss ein Name vergeben werden und eine ID muss mitgeschickt werden.");
    }

    // Name und Beschreibung auf "sichere" Zeichen reduzieren
    $_POST["iDescription"] = (array_key_exists("iDescription", $_POST)) ? safeCharsOnly($_POST["iDescription"]) : "";
    $_POST["iName"] = safeCharsOnly($_POST["iName"]);

    // Prüfen ob eine ID mit dieser Rolle überhaupt existiert
    $tmp = safeQuery($conn, "SELECT ROLE_ID FROM USER_ROLES WHERE ROLE_ID = ?", [$_POST["iRoleID"]]);
    if (count($tmp) < 1) {
      exit("Eine Benutzerrolle mit dieser ID existiert nicht.");
    }

    // Rollen-Daten verändern
    safeExecute($conn, "UPDATE USER_ROLES SET NAME = ?, DESCRIPTION = ? WHERE ROLE_ID = ?",
                       [$_POST["iName"], $_POST["iDescription"], $_POST["iRoleID"]]);

    // Berechtigungen aktualisieren
    safeExecute($conn, "DELETE FROM ROLES_X_PERMISSIONS WHERE ROLE_ID = ?", [$_POST["iRoleID"]]);
    foreach ($_POST["bPerm"] as $k => $v) {
      safeExecute($conn, "INSERT INTO ROLES_X_PERMISSIONS(ROLE_ID, PERMISSION_ID) VALUES (?,?)",
                  [$_POST["iRoleID"], $v]);
    }
  }

  header("Location: ../frontend/permissionpage.php");
  exit();
 ?>
