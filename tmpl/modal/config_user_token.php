<?php
$modal_title = 'Token-Verwaltung';
$modal_ident = 'tokenUser';
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
                            <input type="hidden" name="third[party_pass_use]"
                                   value="inaktiv">
                            <input class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   id="third_party_pass_use"
                                   name="third[party_pass_use]"
                                   value="<?php echo (defined('THIRD_PARTY_PASS_USE') && !empty(THIRD_PARTY_PASS_USE)) ? 'aktiv' : 'inaktiv'; ?>"
                                    <?php echo defined('THIRD_PARTY_PASS_USE') && THIRD_PARTY_PASS_USE == 'aktiv' ? 'checked' : ''; ?>
                                   onclick="this.value = this.checked ? 'aktiv' : 'inaktiv';"/>
                            <label class="form-check-label"
                                   for="third_party_pass_use">Small-Token-Aktivierung</label>
                        </div>
                        <div class="form-group">
                            <label class="col-form-label" for="third_party_pass">Small-Token-Passwort <code>THIRD_PARTY_PASS</code></label>
                            <div class="input-group">
                                <input type="text" class="form-control"
                                       id="third_party_pass"
                                       name="third[party_pass]"
                                       value="<?php echo defined('THIRD_PARTY_PASS') ? THIRD_PARTY_PASS : 'A7f!Q9mZ@2Lp' ?>">
                            </div>
                        </div>
                        <div class="form-text">
                            Das Small-Token-Passwort wird für die Authentifizierung von Drittanwendungen verwendet, um
                            Zugriff auf die Jobarchive-API zu gewähren. Es ist empfehlenswert, ein starkes Passwort zu
                            verwenden, um die Sicherheit zu gewährleisten.
                        </div>
                        <hr/>
                        <!-- Basic switch -->
                        <div class="form-check form-switch mb-1">
                            <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                            <input type="hidden" name="jwt[auth_secret_use]"
                                   value="inaktiv">
                            <input class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   id="jwt_auth_secret_use"
                                   name="jwt[auth_secret_use]"
                                   value="<?php echo (defined('JWT_AUTH_SECRET_USE') && !empty(JWT_AUTH_SECRET_USE)) ? 'aktiv' : 'inaktiv'; ?>"
                                    <?php echo defined('JWT_AUTH_SECRET_USE') && JWT_AUTH_SECRET_USE == 'aktiv' ? 'checked' : ''; ?>
                                   onclick="this.value = this.checked ? 'aktiv' : 'inaktiv';"/>
                            <label class="form-check-label"
                                   for="jwt_auth_secret_use">JWT-HS256 Signaturprüfung</label>
                        </div>
                        <div class="form-group mb-2">
                            <label class="col-form-label" for="jwt_auth_secret">HS256: Signaturprüfung mit Secret-Key
                                <code>JWT_AUTH_SECRET</code></label>
                            <div class="input-group">
                                <input type="text" class="form-control"
                                       id="jwt_auth_secret"
                                       name="jwt[auth_secret]"
                                       value="<?php echo defined('JWT_AUTH_SECRET') ? JWT_AUTH_SECRET : '' ?>">
                            </div>
                        </div>
                        <div class="form-text">
                            <ul>
                                <li>Zufällig und lang (mind. 32 Bytes, besser 64 Bytes oder mehr)</li>
                                <li>Nicht erratbar, nicht Wörterbuch/Pattern-basiert</li>
                                <li>Nur serverseitig gespeichert (Config/ENV/Secret-Store), nie im Repo</li>
                                <li>Darf fast beliebige Zeichen enthalten; am robustesten ist Base64 oder Hex</li>
                                <li>Nicht wiederverwenden für andere Zwecke</li>
                            </ul>
                        </div>
                        <!-- Basic switch -->
                        <div class="form-check form-switch mb-1">
                            <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                            <input type="hidden" name="jwt[auth_public_key_use]"
                                   value="inaktiv">
                            <input class="form-check-input"
                                   type="checkbox"
                                   role="switch"
                                   id="jwt_auth_public_key_use"
                                   name="jwt[auth_public_key_use]"
                                   value="<?php echo (defined('JWT_AUTH_PUBLIC_KEY_USE') && !empty(JWT_AUTH_PUBLIC_KEY_USE)) ? 'aktiv' : 'inaktiv'; ?>"
                                    <?php echo defined('JWT_AUTH_PUBLIC_KEY_USE') && JWT_AUTH_PUBLIC_KEY_USE == 'aktiv' ? 'checked' : ''; ?>
                                   onclick="this.value = this.checked ? 'aktiv' : 'inaktiv';"/>
                            <label class="form-check-label"
                                   for="jwt_auth_public_key_use">JWT-RS256 Signaturprüfung</label>
                        </div>
                        <div class="form-group mb-1">
                            <label class="col-form-label" for="jwt_auth_public_key_file">Public-Keyfile <code>JWT_AUTH_PUBLIC_KEY_FILE</code></label>
                            <div class="input-group">
                                <input type="text" class="form-control"
                                       id="jwt_auth_public_key_file"
                                       name="jwt[auth_public_key_file]"
                                       readonly="readonly"
                                       value="<?php echo defined('JWT_AUTH_PUBLIC_KEY_FILE') ? htmlspecialchars((string)JWT_AUTH_PUBLIC_KEY_FILE, ENT_QUOTES, 'UTF-8') : dirname(__FILE__, 3).'/sicher/jwt_public.pem' ?>">
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label class="col-form-label" for="jwt_auth_private_key_file">Privat-Keyfile <code>JWT_AUTH_PRIVATE_KEY_FILE</code></label>
                            <div class="input-group">
                                <input type="text" class="form-control"
                                       id="jwt_auth_private_key_file"
                                       name="jwt[auth_private_key_file]"
                                       readonly="readonly"
                                       value="<?php echo defined('JWT_AUTH_PRIVATE_KEY_FILE') ? htmlspecialchars((string)JWT_AUTH_PRIVATE_KEY_FILE, ENT_QUOTES, 'UTF-8') : dirname(__FILE__, 3).'/sicher/jwt_private.pem' ?>">
                            </div>
                        </div>
                        <div class="form-text mb-3">Erstellung Kundenbezogen. Siehe dazu Hinweise unter <a href="./inc/php-jwt/README.md">README.md</a></div>
                        <p><strong>Optional:</strong></p>
                        <div class="form-group">
                            <label class="col-form-label" for="jwt_auth_public_key">Shared-Secret im Payload-Claim
                                `secret`
                                <code>JWT_LOGIN_SHARED_SECRET</code></label>
                            <div class="input-group">
                                <input type="text" class="form-control"
                                       id="jwt_login_shared_secret"
                                       name="jwt[login_shared_secret]"
                                       value="<?php echo defined('JWT_LOGIN_SHARED_SECRET') ? JWT_LOGIN_SHARED_SECRET : '' ?>">
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