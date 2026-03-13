<?php
$modal_title = 'Lokale Dateiablage';
$modal_ident = 'localPathSetup';
?>
<div class="modal fade" id="<?php echo $modal_ident ?>" data-bs-backdrop="static"
     data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="<?php echo $modal_ident ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5"
                    id="<?php echo $modal_ident ?>Label"><?php echo $modal_title ?></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label class="col-form-label"
                                   for="install_path">Install-Pfad <code>ROOT</code></label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control"
                                       id="install_path"
                                       disabled="disabled"
                                       value="<?php echo ROOT ?>"/>
                            </div>
                            <div class="form-text" id="basic-addon4">
                                Installations-Pfad für
                                ScanHUB, automatisch ermittelt.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label class="col-form-label"
                                   for="working_path">Arbeits-Pfad (relativ) <code>WORKING_PATH</code></label>
                            <div class="input-group">
                                <span class="input-group-text"
                                      id="basic-addon3"><?php echo ROOT; ?></span>
                                <input type="text"
                                       class="form-control"
                                       id="working_path"
                                       name="working[path]"
                                       value="<?php echo defined('WORKING_PATH') ? WORKING_PATH : 'data' ?>"/>
                            </div>
                            <div class="form-text" id="basic-addon4">Arbeitspfad für
                                Verarbeitung in  <?php echo defined('PROJECT_NAME') ? PROJECT_NAME : 'dieser Anwendung'; ?>.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group mb-3">
                            <label class="col-form-label" for="export_path">Export-Pfad
                                (absolut) <code>EXPORT_PATH</code></label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control"
                                       id="export_path"
                                       name="export[path]"
                                       value="<?php echo defined('EXPORT_PATH') ? EXPORT_PATH : ROOT . 'data'; ?>"
                                       readonly>
                                <button class="btn btn-outline-secondary open-dir-picker" type="button"
                                        data-target-input="#export_path">
                                    <i class="fa fa-folder-open"></i> Durchsuchen...
                                </button>
                            </div>
                            <div class="form-text" id="basic-addon4">Absoluter Pfad für Export fertiger Dokumente.</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label class="col-form-label" for="archive_path">Backup-Pfad
                                (absolut) <code>ARCHIVE_PATH</code></label>
                            <div class="input-group">
                                <input type="text"
                                       class="form-control"
                                       id="archive_path"
                                       name="archive[path]"
                                       value="<?php echo defined('ARCHIVE_PATH') ? ARCHIVE_PATH : ROOT . 'data' ?>"/>
                                <button class="btn btn-outline-secondary open-dir-picker" type="button"
                                        data-target-input="#archive_path">
                                    <i class="fa fa-folder-open"></i> Durchsuchen...
                                </button>
                            </div>
                            <div class="form-text" id="basic-addon4">Apsoluter Pfad
                                für Backup übermittelter Dokumente (30 Tage).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Schließen
                </button>
                <button class="btn btn-primary btn-block" type="submit"
                        name="action"
                        value="update">
                    Speichern
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Ende -->

<!-- Modal für Verzeichnisauswahl -->
<div class="modal fade" id="dirPickerModal" tabindex="-1" aria-labelledby="dirPickerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dirPickerModalLabel">
                    <i class="fa fa-search me-2"></i>Verzeichnis wählen
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            </div>
            <div class="modal-body">
                <div id="dir-browser-container" style="max-height: 400px;">
                    <!-- Wird per AJAX gefüllt -->
                    <div class="text-center"><i class="fa fa-spinner fa-spin"></i> Lade
                        Verzeichnisse...
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <span class="flex-grow-1 text-muted">Aktuell gewählt: <strong
                            id="selected-path-display">-</strong></span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abbrechen</button>
                <button type="button" class="btn btn-primary" id="confirm-dir">Pfad übernehmen</button>
            </div>
        </div>
    </div>
</div>