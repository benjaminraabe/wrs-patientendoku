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

  // Wandelt einen gegebenen Timestamp in eine String-Darstellung der Uhrzeit im Format "01:23 Uhr" um.
  function timeToClock($timestamp) {
    if (is_null($timestamp)) {
      return "##:## Uhr";
    } else {
      return date("H:i", strtotime($timestamp)) . " Uhr";
    }
  }

  // Wandelt einen gegebenen Anzahl Sekunden in eine Repräsentation in Stunden/Minuten um.
  function secondsToStr($seconds) {
    $res = "";
    if ($seconds >= 3600) {
      $res = $res . floor($seconds / 3600) . "h ";
      $seconds = ($seconds % 3600);
    }
    $res = $res . floor($seconds / 60) . "min";
    return $res;
  }

  // Gleicht zwei Datensätze (assoziative Arrays) miteinander ab und produziert
  //    einen String mit den Änderungen.
  function inputDiff($oldData, $newData) {
    $changes = array();
    // Verhindert wiederholte Änderungsvermerke, wenn bereits HTML-Chars eingefügt wurden.
    //  Diese werden in der SafeInsert-Funktion escaped und müssen zum Vergleich zurückkonvertiert werden.
    $oldData = array_map('htmlspecialchars_decode', $oldData);
    $newData = array_map('htmlspecialchars_decode', $newData);

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


  function safeCharsOnly($str){
    $allowed = "ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÜabcdefghijklmnopqrstuvwxyzäöüß1234567890 +-_,.;:/&%!?()[]{}=#*~^°";
    $allowed = str_split($allowed);
    $str = str_split($str);

    $newstr = "";
    foreach ($str as $chr) {
      if (in_array($chr, $allowed)) {
        $newstr = $newstr.$chr;
      }
    }
    return $newstr;
  }
?>
