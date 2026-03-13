<?php
$modal_title = 'Interface-Benutzer IP-Räume';
$modal_ident = 'interfaceUserIPs';
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
                <!-- Basic switch -->
                <div class="form-check form-switch mb-3">
                    <!-- Fallback: wird gesendet, wenn Checkbox nicht angehakt -->
                    <input type="hidden" name="user[rooms_use]"
                           value="inaktiv">
                    <input class="form-check-input"
                           type="checkbox"
                           role="switch"
                           id="user_rooms_use"
                           name="user[rooms_use]"
                           value="<?php echo (defined('USER_ROOMS_USE') && !empty(USER_ROOMS_USE)) ? 'aktiv' : 'inaktiv'; ?>"
                        <?php echo defined('USER_ROOMS_USE') && USER_ROOMS_USE == 'aktiv' ? 'checked' : ''; ?>
                           onclick="this.value = this.checked ? 'aktiv' : 'inaktiv';"/>
                    <label class="form-check-label"
                           for="user_rooms_use">IP-Räume nutzen</label>
                </div>
                <p class="text-muted">Angabe, aus welchen lokalen IP-Räumen die Benutzer sich ohne
                    Passwort anmelden können (Deep-Link). Jede neue Zeile gibt einen
                    IP-Raum an.</p>
                <table class="table table-sm">
                    <tr>
                        <td>Bereich</td>
                        <td>CIDR</td>
                        <td>Beschreibung</td>
                    </tr>
                    <tr>
                        <td>192.168.0.0 – 192.168.255.255</td>
                        <td>/16</td>
                        <td>Privates LAN</td>
                    </tr>
                    <tr>
                        <td>10.0.0.0 – 10.255.255.255</td>
                        <td>/8</td>
                        <td>Großes LAN</td>
                    </tr>
                    <tr>
                        <td>172.16.0.0 – 172.31.255.255</td>
                        <td>/12</td>
                        <td>mittleres LAN</td>
                    </tr>
                </table>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label class="col-form-label" for="newUser_user">IP-Räume</label>
                            <textarea class="form-control"
                                      name="user[rooms]"
                                      id="user_rooms"><?php echo defined('USER_ROOMS') ? USER_ROOMS : '192.168.0.0/16' ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success"
                        type="submit"
                        name="action"
                        value="update">
                    IP-Räume speichern
                </button>
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Schließen
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Ende -->