<?php
$modal_title = 'Interface-Details';
$modal_ident = 'detailSetup';
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