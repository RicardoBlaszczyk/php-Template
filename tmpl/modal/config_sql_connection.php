<?php
$modal_title = 'Interface-Datenbank';
$modal_ident = 'interfaceDB';
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
                            <label class="col-form-label"
                                   for="mssql_typ">Verbindung zu
                                Datenbanktyp</label>
                            <select class="form-select" id="mssql_typ"
                                    name="mssql[typ]">
                                <option value="<?= DbConnector::CONNECTION_MSSQL ?>" <?php echo defined('MSSQL_TYP') && MSSQL_TYP === DbConnector::CONNECTION_MSSQL ? 'selected="selected"' : '' ?>>
                                    MS-SQL
                                </option>
                                <option value="<?= DbConnector::CONNECTION_MYSQL ?>" <?php echo defined('MSSQL_TYP') && MSSQL_TYP === DbConnector::CONNECTION_MYSQL ? 'selected="selected"' : '' ?>>
                                    MySQL/MariaDB
                                </option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-sm-9">
                                <div class="form-group">
                                    <label class="col-form-label"
                                           for="mssql_server">Server</label>
                                    <input type="text" class="form-control"
                                           id="mssql_server" name="mssql[server]"
                                           value="<?php echo defined('MSSQL_SERVER') ? MSSQL_SERVER : '' ?>">
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label class="col-form-label"
                                           for="mssql_port">Port</label>
                                    <input type="text" class="form-control" id="mssql_port"
                                           name="mssql[port]"
                                           value="<?php echo defined('MSSQL_PORT') ? MSSQL_PORT : '' ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-form-label"
                                           for="mssql_encrypt">Encrypt</label>
                                    <select class="form-select" id="mssql_encrypt"
                                            name="mssql[encrypt]">
                                        <option value="true" <?php echo defined('MSSQL_ENCRYPT') && MSSQL_ENCRYPT == 'true' ? 'selected="selected"' : '' ?>>
                                            true
                                        </option>
                                        <option value="false" <?php echo !defined('MSSQL_ENCRYPT') || MSSQL_ENCRYPT == 'false' ? 'selected="selected"' : '' ?>>
                                            false
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-form-label"
                                           for="mssql_trustservercertificate">TrustServerCertificate</label>
                                    <select class="form-select" id="mssql_trustservercertificate"
                                            name="mssql[trustservercertificate]">
                                        <option value="true" <?php echo defined('MSSQL_TRUSTSERVERCERTIFICATE') && MSSQL_TRUSTSERVERCERTIFICATE == 'true' ? 'selected="selected"' : '' ?>>
                                            true
                                        </option>
                                        <option value="false" <?php echo !defined('MSSQL_TRUSTSERVERCERTIFICATE') || MSSQL_TRUSTSERVERCERTIFICATE == 'false' ? 'selected="selected"' : '' ?>>
                                            false
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-form-label"
                                           for="mssql_user">Benutzer</label>
                                    <input type="text" class="form-control" id="mssql_user"
                                           name="mssql[user]"
                                           value="<?php echo defined('MSSQL_USER') ? MSSQL_USER : '' ?>">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label class="col-form-label"
                                           for="mssql_pass">Passwort</label>
                                    <input type="password" class="form-control"
                                           id="mssql_pass" name="mssql[pass]"
                                           value="<?php echo defined('MSSQL_PASS') ? MSSQL_PASS : '' ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-form-label"
                                   for="mssql_db">Datenbank</label>
                            <input type="text" class="form-control" id="mssql_db"
                                   name="mssql[db]"
                                   value="<?php echo defined('MSSQL_DB') ? MSSQL_DB : '' ?>">
                        </div>

                        <hr/>
                        <!-- Basic switch -->
                        <div class="form-check form-switch mb-3">
                            <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                            <input type="hidden" name="db[debug]" value="0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   id="db_debug"
                                   name="db[debug]"
                                   value="<?php echo (defined('DB_DEBUG') && !empty(DB_DEBUG)) ? 1 : 0; ?>"
                                <?php echo defined('DB_DEBUG') && !empty(DB_DEBUG) ? 'checked' : ''; ?>
                                   onclick="this.value = this.checked ? 1 : 0;"/>
                            <label class="form-check-label"
                                   for="db_debug">Debug Modus einschalten</label>
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