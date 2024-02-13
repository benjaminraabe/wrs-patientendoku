<?php
  include_once 'utils.php';
  include_once '../config.php';
  // Stellt ein verbundenes PDO-Datenbank-Objekt zur Verfügung.
  // Zusätzliche Verbindungen müssen von der importierenden Seite
  //    selbst erzeugt werden.
  $conn = connectToDB($db_servername, $db_username, $db_password, $db_dbname);
?>
