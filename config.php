<?php
// includes
include "sicher/data.php";

$arr_presumption = array('nav', 'action');
foreach ($arr_presumption as $key => $value) {
    $$value = $_REQUEST[$value] ?? '';
}
$nav         = 'config';
$breadcrumbs = [
        ['title' => 'Startseite', 'url' => 'index.php'],
        ['title' => 'Systeminformation'],
        ['title' => 'Konfiguration', 'url' => 'config.php'],
];

$arr_benutzer        = User::getUsers();
$arr_benutzergruppen = [];
$obj_documents       = new documents();
$arr_docTypeNames    = $obj_documents->getDocTypesNames();

foreach ($arr_benutzer as $key => $user) {
    $arr_temp = explode(';', $user['userGroups']);
    foreach ($arr_temp as $value) {
        if (!empty($value)) {
            $arr_benutzergruppen[$value]['name'] = $value;
        }
    }
}

$obj_firma    = new firma();
$obj_filiale  = new filiale();
$arr_firmen   = $obj_firma->get_datensatz();
$arr_filialen = $obj_filiale->get_datensatz();

// Datenbankverbindung prüfen
$db_connection_status = false;
try {
    if (isset($connection) && $connection->ping()) {
        $db_connection_status = true;
    }
} catch (Exception $e) {
    $db_connection_status = false;
}

/**
 * TASK-Status
 */

$tasks = file_exists(TASK_STATUS_FILE)
        ? json_decode(file_get_contents(TASK_STATUS_FILE), true)
        : [];

function fmt(?string $dt): string
{
    return $dt ? date("d.m.Y H:i:s", strtotime($dt)) : "—";
}

function runtime(array $t): string
{
    if (empty($t["last_start"])) return "—";
    if ($t["status"] !== "running" && empty($t["last_success"])) return "—";

    $end = $t["status"] === "running"
            ? time()
            : strtotime($t["last_success"]);

    return (int)($end - strtotime($t["last_start"])) . " s";
}

function statusIcon(string $status): string
{
    return match ($status) {
        "ok"      => "🟢 ok",
        "running" => "🟡 running",
        "error"   => "🔴 error",
        default   => "⚪ unknown"
    };
}

switch ($action) {
    case 'update':

        unset($_POST['action']);
        // neuer benutzer
        $arr_newUser = $_POST['newUser'];
        unset($_POST['newUser']);
        // ini schreiben und speichern
        write_php_ini($_POST, ROOT . 'sicher' . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'config.ini');

        /**
         * Benutzer wird erstellt, falls nicht bereits vorhanden.
         * Achtung: Session wird geschlossen, wenn der Benutzer erstellt wird.
         */
        if (user::getUserbyName($_POST['api']['user']) === false) {
            $adminUser = user::createUser($_POST['api']['user'], $_POST['api']['pass'], '01', '01');
            $groups    = array('admin');
            $adminUser->setVars(['userGroups' => json_encode(array_values($groups), JSON_UNESCAPED_UNICODE)]);
            $adminUser->saveToDb();
        } else {
            // Fallback für bestehende Installationen
            $adminUser = user::getUserbyName($_POST['api']['user']);
            $groups    = array('admin');
            $adminUser->setVars(['userGroups' => json_encode(array_values($groups), JSON_UNESCAPED_UNICODE)]);
            $adminUser->saveToDb();
        }

        if (user::getUserbyName($arr_newUser['user']) === false) {
            $user = user::createUser($arr_newUser['user'], $arr_newUser['pass'], (!empty($arr_newUser['firma']) ? $arr_newUser['firma'] : '01'), (!empty($arr_newUser['filiale']) ? $arr_newUser['filiale'] : '01'));
        }

        header('Location: config.php');
        break;
}
?>
<!doctype html>
<html lang="en">
<head>
    <?php include 'tmpl/head.php' ?>
</head>
<body>
<?php include 'tmpl/titlebar.php' ?>
<?php
if (defined('VERSION_TYP') && !empty(VERSION_TYP)) {
    include 'tmpl/navbar-' . VERSION_TYP . '.php';
} else {
    include 'tmpl/navbar.php';
}
?>
<section class="content-header"></section>
<section class="content">
    <div class="container-fluid">
        <!-- start form -->
        <form method="POST" action="config.php" name="frm_config" class="" id="configForm">
            <input type="hidden" class="form-control" id="version_db_nr" name="version[db_nr]"
                   value="<?php echo defined('VERSION_DB_NR') ? VERSION_DB_NR : '' ?>">
            <div class="row" data-masonry='{"percentPosition": true}' id="cards-row">

                <div class="col-sm-6 col-lg-4 mb-4 col">
                    <div class="card mt-3 card-1-1">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h3 class="card-title">Setup</h3>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="list-group list-group-flush">
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceSetup">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Basis-Konfiguration</div>
                                                Grundlegende Einstellungen 360DashBoard Interface
                                            </div>
                                            <span class="badge bg-success rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#detailSetup">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Interfacespezifische Einstellungen</div>
                                                Spezifische Einstellungen für das Interface Genesys4Aktendeckel
                                            </div>
                                            <span class="badge bg-success rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#localPathSetup">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Lokale Dateiablage</div>
                                                Bearbeiten der Konfiguration lokaler Dateiablage
                                            </div>
                                            <span class="badge bg-success rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#batchModuleSetup">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Setup Stapelbearbeitung</div>
                                                Bearbeiten der Konfiguration zur Stapelübersicht
                                            </div>
                                            <span class="badge bg-success rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#documentModuleSetup">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Setup Dokumentbearbeitung</div>
                                                Bearbeiten der Konfiguration zur Dokumentenübersicht
                                            </div>
                                            <span class="badge bg-success rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>

                                    </div>
                                    <!-- Modal -->
                                    <?php
                                    include 'tmpl/modal/config_interface_setup.php';
                                    ?>
                                    <!-- Modal -->
                                    <?php
                                    $modal_title = 'Interfacespezifische Einstellungen';
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
                                                            <h6>Bezeichner in Übersichten</h6>
                                                            <div class="text-muted">Ersetzt Bezeichner in Übersichten
                                                                und Einzelansichten mit gegebenem Text.
                                                            </div>
                                                            <div class="form-group mb-2">
                                                                <label class="col-form-label"
                                                                       for="batch_transaction_name">Vorgangsnummer</label>
                                                                <input type="text"
                                                                       class="form-control"
                                                                       id="batch_transaction_name"
                                                                       name="batch[transaction_name]"
                                                                       value="<?php echo defined('BATCH_TRANSACTION_NAME') ? BATCH_TRANSACTION_NAME : 'Genesys-Id' ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-form-label"
                                                                       for="batch_preview_name">Vorschaudokumente</label>
                                                                <input type="text"
                                                                       class="form-control"
                                                                       id="batch_preview_name"
                                                                       name="batch[preview_name]"
                                                                       value="<?php echo defined('BATCH_PREVIEW_NAME') ? BATCH_PREVIEW_NAME : 'DiVA-Dokumente' ?>">
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
                                    <!-- Modal -->
                                    <?php
                                    include 'tmpl/modal/config_file_paths.php';
                                    ?>
                                    <!-- Modal -->
                                    <?php
                                    $modal_title = 'Setup für Stapelbearbeitung';
                                    $modal_ident = 'batchModuleSetup';
                                    ?>
                                    <div class="modal fade" id="<?php echo $modal_ident ?>" data-bs-backdrop="static"
                                         data-bs-keyboard="false" tabindex="-1"
                                         aria-labelledby="<?php echo $modal_ident ?>Label" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h1 class="modal-title fs-5"
                                                        id="<?php echo $modal_ident ?>Label"><?php echo $modal_title ?></h1>
                                                    <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                            <p><strong>Stapelupload in Stapelübersicht</strong></p>
                                                            <!-- Basic switch -->
                                                            <div class="form-check form-switch mb-1">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="batch[upload_use]" value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="batch_upload_use"
                                                                       name="batch[upload_use]"
                                                                       value="<?php echo (defined('BATCH_UPLOAD_USE') && !empty(BATCH_UPLOAD_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('BATCH_UPLOAD_USE') && !empty(BATCH_UPLOAD_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="batch_upload_use">Stapelupload
                                                                    nutzen</label>
                                                            </div>
                                                            <div class="form-text mb-3">Upload-Möglichkeit in der
                                                                Stapelübersicht
                                                                einblenden und nutzbar machen.
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                            <p><strong>Stapelupdate in Stapelübersicht</strong></p>
                                                            <!-- Basic switch -->
                                                            <div class="form-check form-switch mb-1">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="batch[refresh_use]"
                                                                       value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="batch_refresh_use"
                                                                       name="batch[refresh_use]"
                                                                       value="<?php echo (defined('BATCH_REFRESH_USE') && !empty(BATCH_REFRESH_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('BATCH_REFRESH_USE') && !empty(BATCH_REFRESH_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="batch_refresh_use">Manueler Stapelrefresh
                                                                    möglich</label>
                                                            </div>
                                                            <div class="form-text mb-3">Möglichkeit einen Stapel zu
                                                                aktualiseren durch Abruf bei Parashift.
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr/>
                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                            <!-- Basic switch -->
                                                            <div class="form-check form-switch mb-3">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="barcode[upload_use]"
                                                                       value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="barcode_upload_use"
                                                                       name="barcode[upload_use]"
                                                                       value="<?php echo (defined('BARCODE_UPLOAD_USE') && !empty(BARCODE_UPLOAD_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('BARCODE_UPLOAD_USE') && !empty(BARCODE_UPLOAD_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="barcode_upload_use">Barcode-Reader Upload
                                                                    nutzen</label>
                                                            </div>
                                                            <div class="form-text mb-3">Möglichkeit einen Stapel zum
                                                                Barcode-Reader zu übermitteln. <span
                                                                        class="text-danger">Achtung</span>
                                                                Barcode-Reader muss aktiv sein!
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

                                    <!-- Modal -->
                                    <?php
                                    $modal_title = 'Setup für Dokumentbearbeitung';
                                    $modal_ident = 'documentModuleSetup';
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
                                                    <div class="row mb-3">
                                                        <div class="col-sm-12">
                                                            <p><strong>Dokumentenupload in Dokumentenübersicht</strong>
                                                            </p>
                                                            <!-- Basic switch -->
                                                            <div class="form-check form-switch mb-1">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="document[upload_use]"
                                                                       value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="document_upload_use"
                                                                       name="document[upload_use]"
                                                                       value="<?php echo (defined('DOCUMENT_UPLOAD_USE') && !empty(DOCUMENT_UPLOAD_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('DOCUMENT_UPLOAD_USE') && !empty(DOCUMENT_UPLOAD_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="document_upload_use">Dokumentenupload
                                                                    nutzen</label>
                                                            </div>
                                                            <div class="form-text">Upload-Möglichkeit in der
                                                                Dokumentenübersicht eines Stapels einblenden und nutzbar
                                                                machen.
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-sm-12">
                                                            <p><strong>Sortierung der Dokumente in
                                                                    Stapelbearbeitung</strong>
                                                            </p>
                                                            <!-- Basic switch -->
                                                            <div class="form-check form-switch mb-1">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="document[batch_list]"
                                                                       value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="document_batch_list"
                                                                       name="document[batch_list]"
                                                                       value="<?php echo (defined('DOCUMENT_BATCH_LIST') && !empty(DOCUMENT_BATCH_LIST)) ? 1 : 0; ?>"
                                                                        <?php echo defined('DOCUMENT_BATCH_LIST') && !empty(DOCUMENT_BATCH_LIST) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="document_batch_list">Übersicht Dokumente
                                                                    nach Erkennungsgrad</label>
                                                            </div>
                                                            <div class="form-text">Dokumente können abweichend von der
                                                                Verarbeitungsreihenfolge nach Erkennungsgrad aufgelistet
                                                                werden.
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-sm-12">
                                                            <p><strong>Ausgabeformat Dokumentennamen bei Export</strong>
                                                            </p>
                                                            <!-- Basic switch -->
                                                            <div class="form-check form-switch mb-3">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="synop[doctype_use]"
                                                                       value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="synop_doctype_use"
                                                                       name="synop[doctype_use]"
                                                                       value="<?php echo (defined('SYNOP_DOCTYPE_USE') && !empty(SYNOP_DOCTYPE_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('SYNOP_DOCTYPE_USE') && !empty(SYNOP_DOCTYPE_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="synop_doctype_use">Exportnamen bei
                                                                    Dokumentenübergabe gem. Synop-Übergabe-Format
                                                                    nutzen.</label>

                                                            </div>
                                                            <!-- Radiobutton-Auswahl, nur sichtbar wenn Checkbox aktiv -->
                                                            <div id="doctype_options"
                                                                 style="display: <?php echo (defined('SYNOP_DOCTYPE_USE') && SYNOP_DOCTYPE_USE) ? 'block' : 'none'; ?>; margin-left: 2em;">
                                                                <div class="form-check">
                                                                    <input class="form-check-input"
                                                                           type="radio"
                                                                           name="synop[doctype_type]"
                                                                           id="doctype_type_1"
                                                                           value="1"
                                                                            <?php echo (defined('SYNOP_DOCTYPE_TYPE') && SYNOP_DOCTYPE_TYPE == 1) ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label"
                                                                           for="doctype_type_1">
                                                                        Typ&nbsp;1 – Standardformat
                                                                    </label>
                                                                    <code>User_GenId_DocName_DocTypeId_BatchId_DocId_Info.pdf</code>
                                                                </div>

                                                                <div class="form-check">
                                                                    <input class="form-check-input"
                                                                           type="radio"
                                                                           name="synop[doctype_type]"
                                                                           id="doctype_type_2"
                                                                           value="2"
                                                                            <?php echo (defined('SYNOP_DOCTYPE_TYPE') && SYNOP_DOCTYPE_TYPE == 2) ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label"
                                                                           for="doctype_type_2">
                                                                        Typ&nbsp;2 – Erweitertes Format
                                                                    </label>
                                                                    <code>User_GenId_DocTypeId_DocName_BatchId_DocId_Info.pdf</code>
                                                                </div>
                                                            </div>
                                                            <div class="form-text">Wie sollen die Exportnamen bei
                                                                Dokumentenübergabe gem. Synop-Übergabe-Format formatiert
                                                                werden.
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-sm-12">
                                                            <p><strong>Schlüssel & Bezeichner für unbekannte Dokumente</strong></p>
                                                            <div class="row g-2 align-items-end">
                                                                <div class="col-sm-4">
                                                                    <label for="unknown_key" class="form-label">Key (Identifier)</label>
                                                                    <input type="text"
                                                                           class="form-control"
                                                                           id="unknown_key"
                                                                           placeholder="z.B. KT-999">
                                                                </div>

                                                                <div class="col-sm-6">
                                                                    <label for="unknown_name" class="form-label">Name</label>
                                                                    <input type="text"
                                                                           class="form-control"
                                                                           id="unknown_name"
                                                                           placeholder="z.B. Unbekanntes Dokument">
                                                                </div>

                                                                <div class="col-sm-2">
                                                                    <button type="button"
                                                                            class="btn btn-outline-primary w-100"
                                                                            id="unknown_add_btn">
                                                                        Hinzufügen
                                                                    </button>
                                                                </div>

                                                                <div class="col-sm-12 mt-2">
                                                                    <label for="unknown_document_type_identifier" class="form-label">JSON (wird gespeichert)</label>
                                                                    <textarea class="form-control"
                                                                              id="unknown_document_type_identifier"
                                                                              name="unknown[document_type_identifier]"
                                                                              rows="6"><?php echo defined('UNKNOWN_DOCUMENT_TYPE_IDENTIFIER') ? UNKNOWN_DOCUMENT_TYPE_IDENTIFIER : '{}' ?></textarea>
                                                                    <div class="form-text">Format: <code>{"KT-999":"Unbekanntes Dokument"}</code></div>
                                                                </div>

                                                                <div class="col-sm-12 mt-2">
                                                                    <div class="input-group">
                                                                        <span class="input-group-text">Recognition Confidence</span>
                                                                        <input type="text"
                                                                               class="form-control"
                                                                               id="unknown_recognition_confidence"
                                                                               name="unknown[recognition_confidence]"
                                                                               value="<?php echo defined('UNKNOWN_RECOGNITION_CONFIDENCE') ? UNKNOWN_RECOGNITION_CONFIDENCE : '0.1' ?>"/>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                            <p><strong>Darstellung Vorschaudokumente</strong></p>
                                                            <select class="form-select mb-1"
                                                                    name="document[preview_type]"
                                                                    id="document_preview_type">
                                                                <option value="link" <?php echo defined('DOCUMENT_PREVIEW_TYPE') && DOCUMENT_PREVIEW_TYPE == 'link' ? 'selected="selected"' : ''; ?>>
                                                                    Link Darstellung
                                                                </option>
                                                                <option value="preview" <?php echo defined('DOCUMENT_PREVIEW_TYPE') && DOCUMENT_PREVIEW_TYPE == 'preview' ? 'selected="selected"' : ''; ?>>
                                                                    Vorschau Darstellung
                                                                </option>
                                                            </select>
                                                            <div class="form-text">Darstellung der Vorschauen in
                                                                Dokumentenübersicht rechte Spalte.
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 mb-4 col">
                    <div class="card mt-3 card-1-1">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h3 class="card-title">Benutzer</h3>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">

                                    <div class="list-group list-group-flush">
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceLogin">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Interface Admin</div>
                                                Bearbeiten der Administratorinformation
                                            </div>
                                            <span class="badge bg-success rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceUser">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Interface Benutzer</div>
                                                Übersicht der Benutzer-/informationen
                                            </div>
                                            <span class="badge bg-success rounded-pill">
                                                <span class="fa fa-check"></span>
                                            </span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceAddUser">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Interface Benutzer hinzufügen</div>
                                                Hinzufügen eines Interface-benutzers
                                            </div>
                                            <span class="badge bg-success rounded-pill">
                                                <span class="fa fa-check"></span>
                                            </span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceViewBatches">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Stapel-Sichtbarkeit Benutzergruppen bearbeiten
                                                </div>
                                                Welcher Benutzer darf welche Stapel sehen?
                                            </div>
                                            <span class="badge bg-success rounded-pill">
                                                <span class="fa fa-check"></span>
                                            </span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceUserIPs">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">IP-Räume für Benutzer</div>
                                                Hinzufügen von IP Räumen zur passwortfreien Nutzung
                                            </div>
                                            <span class="badge bg-success rounded-pill">
                                                <span class="fa fa-check"></span>
                                            </span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#tokenUser">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Token-Verwaltung für Benutzeranmeldung</div>
                                                Für Zugänge aus Drittanwendungen (z.B. Aktendeckel, GeneSys, etc.)
                                            </div>
                                            <span class="badge bg-success rounded-pill">
                                                <span class="fa fa-check"></span>
                                            </span>
                                        </a>
                                    </div>

                                    <!-- Modal -->
                                    <?php
                                    $modal_title = 'Interface-Administrator';
                                    $modal_ident = 'interfaceLogin';
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
                                                            <div class="form-group">
                                                                <label class="col-form-label" for="login_user">Benutzername</label>
                                                                <input type="text" class="form-control" id="login_user"
                                                                       name="login[user]"
                                                                       value="<?php echo defined('LOGIN_USER') ? LOGIN_USER : '' ?>">
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-form-label"
                                                                       for="login_pass">Passwort</label>
                                                                <input type="text" class="form-control" id="login_pass"
                                                                       name="login[pass]"
                                                                       placeholder="<?php echo defined('LOGIN_PASS') && !empty(LOGIN_PASS) ? '********' : ''; ?>"
                                                                       value="<?php //echo LOGIN_PASS       ?>">
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

                                    <!-- Modal -->
                                    <?php
                                    include 'tmpl/modal/config_user_list.php';
                                    ?>

                                    <!-- Modal -->
                                    <?php
                                    $modal_title = 'Interface-Benutzer hinzufügen';
                                    $modal_ident = 'interfaceAddUser';
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
                                                    <p>Fügt einen Benutzer hinzu, unabhängig vom Update durch den Import
                                                        von Benutzerdaten aus angeschlossenen Systemen.</p>
                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                            <div class="form-group">
                                                                <label class="col-form-label" for="newUser_user">E-Mail
                                                                    Adresse
                                                                    (Login)</label>
                                                                <input type="text"
                                                                       class="form-control"
                                                                       name="newUser[user]"
                                                                       id="newUser_user"/>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-form-label"
                                                                       for="newUser_pass">Passwort</label>
                                                                <input type="password"
                                                                       class="form-control"
                                                                       name="newUser[pass]"
                                                                       id="newUser_pass"/>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-form-label" for="newUser_firma">Firmenzuordnung
                                                                    (optional)</label>
                                                                <select class="form-select"
                                                                        id="newUser_firma"
                                                                        name="newUser[firma]"
                                                                        aria-label="Floating label select example">
                                                                    <?php
                                                                    if (!empty($arr_firmen) && !isset($arr_firmen['error'])) {
                                                                        foreach ($arr_firmen as $key => $val) { ?>
                                                                            <option value="<?php echo $val['number'] ?>"><?php echo $val['name'] ?></option>
                                                                        <?php }
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-form-label" for="newUser_filiale">Filialzuordnung
                                                                    (optional)</label>
                                                                <select class="form-select"
                                                                        id="newUser_filiale"
                                                                        name=newUser[filiale]"
                                                                        aria-label="Floating label select example">
                                                                    <?php
                                                                    if (!empty($arr_filialen) && !isset($arr_filialen['error'])) {
                                                                        foreach ($arr_filialen as $key => $val) { ?>
                                                                            <option value="<?php echo $val['number'] ?>"><?php echo $val['name'] ?></option>
                                                                        <?php }
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button class="btn btn-success"
                                                            type="button"
                                                            id="createUserBtn">
                                                        Benutzer anlegen
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary"
                                                            data-bs-dismiss="modal">Schließen
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Modal Ende -->

                                    <!-- Modal -->
                                    <?php
                                    $modal_title = 'Stapel-Sichtbarkeit Benutzergruppen';
                                    $modal_ident = 'interfaceViewBatches';
                                    ?>
                                    <div class="modal modal-lg fade" id="<?php echo $modal_ident ?>"
                                         data-bs-backdrop="static"
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
                                                    <p>Fügt eine Benutzergruppe hinzu und regelt, welche Stapel – nach
                                                        Benutzergruppen – eingesehen werden können.</p>
                                                    <!-- Verstecktes Feld für die Speicherung der Daten als JSON -->
                                                    <textarea name="userGroupVisibility[json]"
                                                              id="user_group_visibility_json"
                                                              style="display:none;"><?php echo defined('USERGROUPVISIBILITY_JSON') ? USERGROUPVISIBILITY_JSON : '[]'; ?></textarea>

                                                    <!-- Datencontainer für JavaScript (damit JS die PHP-Arrays kennt) -->
                                                    <?php
                                                    // Vorbereitung der Optionen für JS
                                                    $js_groups = [];
                                                    if (!empty($arr_benutzergruppen) && !isset($arr_benutzergruppen['error'])) {
                                                        foreach ($arr_benutzergruppen as $u) {
                                                            $js_groups[] = ['val' => $u['name'], 'text' => $u['name']];
                                                        }
                                                    }

                                                    $js_branches   = [];
                                                    $js_branches[] = ['val' => '*', 'text' => 'Alle Benutzergruppen'];
                                                    if (!empty($arr_benutzergruppen) && !isset($arr_benutzergruppen['error'])) {
                                                        foreach ($arr_benutzergruppen as $f) {
                                                            $js_branches[] = ['val' => $f['name'], 'text' => $f['name']];
                                                        }
                                                    }
                                                    ?>
                                                    <script>
                                                        // Daten an globalen Scope übergeben (vor dem Rendern)
                                                        window.availableUserGroups = <?php echo json_encode($js_groups); ?>;
                                                        window.availableBranches = <?php echo json_encode($js_branches); ?>;
                                                    </script>

                                                    <div class="row mb-2">
                                                        <div class="col-sm-5">
                                                            <strong>Benutzergruppe</strong>
                                                        </div>
                                                        <div class="col-sm-6">
                                                            <strong>Darf Stapel sehen von</strong>
                                                        </div>
                                                        <div class="col-sm-1"></div>
                                                    </div>

                                                    <div id="visibilityRowsContainer">
                                                        <!-- Hier werden die Zeilen per JS eingefügt -->
                                                    </div>

                                                    <div class="mt-3 mb-3">
                                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                                id="addVisibilityRowBtn">
                                                            <i class="fa fa-plus"></i> Weitere Regel hinzufügen
                                                        </button>
                                                    </div>

                                                    <!-- Basic switch -->
                                                    <div class="form-check form-switch mb-3">
                                                        <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                        <input type="hidden" name="usergroup[select]" value="0">
                                                        <input class="form-check-input"
                                                               type="checkbox"
                                                               role="switch"
                                                               id="usergroup_select"
                                                               name="usergroup[select]"
                                                               value="<?php echo (defined('USERGROUP_SELECT') && !empty(USERGROUP_SELECT)) ? 1 : 0; ?>"
                                                                <?php echo defined('USERGROUP_SELECT') && !empty(USERGROUP_SELECT) ? 'checked' : ''; ?>
                                                               onclick="this.value = this.checked ? 1 : 0;"/>
                                                        <label class="form-check-label"
                                                               for="usergroup_select">Anzeige Benutzerauswahl
                                                            Gruppenbezogen</label>
                                                    </div>
                                                    <!-- Debug / Ansicht alter Daten falls nötig -->
                                                    <?php // echo json_encode($arr_benutzergruppen, JSON_PRETTY_PRINT); ?>

                                                </div>
                                                <div class="modal-footer">
                                                    <button class="btn btn-success"
                                                            type="submit"
                                                            name="action"
                                                            value="update">
                                                        Konfiguration speichern
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary"
                                                            data-bs-dismiss="modal">Schließen
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Modal Ende -->

                                    <!-- Modal -->
                                    <?php
                                    include 'tmpl/modal/config_user_ips.php';
                                    ?>

                                    <!-- Modal -->
                                    <?php
                                    include 'tmpl/modal/config_user_token.php';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 mb-4 col">
                    <div class="card mt-3 card-1-1">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h3 class="card-title">Datenbank</h3>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-5">
                                <div class="col-sm-12">
                                    <div class="list-group list-group-flush">
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceDB">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Lokaler SQL-Server</div>
                                                Bearbeiten der Interfacedatenbank
                                            </div>
                                            <?php $db_pill_status = $db_connection_status ? 'success' : 'danger'; ?>
                                            <span class="badge bg-<?php echo $db_pill_status ?> rounded-pill">
                                                    <span class="fa fa-<?php echo $db_connection_status ? 'check' : 'exclamation' ?>"></span>
                                                </span>
                                        </a>
                                    </div>

                                    <!-- Modal -->
                                    <?php
                                    include 'tmpl/modal/config_sql_connection.php';
                                    ?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-11 ms-sm-4">
                                    <h5>Aufgabenstatus</h5>
                                    <table class="table table-sm table-hover table-responsive-sm">
                                        <tr>
                                            <th>Task</th>
                                            <th>Status</th>
                                            <th>Letzter Start</th>
                                            <th>Letzter Erfolg</th>
                                            <th>Laufzeit</th>
                                            <th>PID</th>
                                        </tr>

                                        <?php foreach ($tasks as $name => $t): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($name) ?></td>
                                                <td class="<?php echo $t["status"] === "error" ? "bad" : "" ?>">
                                                    <?php echo statusIcon($t["status"] ?? "unknown") ?>
                                                </td>
                                                <td><?php echo fmt($t["last_start"] ?? null) ?></td>
                                                <td><?php echo fmt($t["last_success"] ?? null) ?></td>
                                                <td><?php echo runtime($t) ?></td>
                                                <td><?php echo $t["pid"] ?? "—" ?></td>
                                            </tr>
                                        <?php endforeach; ?>

                                    </table>
                                    <p class="small text-muted">
                                        Letzte Aktualisierung: <?php echo date("d.m.Y H:i:s") ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 mb-4 col">
                    <div class="card mt-3 card-1-1">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h3 class="card-title">Rest API</h3>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="list-group list-group-flush">
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceRest">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Interface Rest API
                                                    (<?php echo defined('PROJECT_NAME') ? PROJECT_NAME : 'Eigen' ?>)
                                                </div>
                                                Bearbeiten der internen REST-Anbindung zu diesem Interface
                                            </div>
                                            <span class="badge bg-success rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#parashiftRest">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Parashift Rest API</div>
                                                Bearbeiten der REST-Anbindung zu Parashift
                                            </div>
                                            <span class="badge bg-success rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#genesysRest">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Genesys Rest API</div>
                                                Bearbeiten der REST-Anbindung zu Genesys
                                            </div>
                                            <?php $pill_status = defined('GENESYS_REST_ENDPOINT') && !empty(GENESYS_REST_ENDPOINT) ? 'success' : 'danger' ?>
                                            <span class="badge bg-<?php echo $pill_status ?> rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#jobarchiveRest">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">JobArchive Rest API</div>
                                                Bearbeiten der REST-Anbindung zu JobArchive
                                            </div>
                                            <?php $pill_status = defined('JOBARCHIVE_AKTIV') && !empty(JOBARCHIVE_AKTIV) ? 'success' : 'danger' ?>
                                            <span class="badge bg-<?php echo $pill_status ?> rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                    </div>

                                    <!-- Modal SELF Rest -->
                                    <?php
                                    include 'tmpl/modal/config_rest_connection.php';
                                    ?>

                                    <!-- Modal Parashift Rest API -->
                                    <?php
                                    $modal_title = 'Parashift-REST-Anbindung';
                                    $modal_ident = 'parashiftRest';
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
                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                            <!-- Basic switch -->
                                                            <div class="form-check form-switch mb-3">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="parashift[use]" value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="parashift_use"
                                                                       name="parashift[use]"
                                                                       value="<?php echo (defined('PARASHIFT_USE') && !empty(PARASHIFT_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('PARASHIFT_USE') && !empty(PARASHIFT_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="parashift_use">Parashift nutzen</label>
                                                            </div>
                                                            <div class="form-check form-switch mb-3">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="parashift[doctype_use]"
                                                                       value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="parashift_doctype_use"
                                                                       name="parashift[doctype_use]"
                                                                       value="<?php echo (defined('PARASHIFT_DOCTYPE_USE') && !empty(PARASHIFT_DOCTYPE_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('PARASHIFT_DOCTYPE_USE') && !empty(PARASHIFT_DOCTYPE_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="parashift_doctype_use">Parashift
                                                                    Dokumententypen nutzen</label>
                                                            </div>
                                                            <p><strong>a) Parashift Anbindung</strong></p>
                                                            <div class="form-group">
                                                                <label class="col-form-label" for="parashift_endpoint">Endpunkt</label>
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control"
                                                                           id="url-parashift_endpoint"
                                                                           name="parashift[endpoint]"
                                                                           value="<?php echo defined('PARASHIFT_ENDPOINT') ? PARASHIFT_ENDPOINT : '' ?>">
                                                                    <button class="btn btn-outline-secondary checkBtn"
                                                                            type="button"
                                                                            data-link-id="parashift_endpoint"
                                                                            data-user-id=""
                                                                            data-pass-id="">
                                                                        <span class="fa fa-search"></span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="parashift_tenant">Tenant</label>
                                                                        <input type="text" class="form-control"
                                                                               id="parashift_tenant"
                                                                               name="parashift[tenant]"
                                                                               value="<?php echo defined('PARASHIFT_TENANT') ? PARASHIFT_TENANT : '' ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="parashift_expires">Ablaufdatum</label>
                                                                        <input type="date" class="form-control"
                                                                               id="parashift_expires"
                                                                               name="parashift[expires]"
                                                                               value="<?php echo defined('PARASHIFT_EXPIRES') ? PARASHIFT_EXPIRES : null ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group mb-3">
                                                                <label class="col-form-label"
                                                                       for="parashift_apikey">API-Key</label>
                                                                <textarea class="form-control"
                                                                          id="parashift_apikey"
                                                                          name="parashift[apikey]"><?php echo defined('PARASHIFT_APIKEY') ? PARASHIFT_APIKEY : '' ?></textarea>
                                                            </div>
                                                            <p class="mb-1"><strong>Ausgangs-Proxy Server</strong></p>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="parashift_rest_proxy">Proxy</label>
                                                                        <input type="text" class="form-control"
                                                                               id="parashift_rest_proxy"
                                                                               name="parashift[rest_proxy]"
                                                                               value="<?php echo defined('PARASHIFT_REST_PROXY') ? PARASHIFT_REST_PROXY : '' ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="parashift_rest_proxyport">Proxyport</label>
                                                                        <input type="text" class="form-control"
                                                                               id="parashift_rest_proxyport"
                                                                               name="parashift[rest_proxyport]"
                                                                               value="<?php echo defined('PARASHIFT_REST_PROXYPORT') ? PARASHIFT_REST_PROXYPORT : '' ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="parashift_rest_proxyuser">Proxyuser</label>
                                                                        <input type="text" class="form-control"
                                                                               id="parashift_rest_proxyuser"
                                                                               name="parashift[rest_proxyuser]"
                                                                               value="<?php echo defined('PARASHIFT_REST_PROXYUSER') ? PARASHIFT_REST_PROXYUSER : '' ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="parashift_rest_proxypass">Proxypass</label>
                                                                        <input type="text"
                                                                               class="form-control"
                                                                               id="parashift_rest_proxypass"
                                                                               name="parashift[rest_proxypass]"
                                                                               value="<?php echo defined('PARASHIFT_REST_PROXYPASS') ? PARASHIFT_REST_PROXYPASS : '' ?>">
                                                                    </div>
                                                                </div>
                                                            </div>


                                                            <hr/>
                                                            <p><strong>b) Parashift-Proxy Anbindung</strong></p>
                                                            <div class="form-check form-switch mb-3">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="parashift[proxy_use]"
                                                                       value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="parashift_proxy_use"
                                                                       name="parashift[proxy_use]"
                                                                       value="<?php echo (defined('PARASHIFT_PROXY_USE') && !empty(PARASHIFT_PROXY_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('PARASHIFT_PROXY_USE') && !empty(PARASHIFT_PROXY_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="parashift_proxy_use">Parashift-Proxy
                                                                    nutzen</label>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-form-label" for="parashift_proxy">Proxy-Endpunkt</label>
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control"
                                                                           id="url-parashift_proxy"
                                                                           name="parashift[proxy]"
                                                                           value="<?php echo defined('PARASHIFT_PROXY') ? PARASHIFT_PROXY : '' ?>">
                                                                    <button class="btn btn-outline-secondary checkBtn"
                                                                            type="button"
                                                                            data-link-id="parashift_proxy"
                                                                            data-user-id=""
                                                                            data-pass-id="">
                                                                        <span class="fa fa-search"></span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="parashift_proxy_token">Token
                                                                            (Key4Proxy)</label>
                                                                        <input type="text" class="form-control"
                                                                               id="parashift_proxy_token"
                                                                               name="parashift[proxy_token]"
                                                                               value="<?php echo defined('PARASHIFT_PROXY_TOKEN') ? PARASHIFT_PROXY_TOKEN : '' ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="parashift_proxy_tenant">Tenant</label>
                                                                        <input type="text" class="form-control"
                                                                               id="parashift_proxy_tenant"
                                                                               name="parashift[proxy_tenant]"
                                                                               value="<?php echo defined('PARASHIFT_PROXY_TENANT') ? PARASHIFT_PROXY_TENANT : '' ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <hr/>
                                                            <p><strong>c) Zweideutige Belege</strong></p>
                                                            <p>Zweideutige Belege verwalten, über deren Verwendung als
                                                                Verkaufs- oder Hereinnahmebeleg müssen Nutzer explizit
                                                                entscheiden.</p>
                                                            <!-- Verstecktes Feld für die Speicherung der Daten als JSON -->
                                                            <textarea name="ambiguousDocsParashift[json]"
                                                                      id="ambiguous_doc_parashift_json"
                                                                      style="display:none;"><?php echo defined('AMBIGUOUSDOCSPARASHIFT_JSON') ? AMBIGUOUSDOCSPARASHIFT_JSON : '[]'; ?></textarea>

                                                            <!-- Datencontainer für JavaScript (damit JS die PHP-Arrays kennt) -->
                                                            <?php
                                                            // Vorbereitung der Optionen für JS
                                                            $js_docTypeNames = [];
                                                            if (!empty($arr_docTypeNames) && !isset($arr_docTypeNames['error'])) {
                                                                foreach ($arr_docTypeNames as $identifier => $name) {
                                                                    $js_docTypeNames[] = ['val' => $identifier, 'text' => $identifier . ' - ' . $name];
                                                                }
                                                            }
                                                            ?>
                                                            <script>
                                                                // Daten an globalen Scope übergeben (vor dem Rendern)
                                                                window.availableDocTypeNames = <?php echo json_encode($js_docTypeNames); ?>;
                                                            </script>

                                                            <div class="row mb-2">
                                                                <div class="col-sm-5">
                                                                    <strong>Eingangs-/Verkaufs&shy;beleg</strong>
                                                                </div>
                                                                <div class="col-sm-6">
                                                                    <strong>Hereinnahme&shy;beleg</strong>
                                                                </div>
                                                                <div class="col-sm-1"></div>
                                                            </div>

                                                            <div id="docVisibilityRowsContainer">
                                                                <!-- Hier werden die Zeilen per JS eingefügt -->
                                                            </div>

                                                            <div class="mt-3 mb-3">
                                                                <button type="button"
                                                                        class="btn btn-sm btn-outline-primary"
                                                                        id="addDocVisibilityRowBtn">
                                                                    <i class="fa fa-plus"></i> Weitere Belege hinzufügen
                                                                </button>
                                                            </div>
                                                            <hr/>
                                                            <p><strong>d) Re-Klassifizierung / Parashift
                                                                    Learning</strong></p>
                                                            <div class="form-check form-switch mb-1">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden"
                                                                       name="parashift[reclassification_use]"
                                                                       value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="parashift_reclassification_use"
                                                                       name="parashift[reclassification_use]"
                                                                       value="<?php echo (defined('PARASHIFT_RECLASSIFICATION_USE') && !empty(PARASHIFT_RECLASSIFICATION_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('PARASHIFT_RECLASSIFICATION_USE') && !empty(PARASHIFT_RECLASSIFICATION_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="parashift_reclassification_use">Parashift-Re-Klassifizierung
                                                                    nutzen</label>
                                                            </div>
                                                            <p class="text-muted">Mit Weiterleitung der Dokumente an
                                                                Drittanwendung werden
                                                                die Klassifizierungen auf Änderung geprüft und ggf. an
                                                                Parashift zur Re-Klassifizierung des Dokumnetnetyps
                                                                kundenbezogen (Kunden-Tenant) übermittelt</p>


                                                            <div class="form-check form-switch mb-1">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="parashift[learning_use]"
                                                                       value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="parashift_learning_use"
                                                                       name="parashift[learning_use]"
                                                                       value="<?php echo (defined('PARASHIFT_LEARNING_USE') && !empty(PARASHIFT_LEARNING_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('PARASHIFT_LEARNING_USE') && !empty(PARASHIFT_LEARNING_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="parashift_learning_use">Parashift-Learning
                                                                    nutzen</label>
                                                            </div>
                                                            <p class="text-muted">Geänderte und vom Kunden
                                                                klassifizierte Dokumente können
                                                                an Parashift weitergeleitet werden, um die
                                                                Klassifizierungen auf Änderungen an Parashift Learning
                                                                (Learning-Tenant) zur Prüfung zu übergeben.</p>

                                                            <div class="form-group mb-3">
                                                                <label class="col-form-label"
                                                                       for="parashift_learning_apikey">Learning-API-Key</label>
                                                                <textarea class="form-control"
                                                                          id="parashift_learning_apikey"
                                                                          name="parashift[learning_apikey]"><?php echo defined('PARASHIFT_LEARNING_APIKEY') ? PARASHIFT_LEARNING_APIKEY : '' ?></textarea>
                                                            </div>

                                                            <div class="form-check form-switch mb-3">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="parashift[page_learning_use]"
                                                                       value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="parashift_page_learning_use"
                                                                       name="parashift[page_learning_use]"
                                                                       value="<?php echo (defined('PARASHIFT_PAGE_LEARNING_USE') && !empty(PARASHIFT_PAGE_LEARNING_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('PARASHIFT_PAGE_LEARNING_USE') && !empty(PARASHIFT_PAGE_LEARNING_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="parashift_page_learning_use">Parashift-Page-Learning
                                                                    nutzen</label>
                                                            </div>
                                                            <p class="text-muted">(Experimental) Geänderte und vom
                                                                Kunden klassifizierte
                                                                Dokumente können
                                                                seitenweise an Parashift übermittelt werden, um die
                                                                Klassifizierungen von einzelnen Seiten zu
                                                                verbessern.</p>
                                                            <div class="form-group">
                                                                <label class="col-form-label"
                                                                       for="parashift_page_learning_apikey">Page-Learning-API-Key</label>
                                                                <textarea class="form-control"
                                                                          id="parashift_page_learning_apikey"
                                                                          name="parashift[page_learning_apikey]"><?php echo defined('PARASHIFT_PAGE_LEARNING_APIKEY') ? PARASHIFT_PAGE_LEARNING_APIKEY : '' ?></textarea>
                                                            </div>
                                                            <!-- parashift learning users -->
                                                            <p class="mt-3 mb-1"><strong>&bull; Learning Users</strong>
                                                            </p>
                                                            <div class="text-muted mb-3">Nur ausgewählte Nutzer können
                                                                Inhalte zum Lernen übermitteln.
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-form-label"
                                                                       for="special_users_picker">Nutzer suchen &
                                                                    hinzufügen</label>
                                                                <select class="form-select parashift_users_picker"
                                                                        id="special_users_picker" style="width: 100%">
                                                                    <option value=""></option>
                                                                    <?php foreach ($arr_benutzer as $u) { ?>
                                                                        <option value="<?php echo htmlspecialchars($u['name']) ?>"><?php echo htmlspecialchars($u['name']) ?>
                                                                            (<?php echo htmlspecialchars($u['mandant'] ?? '') ?>
                                                                            )
                                                                        </option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-form-label">Aktive Benutzer</label>
                                                                <!-- Visuelle Tag-Liste -->
                                                                <div id="parashift_tags_container"
                                                                     class="form-control d-flex flex-wrap gap-2"
                                                                     style="min-height: 45px; height: auto; background-color: var(--bs-tertiary-bg);">
                                                                    <!-- Tags werden per JS hier eingefügt -->
                                                                </div>
                                                                <!-- Blinde Textarea für den POST -->
                                                                <textarea class="d-none"
                                                                          id="parashift_learning_users"
                                                                          name="parashift[learning_users]"><?php echo defined('PARASHIFT_LEARNING_USERS') ? htmlspecialchars(PARASHIFT_LEARNING_USERS) : ''; ?></textarea>
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


                                    <!-- Modal Genesys API -->
                                    <?php
                                    include 'tmpl/modal/config_genesys_rest.php';
                                    ?>

                                    <!-- Modal JobArchive -->
                                    <?php
                                    include 'tmpl/modal/config_jobarchive_rest.php';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-lg-4 mb-4 col">
                    <div class="card mt-3 card-1-1">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h3 class="card-title">Einbindungen</h3>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-12">

                                    <div class="list-group list-group-flush">
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceAktendeckel">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Aktendeckel</div>
                                                Bearbeiten der Einbindung Java-Aktendeckel
                                            </div>
                                            <span class="badge bg-<?php echo defined('JAVA_AKTIV') && JAVA_AKTIV === 'aktiv' ? 'success' : 'danger' ?> rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceGenesys4Aktendeckel">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Genesys4Aktendeckel</div>
                                                Bearbeiten der Einbindung Genesys (DMS)
                                            </div>
                                            <span class="badge bg-<?php echo defined('GEN4AKT_AKTIV') && GEN4AKT_AKTIV === 'aktiv' ? 'success' : 'danger' ?> rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#barcodeReaderRest">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Barcode-Reader API</div>
                                                Endpunkt zur Verarbeitung Barcode-Reader/Splitter
                                            </div>
                                            <span class="badge bg-success rounded-pill">
                                                <i class="fa fa-check"></i>
                                            </span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceDiva">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">DiVA-Interface</div>
                                                Bearbeiten der Einbindung DiVA (Financial-Services)
                                            </div>
                                            <span class="badge bg-<?php echo defined('DIVA_AKTIV') && DIVA_AKTIV === 'aktiv' ? 'success' : 'danger' ?> rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                        <a href="#"
                                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-start"
                                           data-bs-toggle="modal" data-bs-target="#interfaceSynop4Aktendeckel">
                                            <div class="ms-2 me-auto">
                                                <div class="fw-bold">Synop4Aktendeckel</div>
                                                Bearbeiten der Einbindung Synop-Splitter & Synop-Sign
                                            </div>
                                            <span class="badge bg-<?php echo defined('SIG2SYN_AKTIV') && SIG2SYN_AKTIV === 'aktiv' ? 'success' : 'danger' ?> rounded-pill"><span
                                                        class="fa fa-check"></span></span>
                                        </a>
                                    </div>

                                    <!-- Modal -->
                                    <?php
                                    $modal_title = 'Einbindung-Aktendeckel';
                                    $modal_ident = 'interfaceAktendeckel';
                                    ?>
                                    <div class="modal fade" id="<?php echo $modal_ident ?>"
                                         data-bs-backdrop="static"
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
                                                            <!-- Basic switch -->
                                                            <div class="form-check form-switch mb-3">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="java[aktiv]"
                                                                       value="inaktiv">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="java_aktiv"
                                                                       name="java[aktiv]"
                                                                       value="<?php echo (defined('JAVA_AKTIV') && !empty(JAVA_AKTIV)) ? 'aktiv' : 'inaktiv'; ?>"
                                                                        <?php echo defined('JAVA_AKTIV') && JAVA_AKTIV == 'aktiv' ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 'aktiv' : 'inaktiv';"/>
                                                                <label class="form-check-label"
                                                                       for="java_aktiv">Aktivierung</label>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-form-label"
                                                                       for="java_url">Ziel-Link</label>
                                                                <input type="text" class="form-control"
                                                                       id="java_url"
                                                                       name="java[url]"
                                                                       value="<?php echo defined('JAVA_URL') ? JAVA_URL : '' ?>">
                                                            </div>
                                                            <div class="form-text">
                                                                Link zum API-Endpunkt mit Login (REST).
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="aktendeckel_user">Benutzer</label>
                                                                        <input type="text" class="form-control"
                                                                               id="aktendeckel_user"
                                                                               name="aktendeckel[user]"
                                                                               value="<?php echo defined('AKTENDECKEL_USER') ? AKTENDECKEL_USER : '' ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="aktendeckel_pass">Passwort</label>
                                                                        <input type="password" class="form-control"
                                                                               id="aktendeckel_pass"
                                                                               name="aktendeckel[pass]"
                                                                               value="<?php echo defined('AKTENDECKEL_PASS') ? AKTENDECKEL_PASS : '' ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- send gendoc -->
                                                            <div class="form-group">
                                                                <label class="col-form-label"
                                                                       for="endpoint_send_gendoc">Übermittlung-Genesys-Dokument-Endpunkt
                                                                    <code>SEND_GENDOC</code></label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">/</span>
                                                                    <input type="text"
                                                                           class="form-control"
                                                                           id="endpoint_send_gendoc"
                                                                           name="endpoint[send_gendoc]"
                                                                           value="<?php echo defined('ENDPOINT_SEND_GENDOC') && !empty(ENDPOINT_SEND_GENDOC) ? ENDPOINT_SEND_GENDOC : 'dokumentenablage'; ?>"
                                                                           readonly="readonly"/>
                                                                    <span class="input-group-text">
                                                                        <input type="checkbox"
                                                                               value="<?php echo (defined('JAVA_URL') ? JAVA_URL : __DIR__) . '/' . (defined('ENDPOINT_SEND_GENDOC') ? ENDPOINT_SEND_GENDOC : 'dokumentenablage') ?>"
                                                                               id="url-send_gendoc"
                                                                               name="send[gendoc]"
                                                                               data-target="send_gendoc"
                                                                               class="form-check-input mt-0 checkbox_config"
                                                                               <?php echo defined('SEND_GENDOC') && !empty(SEND_GENDOC) ? 'checked="checked"' : ''; ?>/>
                                                                    </span>
                                                                    <button class="btn btn-outline-secondary checkBtn"
                                                                            type="button"
                                                                            data-link-id="send_gendoc"
                                                                            data-user-id="aktendeckel_apiuser"
                                                                            data-pass-id="aktendeckel_apipasswort">
                                                                        <span class="fa fa-search"></span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr/>
                                                    <div class="row">
                                                        <div class="col-sm-12">
                                                            <!-- Basic switch -->
                                                            <div class="form-check form-switch mb-3">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="aktendeckel[docrest_use]"
                                                                       value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="aktendeckel_docrest_use"
                                                                       name="aktendeckel[docrest_use]"
                                                                       value="<?php echo (defined('AKTENDECKEL_DOCREST_USE') && !empty(AKTENDECKEL_DOCREST_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('AKTENDECKEL_DOCREST_USE') && !empty(AKTENDECKEL_DOCREST_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="aktendeckel_docrest_use">Exportübertragung
                                                                    an Aktendeckel via Rest nutzen.</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button"
                                                            class="btn btn-outline-secondary"
                                                            data-bs-dismiss="modal">Schließen
                                                    </button>
                                                    <button class="btn btn-primary btn-block"
                                                            type="submit"
                                                            name="action"
                                                            value="update">
                                                        Speichern
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Modal Ende -->

                                    <!-- Modal -->
                                    <?php
                                    include 'tmpl/modal/config_synop_interface.php';
                                    ?>

                                    <!-- Modal -->
                                    <?php
                                    $modal_title = 'Einbindung-Genesys4Aktendeckel';
                                    $modal_ident = 'interfaceGenesys4Aktendeckel';
                                    ?>
                                    <div class="modal fade" id="<?php echo $modal_ident ?>"
                                         data-bs-backdrop="static"
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
                                                            <!-- Basic switch -->
                                                            <div class="form-check form-switch mb-3">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="gen4akt[aktiv]"
                                                                       value="inaktiv">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="gen4akt_aktiv"
                                                                       name="gen4akt[aktiv]"
                                                                       value="<?php echo (defined('GEN4AKT_AKTIV') && !empty(GEN4AKT_AKTIV)) ? 'aktiv' : 'inaktiv'; ?>"
                                                                        <?php echo defined('GEN4AKT_AKTIV') && GEN4AKT_AKTIV == 'aktiv' ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 'aktiv' : 'inaktiv';"/>
                                                                <label class="form-check-label"
                                                                       for="gen4akt_aktiv">Aktivierung</label>
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-form-label"
                                                                       for="gen4akt_url">Link</label>
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control"
                                                                           id="url-gen4akt_url"
                                                                           name="gen4akt[url]"
                                                                           value="<?php echo defined('GEN4AKT_URL') ? GEN4AKT_URL : '' ?>">
                                                                    <button class="btn btn-outline-secondary checkBtn"
                                                                            type="button"
                                                                            data-link-id="gen4akt_url"
                                                                            data-user-id="gen4akt_user"
                                                                            data-pass-id="gen4akt_pass">
                                                                        <span class="fa fa-search"></span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="form-text">
                                                                Link zur Anwendung (Login-Formular).
                                                            </div>
                                                            <div class="form-group">
                                                                <label class="col-form-label"
                                                                       for="gen4akt_endpoint">API-Endpunkt</label>
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control"
                                                                           id="url-gen4akt_endpoint"
                                                                           name="gen4akt[endpoint]"
                                                                           value="<?php echo defined('GEN4AKT_ENDPOINT') ? GEN4AKT_ENDPOINT : '' ?>">
                                                                    <button class="btn btn-outline-secondary checkBtn"
                                                                            type="button"
                                                                            data-link-id="gen4akt_endpoint"
                                                                            data-user-id="gen4akt_user"
                                                                            data-pass-id="gen4akt_pass">
                                                                        <span class="fa fa-search"></span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="form-text">
                                                                Link zum API-Endpunkt mit Login (REST).
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="gen4akt_user">Benutzer</label>
                                                                        <input type="text" class="form-control"
                                                                               id="gen4akt_user"
                                                                               name="gen4akt[user]"
                                                                               value="<?php echo defined('GEN4AKT_USER') ? GEN4AKT_USER : '' ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="gen4akt_pass">Passwort</label>
                                                                        <input type="password" class="form-control"
                                                                               id="gen4akt_pass"
                                                                               name="gen4akt[pass]"
                                                                               value="<?php echo defined('GEN4AKT_PASS') ? GEN4AKT_PASS : '' ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <hr/>
                                                            <div class="row">
                                                                <div class="col-sm-12">
                                                                    <p>
                                                                        <strong>Suche <?php echo defined('BATCH_TRANSACTION_NAME') ? BATCH_TRANSACTION_NAME : 'Vorgangsnummer' ?></strong>
                                                                    </p>
                                                                    <p>In welchen Spalten der KT_Genesys-Tabelle soll
                                                                        nach Übereinstimmungen gem. des Suchbegriffs
                                                                        gesucht werden?</p>
                                                                    <div class="mb-3">
                                                                        <div id="gen4akt_columns_tags"
                                                                             class="form-control mb-2"
                                                                             style="min-height: 45px; height: auto; display: flex; flex-wrap: wrap; gap: 5px; background-color: var(--bs-backdrop-bg);"></div>

                                                                        <input type="text" class="form-control"
                                                                               id="gen4akt_columns_input"
                                                                               placeholder="Spaltenname eingeben & Enter drücken (z.B. fz_diva_nummer)">

                                                                        <!-- Verstecktes Feld für die Speicherung -->
                                                                        <textarea name="gen4akt[search_cols]"
                                                                                  id="gen4akt_columns_textarea"
                                                                                  style="display:none;"><?php echo defined('GEN4AKT_SEARCH_COLS') ? GEN4AKT_SEARCH_COLS : ''; ?></textarea>
                                                                    </div>
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

                                    <!-- Modal -->
                                    <?php
                                    $modal_title = 'Interface Barcode-Reader Anbindung';
                                    $modal_ident = 'barcodeReaderRest';
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
                                                            <!-- Basic switch -->
                                                            <div class="form-check form-switch mb-3">
                                                                <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                                                                <input type="hidden" name="barcode[use]" value="0">
                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       role="switch"
                                                                       id="barcode_use"
                                                                       name="barcode[use]"
                                                                       value="<?php echo (defined('BARCODE_USE') && !empty(BARCODE_USE)) ? 1 : 0; ?>"
                                                                        <?php echo defined('BARCODE_USE') && !empty(BARCODE_USE) ? 'checked' : ''; ?>
                                                                       onclick="this.value = this.checked ? 1 : 0;"/>
                                                                <label class="form-check-label"
                                                                       for="genesys_use">Barcode-Reader nutzen</label>
                                                            </div>
                                                            <p><strong>a) REST Anbindung</strong></p>
                                                            <div class="form-group">
                                                                <label class="col-form-label" for="barcode_endpoint">Endpunkt
                                                                    <code>BARCODE_ENDPOINT</code></label>
                                                                <div class="input-group">
                                                                    <input type="text" class="form-control"
                                                                           id="url-barcode_endpoint"
                                                                           name="barcode[endpoint]"
                                                                           value="<?php echo defined('BARCODE_ENDPOINT') ? BARCODE_ENDPOINT : '' ?>"/>
                                                                    <button class="btn btn-outline-secondary checkBtn"
                                                                            type="button"
                                                                            data-link-id="barcode_endpoint"
                                                                            data-user-id="barcode_user"
                                                                            data-pass-id="barcode_pass">
                                                                        <span class="fa fa-search"></span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="barcode_user">Benutzer</label>
                                                                        <input type="text" class="form-control"
                                                                               id="barcode_user" name="barcode[user]"
                                                                               value="<?php echo defined('BARCODE_USER') ? BARCODE_USER : '' ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="barcode_pass">Passwort</label>
                                                                        <input type="text" class="form-control"
                                                                               id="barcode_pass" name="barcode[pass]"
                                                                               value="<?php echo defined('BARCODE_PASS') ? BARCODE_PASS : '' ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="barcode_proxy">Proxy</label>
                                                                        <input type="text" class="form-control"
                                                                               id="barcode_proxy"
                                                                               name="barcode[proxy]"
                                                                               value="<?php echo defined('BARCODE_PROXY') ? BARCODE_PROXY : '' ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="barcode_proxyport">Proxyport</label>
                                                                        <input type="text" class="form-control"
                                                                               id="barcode_proxyport"
                                                                               name="barcode[proxyport]"
                                                                               value="<?php echo defined('BARCODE_PROXYPORT') ? BARCODE_PROXYPORT : '' ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="barcode_proxyuser">Proxyuser</label>
                                                                        <input type="text" class="form-control"
                                                                               id="barcode_proxyuser"
                                                                               name="barcode[proxyuser]"
                                                                               value="<?php echo defined('BARCODE_PROXYUSER') ? BARCODE_PROXYUSER : '' ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group mb-3">
                                                                        <label class="col-form-label"
                                                                               for="barcode_proxypass">Proxypass</label>
                                                                        <input type="text"
                                                                               class="form-control"
                                                                               id="barcode_proxypass"
                                                                               name="barcode[proxypass]"
                                                                               value="<?php echo defined('BARCODE_PROXYPASS') ? BARCODE_PROXYPASS : '' ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <p><strong>b) FTP und/oder
                                                                            Direktübertragung</strong></p>
                                                                    <div class="form-text mb-3">Wenn FTP-Angaben
                                                                        vollständig,
                                                                        wird FTP-Verbindung zur
                                                                        Übertragung genutzt. Ansonsten wird
                                                                        die Angabe <code>Zielverzeichnis</code> als Ziel
                                                                        zum
                                                                        internen Kopieren der Dateien genutzt.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <div class="form-group">
                                                                        <label for="barcode_ftp_host">Serveradresse mit
                                                                            Port
                                                                            <code>BARCODE_FTP_HOST</code></label>
                                                                        <input type="text" class="form-control"
                                                                               id="barcode_ftp_host"
                                                                               name="barcode[ftp_host]"
                                                                               value="<?php echo defined('BARCODE_FTP_HOST') ? BARCODE_FTP_HOST : '' ?>"/>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="barcode_ftp_path">Zielverzeichnis
                                                                            (FTP-Pfad oder absolute Pfadangabe)</label>
                                                                        <input type="text" class="form-control"
                                                                               id="barcode_ftp_path"
                                                                               name="barcode[ftp_path]"
                                                                               value="<?php echo defined('BARCODE_FTP_PATH') ? BARCODE_FTP_PATH : '' ?>"/>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="barcode_ftp_user">FTP-Benutzer</label>
                                                                        <input type="text"
                                                                               class="form-control flex-fill"
                                                                               id="barcode_ftp_user"
                                                                               name="barcode[ftp_user]"
                                                                               value="<?php echo defined('BARCODE_FTP_USER') ? BARCODE_FTP_USER : '' ?>"/>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="form-group">
                                                                        <label class="col-form-label"
                                                                               for="barcode_ftp_pass">FTP-Passwort</label>
                                                                        <input type="password"
                                                                               class="form-control flex-fill"
                                                                               id="barcode_ftp_pass"
                                                                               name="barcode[ftp_pass]"
                                                                               value="<?php echo defined('BARCODE_FTP_PASS') ? BARCODE_FTP_PASS : '' ?>"/>
                                                                    </div>
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

                                    <?php
                                    include 'tmpl/modal/config_diva_interface.php';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- finish form -->
        </form>
    </div>
</section>
<section class="content-footer"></section>
<?php include 'tmpl/foot.php' ?>
<script src="js/checkLink.js"></script>
<script src="js/config.js?t=<?php time() ?>"></script>

<script>
    // ... existing code ...

    (function () {
        'use strict';

        function safeParseJsonObject(text) {
            const raw = String(text || '').trim();
            if (!raw) return {};
            try {
                const parsed = JSON.parse(raw);
                if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) return parsed;
            } catch (e) {
                // ungültig -> neu starten
            }
            return {};
        }

        function setTextareaJson(textarea, obj) {
            textarea.value = JSON.stringify(obj, null, 2);
            textarea.dispatchEvent(new Event('input', {bubbles: true}));
            textarea.dispatchEvent(new Event('change', {bubbles: true}));
        }

        function addEntryFromInputs() {
            const keyEl = document.getElementById('unknown_key');
            const nameEl = document.getElementById('unknown_name');
            const ta = document.getElementById('unknown_document_type_identifier');

            if (!keyEl || !nameEl || !ta) return;

            const key = String(keyEl.value || '').trim();
            const name = String(nameEl.value || '').trim();

            if (!key || !name) {
                alert('Bitte Key und Name ausfüllen.');
                return;
            }

            const obj = safeParseJsonObject(ta.value);

            // Duplikate case-insensitive behandeln (Key vereinheitlichen)
            const existingKey = Object.keys(obj).find(k => k.toLowerCase() === key.toLowerCase());
            if (existingKey) {
                obj[existingKey] = name; // überschreiben/aktualisieren
            } else {
                obj[key] = name;
            }

            setTextareaJson(ta, obj);

            // Inputs leeren (optional)
            keyEl.value = '';
            nameEl.value = '';
            keyEl.focus();
        }

        // Button-Klick
        document.addEventListener('click', function (e) {
            const target = e.target;
            if (target && target.id === 'unknown_add_btn') {
                e.preventDefault();
                addEntryFromInputs();
            }
        });

        // Enter im Name-Feld -> hinzufügen
        document.addEventListener('keydown', function (e) {
            const target = e.target;
            if (target && target.id === 'unknown_name' && e.key === 'Enter') {
                e.preventDefault();
                addEntryFromInputs();
            }
        });
    })();

    // ... existing code ...
</script>
</body>
</html>