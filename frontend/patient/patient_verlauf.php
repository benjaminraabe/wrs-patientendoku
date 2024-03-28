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
        <div class="cell cell-10"><?php echo $row["EINTRAG"]; ?></div>
      </div>
    <?php endforeach; ?>
</div>
