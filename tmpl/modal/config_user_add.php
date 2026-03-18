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
                            <label class="col-form-label"
                                   for="newUser_position">Position</label>
                            <select class="form-select select2-user-meta select2-bootstrap-like"
                                    name="newUser[position]"
                                    id="newUser_position"
                                    data-placeholder="Position auswählen oder neu eingeben">
                                <option value=""></option>
                                <?php
                                if (!empty($arr_positionen)) {
                                    foreach ($arr_positionen as $val) { ?>
                                        <option value="<?php echo htmlspecialchars($val['name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?php echo htmlspecialchars($val['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="col-form-label"
                                   for="newUser_group">Benutzergruppen</label>
                            <select class="form-select select2-user-meta-multiple select2-bootstrap-like"
                                    name="newUser[group][]"
                                    id="newUser_group"
                                    data-placeholder="Benutzergruppen auswählen oder neu eingeben"
                                    multiple="multiple">
                                <?php
                                if (!empty($arr_benutzergruppen)) {
                                    foreach ($arr_benutzergruppen as $val) { ?>
                                        <option value="<?php echo htmlspecialchars($val['name'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?php echo htmlspecialchars($val['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="col-form-label" for="newUser_firma">Firmenzuordnung
                                (optional)</label>
                            <select class="form-select select2-user-meta select2-bootstrap-like"
                                    id="newUser_firma"
                                    name="newUser[firma]"
                                    data-placeholder="Firma auswählen oder neu eingeben"
                                    aria-label="Firmenzuordnung auswählen oder neu eingeben">
                                <option value=""></option>
                                <?php
                                if (!empty($arr_firmen) && !isset($arr_firmen['error'])) {
                                    foreach ($arr_firmen as $key => $val) { ?>
                                        <option value="<?php echo htmlspecialchars($val['number'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?php echo htmlspecialchars($val['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="col-form-label" for="newUser_filiale">Filialzuordnung
                                (optional)</label>
                            <select class="form-select select2-user-meta select2-bootstrap-like"
                                    id="newUser_filiale"
                                    name="newUser[filiale]"
                                    data-placeholder="Filiale auswählen oder neu eingeben"
                                    aria-label="Filialzuordnung auswählen oder neu eingeben">
                                <option value=""></option>
                                <?php
                                if (!empty($arr_filialen) && !isset($arr_filialen['error'])) {
                                    foreach ($arr_filialen as $key => $val) { ?>
                                        <option value="<?php echo htmlspecialchars($val['number'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?php echo htmlspecialchars($val['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
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
