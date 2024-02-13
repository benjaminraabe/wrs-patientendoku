<?php
  // Weiterleitung auf die Login- oder Index-Seite
  // Dieser Block wird nur implementiert, damit man aus dem Root-Verzeichnis
  //    der Website direkt auf die Software zugreifen kann.
  // Alle relevanten Seiten sind im Ordner "frontend" angesiedelt.
  header('Location: frontend/index.php');
  die();
 ?>
