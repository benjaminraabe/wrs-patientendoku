<?php
  include_once '../backend/sessionmanagement.php';

  $accessible_to = array("ADMIN", "TEL", "SICHTER", "ARZT", "MONITOR"); // Whitelist für Benutzerrollen

  if (!in_array($_SESSION["USER_ROLE"], $accessible_to, true)) { // Aktiver strict-mode!
    echo "Zugriff verweigert.";
    exit();
  }
?>

<?php
  include_once '../backend/db.php';

  // Alle bisher Transportierten Patienten (Hinterlegter Rufname)
  $query = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN
                              WHERE TRANSPORT_RUFNAME IS NOT NULL;");
  if (count($query) == 0) {$query = array(array("GES" => 0));}
  $transporte_gesamt = $query[0]["GES"];

  // Transportierte Patienten der letzten Schicht (Beginn letzte Schicht bis Beginn diese Schicht)
  $query = safeQuery($conn, 'SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN
                              WHERE TRANSPORT_RUFNAME IS NOT NULL
                              AND ZEIT_ENTLASSUNG >
                                ADDTIME(IF(CURRENT_TIME() < "20:0:0",
                                	IF(CURRENT_TIME() < "8:0:0",
                                		(ADDTIME(DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY) , "20:0:0")),
                                		(ADDTIME(CURRENT_DATE(), "8:0:0"))),
                                	(ADDTIME(CURRENT_DATE(), "20:0:0"))), "-12:0:0")
                              AND ZEIT_ENTLASSUNG <
                                IF(CURRENT_TIME() < "20:0:0",
                                	IF(CURRENT_TIME() < "8:0:0",
                                		(ADDTIME(DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY) , "20:0:0")),
                                		(ADDTIME(CURRENT_DATE(), "8:0:0"))),
                                	(ADDTIME(CURRENT_DATE(), "20:0:0")));');
  if (count($query) == 0) {$query = array(array("GES" => 0));}
  $transporte_letzte_schicht = $query[0]["GES"];

  // Transportierte Patienten der aktuellen Schicht
  $query = safeQuery($conn, 'SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN
                              WHERE TRANSPORT_RUFNAME IS NOT NULL
                              AND ZEIT_ENTLASSUNG >
                                IF(CURRENT_TIME() < "20:0:0",
                                	IF(CURRENT_TIME() < "8:0:0",
                                		(ADDTIME(DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY) , "20:0:0")),
                                		(ADDTIME(CURRENT_DATE(), "8:0:0"))),
                                	(ADDTIME(CURRENT_DATE(), "20:0:0")));');
  if (count($query) == 0) {$query = array(array("GES" => 0));}
  $transporte_diese_schicht = $query[0]["GES"];


  // Gesamtzahl Patienten
  $query = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN");
if (count($query) == 0) {$query = array(array("GES" => 0));}
  $patienten_gesamt = $query[0]["GES"];

  // Transportierte Patienten der letzten Schicht (Beginn letzte Schicht bis Beginn diese Schicht)
  $query = safeQuery($conn, 'SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN
                              WHERE ZEIT_EINGANG >
                                ADDTIME(IF(CURRENT_TIME() < "20:0:0",
                                	IF(CURRENT_TIME() < "8:0:0",
                                		(ADDTIME(DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY) , "20:0:0")),
                                		(ADDTIME(CURRENT_DATE(), "8:0:0"))),
                                	(ADDTIME(CURRENT_DATE(), "20:0:0"))), "-12:0:0")
                              AND ZEIT_EINGANG <
                                IF(CURRENT_TIME() < "20:0:0",
                                	IF(CURRENT_TIME() < "8:0:0",
                                		(ADDTIME(DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY) , "20:0:0")),
                                		(ADDTIME(CURRENT_DATE(), "8:0:0"))),
                                	(ADDTIME(CURRENT_DATE(), "20:0:0")));');
  if (count($query) == 0) {$query = array(array("GES" => 0));}
  $patienten_letzte_schicht = $query[0]["GES"];

  // Transportierte Patienten der aktuellen Schicht
  $query = safeQuery($conn, 'SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN
                              WHERE ZEIT_EINGANG >
                                IF(CURRENT_TIME() < "20:0:0",
                                	IF(CURRENT_TIME() < "8:0:0",
                                		(ADDTIME(DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY) , "20:0:0")),
                                		(ADDTIME(CURRENT_DATE(), "8:0:0"))),
                                	(ADDTIME(CURRENT_DATE(), "20:0:0")));');
  if (count($query) == 0) {$query = array(array("GES" => 0));}
  $patienten_diese_schicht = $query[0]["GES"];

  // Aktuell aktive Patienten
  $query = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN WHERE AKTIV = 1;");
  if (count($query) == 0) {$query = array(array("GES" => 0));}
  $aktive_patienten = $query[0]["GES"];

  // Auslastung nach SK
  $query = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN
                              WHERE AKTIV = 1 AND SICHTUNGSKATEGORIE = 'SOFORT';");
  if (count($query) == 0) {$query = array(array("GES" => 0));}
  $aktive_rot = $query[0]["GES"];

  $query = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN
                              WHERE AKTIV = 1 AND SICHTUNGSKATEGORIE = 'SEHR DRINGEND';");
  if (count($query) == 0) {$query = array(array("GES" => 0));}
  $aktive_orange = $query[0]["GES"];

  $query = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN
                              WHERE AKTIV = 1 AND SICHTUNGSKATEGORIE = 'DRINGEND';");
  if (count($query) == 0) {$query = array(array("GES" => 0));}
  $aktive_gelb = $query[0]["GES"];

  $query = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN
                              WHERE AKTIV = 1 AND SICHTUNGSKATEGORIE = 'NORMAL';");
  if (count($query) == 0) {$query = array(array("GES" => 0));}
  $aktive_gruen = $query[0]["GES"];

  $query = safeQuery($conn, "SELECT COUNT(PATIENTEN_ID) AS GES FROM PATIENTEN
                              WHERE AKTIV = 1 AND SICHTUNGSKATEGORIE = 'NICHT DRINGEND';");
  if (count($query) == 0) {$query = array(array("GES" => 0));}
  $aktive_blau = $query[0]["GES"];

  $monitor_nachricht = safeQuery($conn, "SELECT ITEM FROM MONITOR_STRINGS;");
  if (count($monitor_nachricht) > 0) {
    $monitor_nachricht = $monitor_nachricht[0]["ITEM"];
  } else {
    $monitor_nachricht = "";
  }
?>

<?php
  include_once '../config.php';
  $PAGE_TITLE = "<span id='page-timedisplay'>XX:XX</span> Uhr";
 ?>






<!DOCTYPE html>
<html lang="de" dir="ltr">
  <head>
    <title> <?php echo "Monitor | " . $ORG_NAME; ?> </title>
    <meta charset="utf-8">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="../styles/metro-all.min.css">
    <script src="../scripts/metro.min.js" charset="utf-8"></script>
    <script src="../scripts/plotly.js" charset="utf-8"></script>
    <script src="../scripts/monitor.js" charset="utf-8"></script>
    <link rel="stylesheet" href="../styles/page.css">
    <link rel="stylesheet" href="../styles/monitor.css">
  </head>
  <body>
    <div class="page-wrapper">
      <?php include_once '../modules/header.php'; ?>

      <div class="p-4 pt-1">

        <!-- Aktuelle Auslastung -->
        <div id="stat-aktuell" class="mt-4">

          <div class="grid pr-2">
            <div class="row">
              <div class="cell pt-0 pb-0 mr-3">
                <div class="icon-box border bd-default mb-4">
                    <div class="icon bg-cyan fg-white"><span class="mif-ambulance"></span></div>
                    <div class="content p-4 pl-6">
                        <div class="text-upper text-bold text-lead">Transporte</div>
                        <div class="grid">
                          <div class="row">
                            <div class="cell">
                              Gesamt: <br>
                              Letzte Schicht: <br>
                              Diese Schicht:
                            </div>
                            <div class="cell">
                              <?php echo $transporte_gesamt; ?> <br>
                              <?php echo $transporte_letzte_schicht; ?> <br>
                              <?php echo $transporte_diese_schicht; ?>
                            </div>
                          </div>
                        </div>
                    </div>
                </div>
                <div class="icon-box border bd-default">
                    <div class="icon bg-cyan fg-white"><span class="mif-user"></span></div>
                    <div class="content p-4 pl-6">
                        <div class="text-upper text-bold text-lead">Patienten</div>
                        <div class="grid">
                          <div class="row">
                            <div class="cell">
                              Gesamt: <br>
                              Letzte Schicht: <br>
                              Diese Schicht:
                            </div>
                            <div class="cell">
                              <?php echo $patienten_gesamt; ?> <br>
                              <?php echo $patienten_letzte_schicht; ?> <br>
                              <?php echo $patienten_diese_schicht; ?>
                            </div>
                          </div>
                        </div>
                    </div>
                </div>
              </div>
              <div class="cell p-1" style="border: 1px solid rgb(223, 223, 223); text-align: center; box-sizing: border-box;">
                <svg xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    version="1.1" baseProfile="full"
                    width="500px" height="420px" viewBox="0 0 500 420" style="max-height: 245px; margin: 0 auto;">

                  <path style="stroke: rgb(0, 0, 0); fill: rgb(226, 226, 226);" transform="matrix(0.474637, 0, 0, 0.474754, -209.948318, 52.537235)" d="M 598.961418271 771.376415846 A 513.024 513.024 0 0 1 503.57351621 197.051231408 L 681.905848267 280.740068132 A 316.031 316.031 0 0 0 740.666323559 634.533579475 Z">
                    <title>Kreissegment_weiß</title></path>
                  <path style="stroke: rgb(0, 0, 0); fill: rgb(73, 150, 66);" transform="matrix(0.474637, 0, 0, 0.474754, -209.948318, 52.537235)" d="M 515.764440859 172.767418326 A 513.024 513.024 0 0 1 1418.811538945 170.127418991 L 1245.707127667 264.154568502 A 316.031 316.031 0 0 0 689.415653087 265.780850372 Z">
                    <title>Kreissegment_gruen</title>
                  </path>
                  <path style="stroke: rgb(0, 0, 0); fill: rgb(234, 227, 35);" transform="matrix(0.474637, 0, 0, 0.474754, -209.948318, 52.537235)" d="M 1430.932206102 193.895054037 A 513.024 513.024 0 0 1 1431.240475491 635.458355348 L 1253.363551627 550.805877501 A 316.031 316.031 0 0 0 1253.173652747 278.795812325 Z">
                    <title>Kreissegment_gelb</title>
                  </path>
                  <path style="stroke: rgb(0, 0, 0); paint-order: fill; fill: rgb(190, 36, 36);" transform="matrix(0.474637, 0, 0, 0.474754, -209.948318, 52.537235)" d="M 1419.153006017 659.242890865 A 513.024 513.024 0 0 1 1342.198985895 765.954047606 L 1198.51256805 631.1933138 A 316.031 316.031 0 0 0 1245.917476852 565.457532285 Z">
                    <title>Kreissegment_rot</title>
                  </path>
                  <path style="stroke-width: 6px; stroke: rgb(0, 0, 0); fill: rgba(222, 244, 25, 0);" d="M 148.38 738.014 C 334.155 738.014 450.264 536.764 357.377 375.765 C 264.489 214.764 32.27 214.764 -60.617 375.765 C -81.307 411.623 -92.433 452.202 -92.931 493.606" transform="matrix(0.702401, -0.711782, 0.711782, 0.702401, -207.739448, 6.222102)">
                    <title>tacho_outer_rim</title>
                  </path>
                  <circle style="stroke: rgb(0, 0, 0);" transform="matrix(1, 0, 0, 0.999998, -281.5, -272.498932)" cx="531.5" cy="522.5" r="15">
                    <title>tacho_middle</title>
                  </circle>

                  <path d="M 249.821 52 L 255.737 241.855 L 243.905 241.855 L 249.821 52 Z" style="stroke: rgb(0, 0, 0);" id="svg_tacho_nadel">
                    <title>tacho_nadel</title>
                  </path>

                  <line style="stroke-width: 6px; fill: rgb(200, 38, 38); stroke: rgb(0, 0, 0);" x1="114.771" y1="381.145" x2="74.267" y2="418.948"><title>l_links</title></line>
                  <line style="fill: rgb(216, 216, 216); stroke-width: 6px; stroke: rgb(0, 0, 0);" x1="426.723" y1="418.489" x2="386.907" y2="381.175" transform="matrix(-1, 0, 0, -1, 812.703975, 799.849972)"><title>l_rechts</title></line>
                </svg>
              </div>
            </div>
          </div>

          <!-- <h4>Aktuelle Auslastung</h4> -->
          <div id="plot-aktuell" class="" style="width: 100%; background-color: lightgray;"></div>

          <script type="text/javascript">
            // Aktualisiert die Uhr in der Kopfzeile alle 10 Sekunden
            page_header_updateTime()
            setInterval(page_header_updateTime, 10000)
            // document.getElementById("svg_tacho_nadel").setAttribute("d", arrowFromPatNr(150))
            document.getElementById("svg_tacho_nadel").setAttribute("d", arrowFromPatNr(<?php echo $aktive_patienten; ?>))
            setTimeout(() => {window.location.reload()}, 60000);



          </script>
          <script type="text/javascript">
            var trace1 = {
              x: ["(S3) Nicht Dringend"],
              y: [<?php echo $aktive_blau; ?>],
              type: 'bar',
              text: ["<?php if ($aktive_blau > 1){echo $aktive_blau." Patienten";}
                                 elseif ($aktive_blau == 1) {echo $aktive_blau." Patient";} ?>"],
              marker: {color: 'rgba(130, 130, 255, 0.4)'},
              insidetextanchor: "middle"
            };
            var trace2 = {
              x: ["(S3) Normal"],
              y: [<?php echo $aktive_gruen; ?>],
              type: 'bar',
              text: ["<?php if ($aktive_gruen > 1){echo $aktive_gruen." Patienten";}
                                 elseif ($aktive_gruen == 1) {echo $aktive_gruen." Patient";} ?>"],
              marker: {color: 'rgba(0, 255, 0, 0.3)'},
              insidetextanchor: "middle"
            };
            var trace3 = {
              x: ["(S2) Dringend"],
              y: [<?php echo $aktive_gelb; ?>],
              type: 'bar',
              text: ["<?php if ($aktive_gelb > 1){echo $aktive_gelb." Patienten";}
                                 elseif ($aktive_gelb == 1) {echo $aktive_gelb." Patient";} ?>"],
              marker: {color: 'rgba(255, 255, 0, 0.4)'},
              insidetextanchor: "middle"
            };
            var trace4 = {
              x: ["(S1) Sehr Dringend"],
              y: [<?php echo $aktive_orange; ?>],
              type: 'bar',
              text: ["<?php if ($aktive_orange > 1){echo $aktive_orange." Patienten";}
                                 elseif ($aktive_orange == 1) {echo $aktive_orange." Patient";} ?>"],
              marker: {color: 'rgba(255, 128, 0, 0.2)'},
              insidetextanchor: "middle"
            };
            var trace5 = {
              x: ["(S1) Sofort"],
              y: [<?php echo $aktive_rot; ?>],
              type: 'bar',
              text: ["<?php if ($aktive_rot > 1){echo $aktive_rot." Patienten";}
                                 elseif ($aktive_rot == 1) {echo $aktive_rot." Patient";} ?>"],
              marker: {color: 'rgba(255, 0, 0, 0.2)'},
              insidetextanchor: "middle"
            };

            var data = [trace1, trace2, trace3, trace4, trace5];

            Plotly.newPlot('plot-aktuell', data, {
              showlegend: false,
              title: {text: "Aktuelle Auslastung", font: {family: 'Segoe UI', size: 24}},
              yaxis: {range: [0, <?php echo max(array($aktive_blau, $aktive_gruen, $aktive_gelb, $aktive_orange, $aktive_rot, 5));?>]}
            }, {displayModeBar: false});
          </script>
        </div>
      </div>
      <script type="text/javascript">
      </script>
      <!-- Liveticker -->
      <div class="ticker-wrapper">
        <div class="liveticker">
          <div class="ticker-item">
            <?php echo $monitor_nachricht; ?>
          </div>
          <!-- <div class="ticker-item">
            Lorem ipsum dolor sit amet, consectetur adipisicing elit.
          </div> -->
          <!-- <div class="ticker-item">
            Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
          </div>
          <div class="ticker-item">
            Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.
          </div>
          <div class="ticker-item">
            Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
          </div> -->
        </div>
      </div>



      <?php include_once '../modules/footer.php'; ?>
    </div>
  </body>
</html>
