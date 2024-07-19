<?php
  // Verbindungsdaten für die Datenbank. Kann alternativ aus ENV-Variablen
  //    des Servers geladen werden. Da "einfache" Webhoster meist keinen
  //    Zugriff auf die Serverconf erlauben, werden sie hier standardmäßig
  //     als Variablen angeboten.
  $db_servername = "";
  $db_username = "";
  $db_password = "";
  $db_dbname = "";


  // Name der Organisation. Wird im Titel der Website angezeigt.
  $ORG_NAME = "W:R:S";


  // Optional können die Benutzernamen mit ihren zugehörigen Rollen auf der Login-Seite
  //    als Select-Box angezeigt werden. Standardmäßig ist diese Einstellung deaktiviert.
  // Wird die Option aktiviert, exponiert das die Benutzernamen an einen möglichen Angreifer.
  //    Grundsätzlich wird das nicht empfohlen, wenn es aber unbedingt gewünscht ist,
  //    kann es hier aktiviert werden.
  $SHOW_USERNAMES_ON_LOGIN = False;

  // Session-Lifetime in Sekunden.
  //    Standardmäßig auf 12 Stunden gesetzt
  $CST_SESSION_LIFETIME = 12 * 60 * 60;
 ?>
