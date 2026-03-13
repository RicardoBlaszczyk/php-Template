<?php
$modal_title = 'Interface-Konfiguration';
$modal_ident = 'interfaceSetup';
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
                                   for="project_name">Projekt-Name <small class="text-muted">(Title)</small></label>
                            <input type="text"
                                   class="form-control form-control-lg"
                                   id="project_name"
                                   name="project[name]"
                                   value="<?php echo defined('PROJECT_NAME') ? PROJECT_NAME : '' ?>">
                        </div>
                        <div class="form-group mb-3">
                            <label class="col-form-label"
                                   for="version_nr">Version</label>
                            <input type="text" class="form-control" id="version_nr"
                                   name="version[nr]"
                                   value="<?php echo defined('VERSION_NR') ? VERSION_NR : '' ?>">
                        </div>
                        <div class="form-group mb-3">
                            <input type="hidden"
                                   class="form-control"
                                   id="url-project_path"
                                   value="<?php echo getServerBaseUrl() . '/' . (defined('PROJECT_PATH') ? PROJECT_PATH : ''); ?>"/>

                            <label class="col-form-label"
                                   for="project_path">Base URL <code>PROJECT_PATH</code></label>
                            <div class="input-group">
                                <div class="input-group-text"
                                     id="basic-addon3"><?php echo getServerBaseUrl() . '/' ?></div>
                                <input type="text" class="form-control"
                                       name="project[path]"
                                       value="<?php echo defined('PROJECT_PATH') ? PROJECT_PATH : '' ?>"/>
                                <button class="btn btn-outline-secondary checkBtn"
                                        type="button"
                                        data-link-id="project_path"
                                        data-user-id=""
                                        data-pass-id="">
                                    <span class="fa fa-search"></span>
                                </button>
                            </div>
                            <div class="form-text" id="basic-addon4">
                                Pfad unter dem das Projekt im lokalen Webserver
                                installiert ist.
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label"
                                   for="project_root">Projekt-Root (absolut) <code>PROJECT_ROOT</code></label>
                            <input type="text" class="form-control"
                                   id="project_root" name="project[root]"
                                   value="<?php echo defined('PROJECT_ROOT') ? PROJECT_ROOT : ROOT ?>"
                                   disabled="disabled">
                            <div class="form-text" id="basic-addon4">
                                Absoluter Pfad der Projektinstallation auf dem
                                Server.
                            </div>
                        </div>
                        <hr/>
                        <div class="form-group mb-3">
                            <label class="form-label" for="log_lvl">Log-Level</label>
                            <select class="form-select" id="log_lvl" name="log[lvl]">
                                <?php foreach (Log::LOGLEVEL_RANKING as $name => $key) {
                                    $lvl = defined('LOG_LVL') ? LOG_LVL : Log::LEVEL_INFO;
                                    $sel = $name == $lvl ? ' selected="selected"' : '';
                                    ?>
                                    <option value="<?php echo $name ?>" <?php echo $sel ?>>
                                        <?php echo $name ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <div class="form-text" id="basic-addon4">
                                Legt fest, ab welcher Stufe Logmeldungen protokolliert werden.
                            </div>
                        </div>
                        <hr/>
                        <p><strong>Grundlegende Zuordnung zu Firma</strong></p>
                        <div class="form-group">
                            <label class="col-form-label" for="project_company">Interface
                                für Firma</label>
                            <select class="form-select"
                                    id="project_company"
                                    name="project[company]">
                                <option value="">Auswahl...</option>
                                <?php
                                if (!empty($arr_firmen) && !isset($arr_firmen['error'])) {
                                    foreach ($arr_firmen as $key => $val) {
                                        $sel = defined('PROJECT_COMPANY') && $val['number'] == PROJECT_COMPANY ? 'selected="selected' : '';
                                        ?>
                                        <option value="<?php echo $val['number'] ?>" <?php echo $sel ?>><?php echo $val['name'] ?></option>
                                    <?php }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="col-form-label" for="project_branch">Interface
                                für Filiale</label>
                            <select class="form-select"
                                    id="project_branch"
                                    name="project[branch]">
                                <option value="">Auswahl...</option>
                                <?php
                                if (!empty($arr_filialen) && !isset($arr_filialen['error'])) {
                                    foreach ($arr_filialen as $key => $val) {
                                        $sel = defined('PROJECT_BRANCH') && $val['number'] == PROJECT_BRANCH ? 'selected="selected' : '';
                                        ?>
                                        <option value="<?php echo $val['number'] ?>" <?php echo $sel ?>><?php echo $val['name'] ?></option>
                                    <?php }
                                }
                                ?>
                            </select>
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