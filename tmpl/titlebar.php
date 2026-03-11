<nav class="navbar fixed-top navbar bg-dark border-bottom border-body first-navbar" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
        <span class="brand-button bg-dark">
            <img src="<?php echo !empty(user::$hersteller_logo) ? user::$hersteller_logo : 'css/img/logo/logo_w.png' ?>"
                 alt="<?php echo user::$hersteller_name ?>"
                 width="auto"
                 height="24"/>
        </span>&nbsp;<?php echo defined('PROJECT_NAME') ? PROJECT_NAME : 'KaiTech IT-Systems GmbH' ?>
            <small><?php echo defined('VERSION_NR') ? VERSION_NR : '0.0.0';?></small>
        </a>
        <span class="navbar-text hersteller_font">
            <?php echo defined('PROJECT_MANUFACTURER') ? PROJECT_MANUFACTURER : user::$hersteller_name ?>
        </span>
        <a class="navbar-brand" href="#">
            <?php echo (defined('PROJECT_COMPANY') ? PROJECT_COMPANY : trim(user::$mandant_name))
            . (defined('PROJECT_BRANCH') ? '/' . PROJECT_BRANCH : (!empty(user::$filiale_name) ? '/' . trim(user::$filiale_name) : '')) ?>
        </a>
    </div>
</nav>