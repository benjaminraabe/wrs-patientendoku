<?php
  // Implementiert eine Reihe von Utility-Funktionen, die von anderen
  //    importiert werden können.
  // Implementiert insbesondere zwei Wrapper für SQL-Queries mit PDO.


  function connectToDB($servername, $username, $password, $dbname) {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    return $pdo;
  }

  // SQLi-sicherer Query -> Als Array of Assoziativ-Array
  // Natürlich nur sicher, wenn man auch "?"-Platzhalter verwendet!!
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
  // Natürlich nur sicher, wenn man auch "?" Platzhalter verwendet!!
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
    $days = ["So.","Mo.","Di.","Mi.","Do.","Fr.","Sa."];
    return $days[$day];
  }

  // Entfernt leere Strings aus dem Array, indem der assoziierte Eintrag
  //    auf NULL gesetzt wird.
  // Wird in Kombination mit inputDiff verwendet.
  function nullEmptyString($arr = array()) {
    foreach ($arr as $key => $value) {
      if ($value == '') {
        $arr[$key] = NULL;
      }
    }
    return $arr;
  }

  // Gleicht zwei Datensätze (assoziative Arrays) miteinander ab und produziert
  //    einen String mit den Änderungen.
  function inputDiff($oldData, $newData) {
    $changes = array();
    foreach ($newData as $key => $value) {
      if(array_key_exists($key, $oldData)) {
        // Sonderfall Geburtsdatum: Standardwert aus der DB abfangen
        if ($key == "DOB" && $oldData["DOB"] == "0000-00-00" && $value == "") {
          continue;
        }
        if ($oldData[$key] != $value) {
          array_push($changes, "[".$key."] " . $oldData[$key] . " ↦ " . $value);
        }
      }
    }
    return implode("\n", $changes);
  }
?>
