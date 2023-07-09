<?php
  include_once '../backend/sessionmanagement.php';

  $accessible_to = array("ADMIN"); // Whitelist für Benutzerrollen

  if (!in_array($_SESSION["USER_ROLE"], $accessible_to, true)) { // Aktiver strict-mode!
    echo "Zugriff verweigert.";
    exit();
  }
?>


<?php
  include_once 'db.php';
  $postdata = json_decode(file_get_contents('php://input'), true);

  // Monitor-Nachricht verändern
  if (array_key_exists("MONITOR_NACHRICHT", $postdata)) {
    $query = safeQuery($conn, "SELECT * FROM MONITOR_STRINGS ORDER BY UID;");
    if (count($query) > 0) {
      safeExecute($conn, "UPDATE MONITOR_STRINGS SET ITEM = ? WHERE UID = ?;", [$postdata["MONITOR_NACHRICHT"], $query[0]["UID"]]);
    } else { // Fallback, wenn noch kein Eintrag existiert
      safeExecute($conn, "INSERT INTO MONITOR_STRINGS(ITEM) VALUES (?)", [$postdata["MONITOR_NACHRICHT"]]);
    }
  }


  // Neuen Benutzer anlegen
  if (array_key_exists("NEW_USER", $postdata)) {
    $postdata = nullEmptyString($postdata);
    safeExecute($conn, "INSERT INTO USER (USERNAME, PW_HASH, UHST_ID, ACTIVE, USER_ROLE, CAN_LATE_EDIT, CAN_SEARCH_PATIENTS, CAN_BACKDATE_PROTOCOL)
                                    VALUES (?,?,?,?,?,?,?,?)", [
                                      $postdata["USERNAME"],
                                      password_hash($postdata["PASSWORD"], PASSWORD_DEFAULT),
                                      $postdata["UHST_ID"],
                                      $postdata["ACTIVE"],
                                      $postdata["USER_ROLE"],
                                      $postdata["CAN_LATE_EDIT"],
                                      $postdata["CAN_SEARCH_PATIENTS"],
                                      $postdata["CAN_BACKDATE_PROTOCOL"]
                                    ]);
  }

  // Benutzer bearbeiten
  if (array_key_exists("MODIFY_USER", $postdata)) {
    // Passwortwechsel muss separat gehandelt werden, da dem Server nur der Hash des User-PWs bekannt ist
    if (array_key_exists("PASSWORD", $postdata) && ($postdata["PASSWORD"] != "")) {
      safeExecute($conn, "UPDATE USER SET PW_HASH = ? WHERE USERNAME = ?;",
                  [password_hash($postdata["PASSWORD"], PASSWORD_DEFAULT), $postdata["USERNAME"]]);
    }

    // Verhindert, dass sich der Admin selbst aussperrt, indem er seinen Account deaktiviert oder "degradiert"
    if ($postdata["USERNAME"] == $_SESSION["USER_ID"]) {
      if ($postdata["ACTIVE"] != 1) {
        exit("Error: Der eigene Account darf nicht deaktiviert werden! (Aussperr-Gefahr)");
      }
      if ($postdata["USER_ROLE"] != "ADMIN") {
        exit("Error: Dem eigenen Account dürfen nicht die Admin-Berechtigungen entzogen werden! (Aussperr-Gefahr)");
      }

    }

    // Der Username kann nicht geändert werden, da es sonst ggf. zu Fremdschlüsselproblemen mit dem Verlauf kommt.
    // TODO: GGf. verhindern, das Arzt-Account / Nicht-Arzt-Accounts ineinander umgewandelt werden (USER_ROLE ändern)
    //        Dann kann es zu Problemen mit Arzt-Visitationen als Verlaufseinträge kommen. Das ist aber am besten bei den
    //        Verlaufseinträgen zu ändern.
    $postdata = nullEmptyString($postdata);
    safeExecute($conn, "UPDATE USER SET UHST_ID = ?, ACTIVE = ?, USER_ROLE = ?,
                        CAN_LATE_EDIT = ?, CAN_SEARCH_PATIENTS = ?, CAN_BACKDATE_PROTOCOL = ? WHERE USERNAME = ?", [
                                      $postdata["UHST_ID"],
                                      $postdata["ACTIVE"],
                                      $postdata["USER_ROLE"],
                                      $postdata["CAN_LATE_EDIT"],
                                      $postdata["CAN_SEARCH_PATIENTS"],
                                      $postdata["CAN_BACKDATE_PROTOCOL"],
                                      $postdata["USERNAME"]
                                    ]);
  }
 ?>
