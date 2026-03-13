<?php
$modal_title = 'Interface-Benutzer';
$modal_ident = 'interfaceUser';
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
                        <div class="table-responsive">
                            <table class="table table-condensed table-hover">
                                <tr>
                                    <th>Benutzer</th>
                                    <th>Firma</th>
                                    <th>Filiale</th>
                                    <th>Status</th>
                                </tr>
                                <?php foreach ($arr_benutzer as $user_id => $user) {
                                    ?>
                                    <tr>
                                        <td><?php echo $user['name'] ?></td>
                                        <td><?php echo $user['mandant'] ?></td>
                                        <td><?php echo $user['filiale'] ?></td>
                                        <td>
                                            <?php
                                            if (!empty($user['active'])) {
                                                $checked = 'checked="checked"';
                                            } else {
                                                $checked = '';
                                            }
                                            ?>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       role="switch"
                                                       value="1"
                                                       name="users[<?php echo $user['id'] ?>][active]"
                                                       id="users_<?php echo $user['id'] ?>_active" <?php echo $checked; ?>>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">Schließen
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Ende -->