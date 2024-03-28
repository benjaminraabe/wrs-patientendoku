<!-- Patientendaten -->
<h4 class="pl-4">Patientendaten</h4>
<div class="grid pl-4 pr-4">
  <div class="row textfieldwrapper">
    <div id="form-Name" class="cell field" data-caption="Name">
      <input type="text" class="input" value="<?php echo $patientendaten["NAME"];?>">
    </div>
    <div id="form-Vname" class="cell field" data-caption="Vorname">
      <input type="text" class="input" value="<?php echo $patientendaten["VORNAME"];?>">
    </div>
    <div id="form-Geburtsdatum" class="cell field" data-caption="Geburtsdatum" data-value="<?php echo $patientendaten["DOB"];?>">
      <input type="date" class="input" value="<?php echo $patientendaten["DOB"];?>">
    </div>
    <div id="form-Geschlecht" class="cell field" data-caption="Geschlecht" data-value="<?php echo $patientendaten["GESCHLECHT"];?>">
      <select id="form-geschlecht-select" data-role="select" data-filter="False" class="geschlechtSelect">
        <option value="" <?php if ($patientendaten["GESCHLECHT"] == ''){echo ' selected';}?>>Keine Angabe</option>
        <option value="M"<?php if ($patientendaten["GESCHLECHT"] == 'M'){echo ' selected';}?>>Männlich</option>
        <option value="W"<?php if ($patientendaten["GESCHLECHT"] == 'W'){echo ' selected';}?>>Weiblich</option>
        <option value="D"<?php if ($patientendaten["GESCHLECHT"] == 'D'){echo ' selected';}?>>Divers</option>
      </select>
    </div>
  </div>
  <div class="row textfieldwrapper">
    <div id="form-Land" class="cell field" data-caption="Land">
      <input type="text" class="input" value="<?php echo $patientendaten["LAND"];?>">
    </div>
    <div id="form-Ort" class="cell field" data-caption="Ort">
      <input type="text" class="input" value="<?php echo $patientendaten["ORT"];?>">
    </div>
    <div id="form-Str" class="cell field" data-caption="Straße">
      <input type="text" class="input" value="<?php echo $patientendaten["STRASSE"];?>">
    </div>
    <div id="form-Hnr" class="cell field" data-caption="Hausnummer">
      <input type="text" class="input" value="<?php echo $patientendaten["HAUSNUMMER"];?>">
    </div>
  </div>
</div>

<div class="grid pl-4 pr-4 mt-8">
  <div class="row textfieldwrapper">
    <div id="form-Bemerkung" class="cell field bigfield" data-caption="Bemerkung">
      <textarea><?php echo $patientendaten["BEMERKUNG"] ?></textarea>
    </div>
  </div>
</div>
