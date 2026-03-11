<?php
if (isset($_SERVER['HTTPS']) &&
        ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
    $scheme = 'https';
} else {
    $scheme = 'http';
}
$host       = $_SERVER['HTTP_HOST'];
$uri        = $_SERVER['REQUEST_URI'];
$currentUrl = "$scheme://$host$uri";
?>
<nav class="navbar second-navbar fixed-top navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="btn btn-sm btn-secondary" href="index.php">
            <img src="css/img/logo/logo_w.png" alt="KaiTech IT-Systems" width="auto" height="24"/></a>
        <button class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNavAltMarkup"
                aria-controls="navbarNavAltMarkup"
                aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <div class="navbar-nav">
                <nav aria-label="breadcrumb" style="margin-left: 12px;">
                    <ol class="breadcrumb" style="margin: 0">
                        <?php foreach ($breadcrumbs as $index => $crumb): ?>
                            <li class="breadcrumb-item  <?php echo $index === array_key_last($breadcrumbs) ? 'active' : '' ?>"
                                    <?php if ($index === array_key_last($breadcrumbs)) echo 'aria-current="page"'; ?>>
                                <?php if (isset($crumb['url']) && $index !== array_key_last($breadcrumbs)): ?>
                                    <a href="<?php echo htmlspecialchars($crumb['url']) ?>">
                                        <?php echo htmlspecialchars($crumb['title']) ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($crumb['title']) ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            </div>
            <ul class="navbar-nav ms-auto">
                <?php if ($loggedIn) { ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                           aria-expanded="false">
                            <?php echo user::$currentUser->name; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Benutzer</h6></li>
                            <li>
                                <a class="dropdown-item" href="login.php">Abmelden</a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="user-change.php">Benutzerdaten ändern</a>
                            </li>
                            <?php if (user::$currentUser->isAdmin()) { ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <?php if (defined('PARASHIFT_USE') && !empty(PARASHIFT_USE)) { ?>
                                    <li><h6 class="dropdown-header">Parashift (online)</h6></li>
                                    <li>
                                        <a class="dropdown-item" href="parashift-batches.php">Stapel</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="parashift-documents.php">Dokumente</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="parashift-pages.php">Seiten</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="parashift-files.php">Dateien</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="parashift-documenttypes.php">Dokumententypen</a>
                                    </li>
                                <?php } ?>
                                <?php if (defined('GENESYS_USE') && !empty(GENESYS_USE)) { ?>
                                    <li><h6 class="dropdown-header">Genesys (online)</h6></li>
                                    <li>
                                        <a class="dropdown-item" href="genesys-users.php">Benutzer</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="genesys-documenttypes.php">Dokumententypen</a>
                                    </li>
                                <?php } ?>
                                <li><h6 class="dropdown-header">ScanHUB (offline)</h6></li>

                                <li><h6 class="dropdown-header">Systeminformationen</h6></li>
                                <li>
                                    <a class="dropdown-item" href="logs.php">Log-Dateien</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="config.php">Konfiguration</a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="phpinfo.php">PHPInfo</a>
                                </li>
                            <?php } ?>
                        </ul>
                    </li>
                <?php } else {
                    if (basename($uri) != 'setup.php') {
                        ?>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal"
                                data-bs-target="#modal_login">
                            Jetzt anmelden
                        </button>
                    <?php }
                } ?>
            </ul>
        </div>

    </div>
</nav>

<!-- admin login -->
<div class="modal" id="modal_login" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="frm_login" name="frm_login" action="login.php" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="user" class="form-label">Benutzername</label>
                        <input type="text" class="form-control" name="user" id="login_user" placeholder="">
                    </div>
                    <div class="mb-3">
                        <label for="pass" class="form-label">Passwort</label>
                        <input type="password" class="form-control" name="pass" id="login_pass" placeholder=""/>
                        <div class="form-text" id="basic-addon4">(Passwort kann leer bleiben, wenn du im internen
                            Netzwerk bist)
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                    <button type="submit" name="action" value="login" class="btn btn-success">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- admin create user -->
<div class="modal" id="modal_create" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="frm_create_user" name="frm_create_user" action="login.php" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">User-Create</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="user" class="form-label">Benutzername</label>
                        <input type="text" class="form-control" name="name" id="create_name" placeholder="">
                    </div>
                    <div class="mb-3">
                        <label for="pass" class="form-label">Passwort</label>
                        <input type="password" class="form-control" name="password" id="create_pass" placeholder="">
                    </div>
                    <div class="mb-3">
                        <label for="user" class="form-label">Firma</label>
                        <input type="text" class="form-control" name="mandant" id="create_mandant" placeholder="">
                    </div>
                    <div class="mb-3">
                        <label for="user" class="form-label">Filiale</label>
                        <input type="text" class="form-control" name="filiale" id="create_filiale" placeholder="">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Schließen</button>
                    <button type="submit" name="action" value="create" class="btn btn-success">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- toast error -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <img src="css/img/icons/ok-64-rot.png" alt="Erfolgreich" width="24" height="24"/>
            <strong class="me-auto">&nbsp;Fehler</strong>
            <small>Achtung</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?php
            $errors = $errors ?? Notification::getErrors();
            if (!empty($error)) {
                $errors[] = $error;
            }
            foreach ($errors as $error) {
                echo '<p>' . $error . '</p>';
                $has_errors = true;
            }
            ?>
        </div>
    </div>
</div>

<!-- toast message -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="messageToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <img src="css/img/icons/ok-64-gruen.png" alt="Erfolgreich" width="24" height="24"/>
            <strong class="me-auto">&nbsp;Erfolgreich</strong>
            <small>Achtung</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?php
            $messages = $messages ?? Notification::getMessages();
            if (!empty($message)) {
                $messages[] = $message;
            }
            foreach ($messages as $message) {
                echo '<p>' . $message . '</p>';
                $has_messages = true;
            }
            ?>
        </div>
    </div>
</div>