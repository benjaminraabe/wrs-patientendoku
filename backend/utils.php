<?php
  function connectToDB($servername, $username, $password, $dbname) {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
  }

  // SQLi-sicherer Query -> Als Array of Assoziativ-Array
  // Natürlich nur sicher, wenn man auch "?" Platzhalter verwendet!!!!!!!
  function safeQuery($connection, $sql, $parameters = array()) {
    // HTML-Special-Chars werden bereinigt um Template-XSS zu vermeiden
    foreach ($parameters as $key => $value) {
      if ($value !== NULL) {
        $parameters[$key] = htmlspecialchars($value);
      }
    }
    // Prepared-Queries werde verwendet
    $stmt = $connection->prepare($sql);
    $stmt->execute($parameters);
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $result = $stmt->fetchAll();
    // Failsafe for Versions < PHP 8
    if ($result == false) {
      $result = array();
    }
    return $result;
  }

  // SQLi-sicherer Insert/Update -> Anzahl der veränderten Zeilen
  // Natürlich nur sicher, wenn man auch "?" Platzhalter verwendet!!!!!!!
  function safeExecute($connection, $sql, $parameters = array()) {
    // HTML-Special-Chars werden bereinigt um Template-XSS zu vermeiden
    foreach ($parameters as $key => $value) {
      if ($value !== NULL) {
        $parameters[$key] = htmlspecialchars($value);
      }
    }

    // Prepared-Queries werde verwendet
    $stmt = $connection->prepare($sql);
    $stmt->execute($parameters);

    return $stmt->rowCount();
  }

  function dayNumberToDayShort($day) {
    $days = ["Mo.","Di.","Mi.","Do.","Fr.","Sa.","So."];
    return $days[$day];
  }

  function nullEmptyString($arr = array()) {
    foreach ($arr as $key => $value) {
      if ($value == '') {
        $arr[$key] = NULL;
      }
    }
    return $arr;
  }
?>
