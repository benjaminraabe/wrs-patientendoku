<div class="grid pl-4">
  <div class="row">
    <!-- Ergebnis des Datenchecks -->
    <div class="cell-4 pt-4" style="border: 1px solid gray;">
      <h4 class="pl-4">Datenprüfung</h4>
      <div class="text-center" id="wrapperExitCheckSuccess">
        <span class="mif-checkmark icon fg-green"></span>
        Alle Daten sind vollständig!
      </div>

      <div class="text-center" id="wrapperExitCheckFail">
        <div class="grid" id="ExitCheckFailGrid">
        </div>
      </div>
    </div>


    <div class="cell-1"></div>


    <!-- Interaktionsflächen Transport / Entlassungen -->
    <div class="cell-7 p-4">
      <!-- "Normaler" Patientenausgang ohne Transport -->
      <div class="wrapper-exit">
        <h4>Patient Entlassen</h4>
        <button class="image-button success outline w-100" onclick="update_pat_data(true);"
          <?php if(!$has_write_access){echo "disabled";} ?>>
          <span class="mif-exit icon"></span>
          <span class="caption">Nach Hause entlassen</span>
        </button>
      </div>


      <!-- Transportanforderung / Übergabe -->
      <div class="wrapper-exit mt-12">
        <h4><?php if(is_null($patientendaten["TRANSPORTKATEGORIE"])){echo "Transportanforderung";} else {echo "Übergabe an Rettungsmittel";} ?></h4>

        <div class="grid mb-4">
          <div class="row">
            <select id="form-transport-kategorie" data-role="select" data-prepend="Kategorie" <?php if (!is_null($patientendaten["TRANSPORTKATEGORIE"])){echo " disabled";} ?>>
              <option value="KBF"
                <?php if($patientendaten["TRANSPORTKATEGORIE"] == "KBF"){echo " selected";} ?>>KBF</option>
              <option value="Notfall K"
                <?php if($patientendaten["TRANSPORTKATEGORIE"] == "Notfall K"){echo " selected";} ?>>Notfall K</option>
              <option value="Notfall 01"
                <?php if($patientendaten["TRANSPORTKATEGORIE"] == "Notfall 01"){echo " selected";} ?>>Notfall 01</option>
              <option value="Notfall 11"
                <?php if($patientendaten["TRANSPORTKATEGORIE"] == "Notfall 11"){echo " selected";} ?>>Notfall 11</option>
              <option value="Platztransport"
                <?php if($patientendaten["TRANSPORTKATEGORIE"] == "Platztransport"){echo " selected";} ?>>Platztransport</option>
              <!-- <option value="Infektion"
                <?php #if($patientendaten["TRANSPORTKATEGORIE"] == "Infektion"){echo " selected";} ?>>Infektion</option>
              <option value="Überschwer"
                <?php #if($patientendaten["TRANSPORTKATEGORIE"] == "Überschwer"){echo " selected";} ?>>Überschwer</option> -->
            </select>
          </div>
          <div class="row mt-2 <?php if(is_null($patientendaten["TRANSPORTKATEGORIE"])){echo " hidden";} ?>">
            <input id="form-transport-rufname" type="text" data-role="input" data-prepend="Rufname ">
          </div>
          <div class="row mt-2 <?php if(is_null($patientendaten["TRANSPORTKATEGORIE"])){echo " hidden";} ?>">
            <select id="form-transport-zielkh" data-role="select" data-prepend="Zielklinik">
              <option value=""></option>
              <?php foreach ($kliniken as $klinik):?>
                <option value="<?php echo $klinik["KLINIK_ID"];?>"><?php echo $klinik["KLINIK_NAME"];?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <?php if (is_null($patientendaten["TRANSPORTKATEGORIE"])): ?>
          <!-- Transportanforderung -->
          <button class="image-button success outline w-100" onclick="run_pat_transport_req();"
            <?php if(!$has_write_access){echo "disabled";} ?>>
            <span class="mif-ambulance icon"></span>
            <span class="caption">Anforderung speichern</span>
          </button>
        <?php endif; ?>
        <?php if (!is_null($patientendaten["TRANSPORTKATEGORIE"])): ?>
          <button class="image-button success outline w-100" onclick="run_pat_transport_req(true);"
            <?php if(!$has_write_access){echo "disabled";} ?>>
            <span class="mif-ambulance icon"></span>
            <span class="caption">Übergabe speichern</span>
          </button>
        <?php endif; ?>


      </div>
    </div>
  </div>
</div>
