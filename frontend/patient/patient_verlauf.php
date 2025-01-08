<?php
  include_once '../backend/util_patientenverlauf.php';
  $ids_korrektur = array(
    ENTRY_TYPE_NACHTRAG_EINGANGSZEIT,
    ENTRY_TYPE_NACHTRAG_AUSGANGSZEIT,
    ENTRY_TYPE_NACHTRAG_DATENAENDERUNG
  );
?>


<!-- Patientenverlauf -->
<h4 class="pl-4 verlauf-heading">Verlauf</h4>
<div class="grid pl-4 pr-4 verlauf">
    <?php foreach ($verlauf as $row):?>
      <div class="row">
        <div class="cell cell-2 text-center">
          <?php echo dayNumberToDayShort(intval(date("w", strtotime($row["TIMESTAMP"])))) . " " . date("d.m.", strtotime($row["TIMESTAMP"])); ?> <br>
          <?php echo date("H:i", strtotime($row["TIMESTAMP"])) . " Uhr"; ?> <br>
          <?php echo "@".$row["USERNAME"] ?>
        </div>
        <div class="cell cell-10">
          <?php if (in_array($row["ART"], $ids_korrektur)): ?>
            <!-- Je nach Art des Eintrags werden zusätzliche Attribute hinzugefügt. -->
            <span class style='color: red;'>
              Korrektur nach Entlassung:
            </span> <br>
          <?php endif; ?>

            <span class="pre"><?php echo $row["EINTRAG"]; ?></span>

        </div>
      </div>
    <?php endforeach; ?>
</div>
