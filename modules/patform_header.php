<div class="page-header grid p-3 pl-7 pr-7">
  <div class="row">
    <div class="cell page-header-title">
      <span class=""> <?php echo $PAGE_TITLE; ?> </span>
    </div>
    <div class="cell pt-3 text-right">
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
      position: relative;
      z-index: 100;
      white-space: nowrap;
    }
    .page-header-title {
      font-size: 2em;
      overflow-x: hidden;
      white-space: nowrap;
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
