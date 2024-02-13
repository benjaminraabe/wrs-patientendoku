<?php
  // Wird bei explizitem Log-Out aus dem Menü aufgerufen.
  // Sorgt dafür, dass die Session zerstört und verbleibende Cookies und
  //    Session-Daten entfernt werden.
  session_start();
  setcookie(session_name(), '', 100);
  session_unset();
  session_destroy();
  $_SESSION = array();

  header('Location: ../frontend/login.php');
  exit();
?>
