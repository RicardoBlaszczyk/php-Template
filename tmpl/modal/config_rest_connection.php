<?php
$modal_title = 'Interface-REST-Anbindung';
$modal_ident = 'interfaceRest';
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
                        <div class="form-text mb-3">API Anbindung von <?php echo defined('PROJECT_NAME') ? PROJECT_NAME : 'dieses Interface' ?>.</div>
                        <div class="form-group">
                            <label class="col-form-label" for="rest_endpoint">Endpunkt</label>
                            <div class="input-group">
                                <input type="text" class="form-control"
                                       id="url-rest_endpoint" name="rest[endpoint]"
                                       value="<?php echo defined('REST_ENDPOINT') ? REST_ENDPOINT : '' ?>">
                                <button class="btn btn-outline-secondary checkBtn"
                                        type="button"
                                        data-link-id="rest_endpoint"
                                        data-user-id="api_user"
                                        data-pass-id="api_pass">
                                    <span class="fa fa-search"></span>
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-form-label" for="api_user">Benutzername
                                        <code>API_USER</code></label>
                                    <input type="text" class="form-control"
                                           id="api_user"
                                           name="api[user]"
                                           value="<?php echo defined('API_USER') ? API_USER : 'api-sa'; ?>">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-form-label"
                                           for="api_pass">Passwort
                                        <code>API_PASS</code></label>
                                    <input type="text" class="form-control"
                                           id="api_pass"
                                           name="api[pass]"
                                           value="<?php echo defined('API_PASS') ? API_PASS : 'kaitech'; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-form-label"
                                           for="rest_proxy">Proxy</label>
                                    <input type="text" class="form-control"
                                           id="rest_proxy"
                                           name="rest[proxy]"
                                           value="<?php echo defined('REST_PROXY') ? REST_PROXY : '' ?>">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-form-label"
                                           for="rest_proxyport">Proxyport</label>
                                    <input type="text" class="form-control"
                                           id="rest_proxyport"
                                           name="rest[proxyport]"
                                           value="<?php echo defined('REST_PROXYPORT') ? REST_PROXYPORT : '' ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-form-label"
                                           for="rest_proxyuser">Proxyuser</label>
                                    <input type="text" class="form-control"
                                           id="rest_proxyuser"
                                           name="rest[proxyuser]"
                                           value="<?php echo defined('REST_PROXYUSER') ? REST_PROXYUSER : '' ?>">
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="col-form-label"
                                           for="rest_proxypass">Proxypass</label>
                                    <input type="text"
                                           class="form-control"
                                           id="rest_proxypass"
                                           name="rest[proxypass]"
                                           value="<?php echo defined('REST_PROXYPASS') ? REST_PROXYPASS : '' ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <hr/>
                <p>Einschränkung der Zugänge zu den API-Endpunkten, wenn der Zugang
                    nicht über BasicAuth erfolgt (optional).</p>
                <div class="row mb-3">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label class="col-form-label"
                                   for="api_allowed_ips">Erlaubte IP-Adressen
                                (komma-separiert)</label>
                            <textarea class="form-control"
                                      id="api_allowed_ips"
                                      name="api[allowed_ips]"><?php echo defined('API_ALLOWED_IPS') ? API_ALLOWED_IPS : '' ?></textarea>
                            <div class="form-text" id="basic-addon4">
                                127.0.0.1,192.168.1.1
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label class="col-form-label"
                                   for="api_allowed_origins">Erlaubte Origins
                                (komma-separiert)</label>
                            <textarea class="form-control"
                                      id="api_allowed_origins"
                                      name="api[allowed_origins]"><?php echo defined('API_ALLOWED_ORIGINS') ? API_ALLOWED_ORIGINS : '' ?></textarea>
                            <div class="form-text" id="basic-addon4">
                                http://localhost,http://localhost:8080,https://genesys.example.com
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