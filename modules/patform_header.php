<div class="page-header grid p-3 pl-5">
  <div class="row">
    <div class="cell">
      <span class="page-header-title"> <?php echo $PAGE_TITLE; ?> </span>
    </div>
    <div class="cell pt-3">
      <span class="page-header-controls">
        <button class="image-button primary outline" onclick="open_pat_transport();">
          <span class="mif-ambulance icon"></span>
          <span class="caption">Transport</span>
        </button>
        <button class="image-button primary outline" onclick="open_pat_exit();">
          <span class="mif-exit icon"></span>
          <span class="caption">Entlassen</span>
        </button>
      </span>
    </div>
    <div class="cell pt-3">
      <span class="page-header-controls">
        <button class="image-button success outline" onclick="update_pat_data();">
          <span class="mif-floppy-disk icon"></span>
          <span class="caption">Speichern</span>
        </button>
        <button class="image-button alert outline" onclick="history.back();">
          <span class="mif-cross icon"></span>
          <span class="caption">Abbrechen</span>
        </button>
      </span>
    </div>
  </div>


  <style>
    .page-header {
      position: fixed !important;
      background-color: #fff;
      top: 0;
      width: 100%;
      max-width: 1000px;
      min-width: 550px;
      margin: 0 auto;
      box-sizing: border-box;
      margin-bottom: 30px;
      position: relative;
      border-bottom: 1px solid lightgray;
      z-index: 100;
    }
    .page-header-title {
      font-size: 2em;
      overflow-x: hidden;
    }
    .page-header-controls {
      padding-left: 3px;
      min-width: 505px;
      white-space: nowrap;
    }
    .page-header-controls > .image-button {
      margin-right: 3px;
    }
  </style>
</div>
