<?php
$modal_title = 'Benutzer hinzufügen';
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