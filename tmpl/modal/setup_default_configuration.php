<?php
/**
 * Modal: default.ini anzeigen
 */
$modal_title = 'default.ini';
$modal_ident = 'defaultIniModal';

$defaultIniPath = ROOT . 'sicher' . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'default.ini';
$defaultIniContent = '';

if (is_file($defaultIniPath)) {
    $raw = file_get_contents($defaultIniPath);
    if ($raw !== false) {
        $defaultIniContent = $raw;
    }
} else {
    $defaultIniContent = 'Datei nicht gefunden: ' . $defaultIniPath;
}
?>
<div class="modal fade" id="<?php echo $modal_ident ?>" data-bs-backdrop="static"
     data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="<?php echo $modal_ident ?>Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5"
                    id="<?php echo $modal_ident ?>Label"><?php echo $modal_title ?></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    Hinweis: Inhalte können sensitive Konfiguration enthalten. Bitte nur mit entsprechender Berechtigung teilen.
                </div>
                <div class="form-group">
                    <label class="col-form-label" for="default_ini_view">Inhalt (<code>sicher/ini/default.ini</code>)</label>
                    <textarea class="form-control font-monospace" id="default_ini_view" rows="18" readonly><?php
                        echo htmlspecialchars($defaultIniContent, ENT_QUOTES, 'UTF-8');
                    ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Schließen</button>
            </div>
        </div>
    </div>
</div>