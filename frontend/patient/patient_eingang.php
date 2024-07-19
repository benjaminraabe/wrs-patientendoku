<!-- Sichtungskategorien -->
<h4 class="pl-4">Sichtung</h4>
<div class="grid pl-4 pr-4">
  <div class="row buttonwrapper buttongroup">
    <div class="cell">
      <button type="button" data-caption="Sofort (S1)" data-text="Sofort" class="bg-prio1 js-sichtk
        <?php if($patientendaten["SICHTUNGSKATEGORIE"] === "SOFORT"){echo "active";} ?>">
      </button>
    </div>
    <div class="cell">
      <button type="button" data-caption="< 10min (S1)" data-text="Sehr Dringend" class="bg-prio2 js-sichtk
        <?php if($patientendaten["SICHTUNGSKATEGORIE"] === "SEHR DRINGEND"){echo "active";} ?>">
      </button>
    </div>
    <div class="cell">
      <button type="button" data-caption="< 30min (S2)" data-text="Dringend" class="bg-prio3 js-sichtk
        <?php if($patientendaten["SICHTUNGSKATEGORIE"] === "DRINGEND"){echo "active";} ?>">
      </button>
    </div>
    <div class="cell">
      <button type="button" data-caption="< 90min (S3)" data-text="Normal" class="bg-prio4 js-sichtk
        <?php if($patientendaten["SICHTUNGSKATEGORIE"] === "NORMAL"){echo "active";} ?>">
      </button>
    </div>
    <div class="cell">
      <button type="button" data-caption="< 120min (S3)" data-text="Nicht Dringend" class="bg-prio5 js-sichtk
        <?php if($patientendaten["SICHTUNGSKATEGORIE"] === "NICHT DRINGEND"){echo "active";} ?>">
      </button>
    </div>
  </div>
</div>



<!-- Abteilungen der UHS -->
<h4 class="pl-4 mt-8">Abteilung</h4>
<div class="grid pl-4 pr-4 buttongroup">
  <!-- Jede UHS wird als Zeile gerendert, jede zugehörige Abteilung als Zelle in dieser Zeile. -->
  <?php foreach ($uhsen as $uhs):?>
    <div class="row buttonwrapper pb-3">
      <?php foreach ($abteilungen as $row):?>
        <?php if ($uhs["UHST_ID"] === $row["UHST_ID"]): ?>
          <div class="cell">
            <button
              type="button"
              class="js-bereich <?php if($row["BEREICH_ID"] === $patientendaten["BEREICH_ID"]) {echo "active";} ?>"
              data-caption="<?php echo $row["UHS_NAME"]; ?>"
              data-text="<?php echo $row["ABT_NAME"]; ?>"
              data-value="<?php echo $row["BEREICH_ID"]; ?>">
            </button>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>


<!-- Formular Eingangsdaten -->
<h4 class="pl-4 mt-8">Eingangsdaten</h4>
<div class="grid pl-4 pr-4 buttongroup">
  <div class="row buttonwrapper mb-3">
      <div class="cell">
        <button type="button" class="js-eingangsart <?php if($patientendaten["EINGANGSART"] != 1 && $patientendaten["EINGANGSART"] != 2) {echo "active";} ?>" data-caption="Eingangsart" data-text="Unbekannt" data-value="0"></button>
      </div>
      <div class="cell">
        <button type="button" class="js-eingangsart <?php if($patientendaten["EINGANGSART"] == 1) {echo "active";} ?>" data-caption="Eingangsart" data-text="Selbstständig" data-value="1"  ></button>
      </div>
      <div class="cell">
        <button type="button" class="js-eingangsart <?php if($patientendaten["EINGANGSART"] == 2) {echo "active";} ?>" data-caption="Eingangsart" data-text="Durch SAN-Dienst" data-value="2"></button>
      </div>
  </div>
  <div class="row textfieldwrapper" style="border-top: 1px solid black;">
    <div id="form-Pzc" class="cell cell-3 field text-center" data-caption="PZC">
      <input id="pzc-input" type="text" class="input" onfocus="document.activeElement.blur();openPzcSuche();" value="<?php echo $patientendaten["PZC"];?>" tabIndex="0">
    </div>
    <div  id="form-beschreibung-wrapper" class="cell field text-center" data-caption="Beschreibung" onclick="openPzcSuche();">
      <span id="pzc-beschreibung">
      <?php if (array_key_exists("DESCRIPTION", $patientendaten)) {echo $patientendaten["DESCRIPTION"];}?>
      </span>
    </div>
  </div>
  <div class="row textfieldwrapper">
    <div class="cell field p-2" data-caption="Sonstige Eigenschaften">
      <input id="cbInfekt" type="checkbox" data-role="checkbox" data-caption="Infektiös" <?php if($patientendaten["INFEKT"] == 1) {echo "checked";} ?>>
      <input id="cbGewicht" type="checkbox" data-role="checkbox" data-caption="Adipös (>150kg)" <?php if($patientendaten["UEBERSCHWER"] == 1) {echo "checked";} ?>>
    </div>
  </div>
  <div class="row">
    <div class="cell field" data-caption="Verknüpfte Protokolle (Bestätigen mit Enter)">
      <input id="eProtokollVerkn" type="text" data-role="taginput" data-tag-trigger="Enter" value="<?php echo implode(",", $linked_nrs); ?>">
    </div>
  </div>
</div>

<!-- Zeiten nachtragen -->
<?php if (($patientendaten["AKTIV"] == 0)
          && (in_array("PERM_LATE_ENTER_PATIENTS", $_SESSION["PERMISSIONS"], true) ||
              in_array("PERM_CHANGE_ARCHIVED_PATIENT_DATA", $_SESSION["PERMISSIONS"], true))):?>
<div class="pl-4 pr-4 mt-3">
  <button class="button alert outline" onclick="openTimeEditDlg();">Zeiten editieren</button>
</div>
<?php endif; ?>



<!-- PZC-Dialog -->
<div class="dialog" data-role="dialog" id="pzcDialog">
  <div class="dialog-title">PZC auswählen</div>
  <div class="dialog-content" style="overflow-y: scroll;">

    <input id="i-pzcsuche" type="text" data-role="input" class="primary mb-3" data-search-button="true" onkeyup="pzcSuche(this)">

    <div class="grid pl-0 pr-0 mb-3 pzctable">
        <?php $lastcategory = ''; ?>
        <?php foreach ($pzcs as $row):?>
          <?php if ($lastcategory != $row["CAT_DESCRIPTION"]) : ?>
            <?php $lastcategory = $row["CAT_DESCRIPTION"]; ?>
            <div class="row fg-light" style="background-color: #999;">
              <div class="cell p-0 pl-2"><?php echo $row["CAT_DESCRIPTION"]; ?></div>
            </div>
          <?php endif; ?>
          <div class="row" onclick="selectPZC('<?php echo $row["PZC"]; ?>', '<?php echo htmlspecialchars($row["DESCRIPTION"]); ?>');" style="border-bottom: 1px solid lightgray;">
            <div class="cell cell-2 text-center">
              <?php echo $row["PZC"] ?> <br>
            </div>
            <div class="cell cell-10">
              <?php echo htmlspecialchars($row["DESCRIPTION"]); ?>
            </div>
            <div style="display: none;"><?php echo $row["CAT_DESCRIPTION"]; ?></div>
          </div>
        <?php endforeach; ?>
    </div>
  </div>
  <div class="dialog-actions">
      <button class="button js-dialog-close place-right">Schließen</button>
  </div>
</div>
