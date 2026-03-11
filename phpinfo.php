<?php
// includes
include "sicher/data.php";

$arr_presumption = array('nav', 'action');
foreach ($arr_presumption as $key => $value) {
    $$value = $_REQUEST[$value] ?? '';
}
$nav = 'phpinfo';
$nav = 'config';$breadcrumbs = [
    ['title' => 'Startseite', 'url' => 'index.php'],
    ['title' => 'Systeminformation'],
    ['title' => 'PHP-Info', 'url' => 'phpinfo.php'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <?php include 'tmpl/head.php' ?>
</head>
<body>
<?php include 'tmpl/titlebar.php' ?>
<?php
if (!defined('VERSION_TYP') || empty(VERSION_TYP)) {
    include 'tmpl/navbar.php';
} else {
    include 'tmpl/navbar-' . VERSION_TYP . '.php';
}
?>

<section class="content-header"></section>

<section class="content">
    <div class="container-fluid">
        <br/>
        <div class="row" data-masonry='{"percentPosition": true}' id="cards-row">

            <div class="col-sm-12 col-lg-8 mb-8 col">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-12">
                                <h5 class="card-title">System-Info (PHP)</h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12">

                                <?php
                                ob_start();
                                phpinfo();
                                $phpinfo = ob_get_contents();
                                ob_end_clean();

                                $phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
                                echo($phpinfo);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
<section class="content-footer"></section>

<?php include 'tmpl/foot.php' ?>

<script type="text/javascript">
    var msnry = new Masonry('#cards-row', {
        itemSelector: '.col',
        percentPosition: true
    });
</script>
</body>
</html>