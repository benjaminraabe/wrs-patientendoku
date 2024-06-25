<?php
  // Übersichtsseite für Patienten, für die bereits ein Transport angefordert,
  //    aber noch keine Entlassung vermerkt worden ist.
  // Filter nach einer bestimmten UHS ist auch hier durch Einschränkung der
  //    Datenquellen beim Login möglich.


  include_once '../backend/sessionmanagement.php';

  $accessible_to = array("ADMIN", "TEL", "SICHTER"); // Whitelist für Benutzerrollen.

  if (!in_array($_SESSION["USER_ROLE"], $accessible_to, true)) { // Aktiver strict-mode!
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../backend/db.php';

  $transporte = safeQuery($conn, "SELECT
                                  	p.PATIENTEN_ID,
                                  	af.`TIMESTAMP`,
                                  	p.TRANSPORTKATEGORIE,
                                  	ud.NAME as UHSNAME,
                                  	pz.PZC,
                                  	pz.DESCRIPTION,
                                  	p.INFEKT,
                                  	p.UEBERSCHWER
                                  FROM PATIENTEN p
                                  LEFT JOIN BEREICHE b
                                  	ON b.BEREICH_ID = p.BEREICH_ID
                                  LEFT JOIN UHS_DEFINITION ud
                                  	ON ud.UHST_ID = b.UHST_ID
                                  LEFT JOIN PZC pz
                                  	ON pz.PZC = p.PZC
                                  LEFT JOIN (SELECT PATIENTEN_ID, `TIMESTAMP` FROM PATIENTENVERLAUF
                                  			WHERE EINTRAG LIKE 'Transport in der Kategorie%') af
                                  	ON p.PATIENTEN_ID = af.PATIENTEN_ID
                                  WHERE TRANSPORTKATEGORIE IS NOT NULL
                                  	AND ZEIT_ENTLASSUNG IS NULL
                                    AND ((b.UHST_ID = ? OR NULL <=> ?) OR p.BEREICH_ID IS NULL)
                                  ORDER BY UHSNAME ASC, af.`TIMESTAMP` ASC;",[$_SESSION['UHS'], $_SESSION['UHS']]);

  // Halte den Zeitpunkt der Datenabfrage fest.
  $data_timestamp = time();
  $time_last_quittance = 0;
  if(array_key_exists("lastQuittance", $_GET)) {
    $time_last_quittance = $_GET["lastQuittance"];
  }

  $newest_transport = 0;
  $count_new_transports = 0;
  foreach ($transporte as $key => $t) {
    $ts = strtotime($t["TIMESTAMP"]);
    // Findet den Unix-Epock-Zeitstempel der neusten Transportanforderung.
    if ($ts > $newest_transport) {
      $newest_transport = $ts;
    }
    // Zählt "unquittierte" Transporte und markiert diese
      if ($ts > $time_last_quittance && $time_last_quittance > -1) { // Edge-Case: Erster Aufruf
        $transporte[$key]["IS_NEW"] = True;
        $count_new_transports++;
      } else {
        $transporte[$key]["IS_NEW"] = False;
      }
  }
 ?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "Aktive Transportanforderungen";
?>



<!DOCTYPE html>
<html lang="de" dir="ltr">
  <head>
    <title> <?php echo $PAGE_TITLE . " | " . $ORG_NAME; ?> </title>
    <meta charset="utf-8">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="../styles/metro-all.min.css">
    <script src="../scripts/metro.min.js" charset="utf-8"></script>
    <link rel="stylesheet" href="../styles/page.css">
    <link rel="stylesheet" href="../styles/patliste.css">
  </head>
  <body>
    <div class="page-wrapper">
      <?php include_once '../modules/header.php'; ?>


      <!-- Meldung über unquittierte Transporte -->
      <?php if ($count_new_transports > 0) : ?>
        <div id="transport_alert" class="meldung mb-7">
          <?php echo $count_new_transports ?> neue Transportanforderung<?php if($count_new_transports > 1) echo "en" ?>
          <button class="button secondary outline ml-5" onclick="confirmTransports()">Quittieren</button>
        </div>
      <?php endif; ?>

      <div class="grid patliste-liste pl-3 pr-3">
        <div class="row header-row fg-light" style="background-color: #024ea4;">
          <div class="cell-2 text-center"><b>#</b></div>
          <div class="cell"><b>Zeit</b></div>
          <div class="cell"><b>Anforderung</b></div>
          <div class="cell"><b>Bemerkungen</b></div>
        </div>

        <!-- Keine vorhandenen Transporte. -->
        <?php if (count($transporte) == 0) : ?>
        <div class="row text-center fg-light" style="background-color: #999999;">
          <div class="cell p-0 pb-1">Keine offenen Transporte.</div>
        </div>
        <?php endif; ?>


        <!-- Transporte werden nach der UHS und dem Zeitpunkt der Anforderung gelistet -->
        <?php if (count($transporte) > 0) : ?>
          <!-- Trenner "Unbekannte UHS einfügen" -->
          <?php if ($transporte[0]["UHSNAME"] == ""): ?>
            <div class="row divider-row text-center fg-light" style="background-color: #999999;">
              <div class="cell-2 p-0 pb-1">Unbekannt</div>
            </div>
          <?php endif; ?>

          <?php $lastcategory = ""; ?>
          <?php foreach ($transporte as $transport) :?>

            <!-- Ggf. Sektionstrenner schreiben -->
            <?php if ($lastcategory != $transport["UHSNAME"]) : ?>
              <?php $lastcategory = $transport["UHSNAME"]; ?>
              <div class="row divider-row text-center fg-light" style="background-color: #999999;">
                <div class="cell-2 p-0 pb-1"><?php echo $transport["UHSNAME"]; ?></div>
              </div>
            <?php endif; ?>

            <div class="row data-row" onclick="window.location.href = 'patient.php?id=<?php echo $transport["PATIENTEN_ID"] ?>'">
              <div class="cell-2 text-center" lang="de" style="line-height: 60px;">
                <?php if ($transport["IS_NEW"] === True): ?>
                  <span class='newtransportwarning mif-warning mr-2' style="color:#e3a21a"></span>
                <?php endif; ?>
                <?php echo $transport["PATIENTEN_ID"]; ?>
              </div>
              <div class="cell pt-3">
                <?php echo date("d.m.Y", strtotime($transport["TIMESTAMP"])); ?>
                <br>
                <?php echo date("H:i", strtotime($transport["TIMESTAMP"]))."  Uhr"; ?>
              </div>
              <div class="cell pt-3">
                <?php echo $transport["TRANSPORTKATEGORIE"]; ?>
                <br>
                <?php
                  if ($transport["PZC"] != "") {echo $transport["PZC"] . " - " . $transport["DESCRIPTION"];} ?>

              </div>
              <div class="cell cell-permissions" style="line-height: 60px;">
                <?php if ($transport["INFEKT"] == 1) : ?>
                  <span> Infekt</span>
                <?php endif; ?>
                <?php if ($transport["UEBERSCHWER"] == 1) : ?>
                  <span> >150kg</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach ?>
        <?php endif; ?>

      </div>


      <?php include_once '../modules/footer.php'; ?>
    </div>

    <script type="text/javascript">
      setTimeout(() => {
        localStorage.setItem("lastUpdateTime", <?php echo $data_timestamp; ?>)
        window.location.href = window.location.href.split("?")[0] + '?lastQuittance=' + (localStorage.getItem("lastConfirmedTime") || 0)
      }, 10000);

      let lastUpdateTime = localStorage.getItem("lastUpdateTime")
      let lastConfirmedTime = localStorage.getItem("lastConfirmedTime") || 0
      // Fenster wurde noch nie geöffnet, kein Ton.
      if (!lastUpdateTime) {}
      else {
        // Spielt einen Ton, wenn seit der letzten Aktualisierung ein neuer Transport angefordert wurde.
        if (<?php echo $newest_transport;  ?> > lastUpdateTime) {
          new Audio('../mif/sound.mp3').play()
        }
      }


      function confirmTransports() {
        // Timestamp wird als UNIX-Timestamp (Anzahl Sekunden, anstatt Anzahl Millisekunden) gespeichert
        //    Damit es mit dem PHP-Timestamp kompatibel ist.
        localStorage.setItem("lastConfirmedTime", Math.floor(Date.now()/1000))
        document.getElementById('transport_alert').style.display = "none";
        for (const el of document.getElementsByClassName('newtransportwarning')) {
          el.style.display = "none";
        }
      }

    </script>
  </body>
</html>
