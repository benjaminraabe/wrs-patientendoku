<div class="page-header grid p-3 pl-5">
  <div class="row">
    <div class="cell">
      <span class="page-header-title"> <?php echo $PAGE_TITLE; ?> </span>
    </div>
    <div class="cell">
      <span class="page-header-controls no-print place-right">
        <button class="image-button secondary outline" onclick="window.location.href = '../frontend/index.php'">
          <span class="mif-home icon"></span>
          <span class="caption">Startseite</span>
        </button>
        <button class="image-button secondary outline" onclick="history.back()">
          <span class="mif-arrow-left icon"></span>
          <span class="caption">Zurück</span>
        </button>
        <button class="button alert outline" onclick="window.location.href = '../backend/logout.php'">
          <span class="mif-exit icon"></span>
        </button>
      </span>
    </div>
  </div>

  <style>
    .page-header {
      width: 100%;
      margin: 0;
      box-sizing: border-box;
      margin-bottom: 30px;
      position: relative;
      border-bottom: 1px solid lightgray;
    }
    .page-header-title {
      font-size: 2em;
      overflow-x: hidden;
    }
    .page-header-controls {
      padding-left: 3px;
      padding-top: 6px;
      min-width: 265px;
      white-space: nowrap;
    }
    .page-header-controls > .image-button {
      margin-right: 3px;
    }
  </style>
</div>
