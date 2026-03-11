<?php

// includes
include "sicher/data.php";

$arr_presumption = array(
        'nav',
        'action',
        'error',
        'message',
        'table',
        'offset',
        'limit',
        'search',
        'order',
        'order_col',
        'search_col',
        'user',
        'pass',
        'userId',
        'batchId',
        'token',
        'noJwtRedirect'
);
foreach ($arr_presumption as $key => $value) {
    $$value = $_REQUEST[$value] ?? '';
}
$nav = 'index';

// JWT aus Authorization-Header (Bearer) lesen und an login.php weiterreichen
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
$jwt        = '';
if (is_string($authHeader) && preg_match('/^\s*Bearer\s+(.+)\s*$/i', $authHeader, $m)) {
    $jwt = $m[1];
}

$loggedIn    = user::getLoggedInUser();
$obj_batches = new batches();

if (!$loggedIn && !empty($jwt) && empty($noJwtRedirect)) {
    $arr_query = array(
            'action'  => 'login',
            'userId'  => $userId,
            'batchId' => $batchId,
            'jwt'     => $jwt
    );
    header('Location: login.php?' . http_build_query($arr_query));
    exit;
}

if (!empty($userId) || !empty($token)) {
    $arr_query = array(
            'action'  => 'login',
            'userId'  => $userId,
            'batchId' => $batchId,
            'token'   => $token
    );
    header('Location: login.php?' . http_build_query($arr_query));
    exit;
}

if (!$loggedIn) {
    $breadcrumbs = [
            ['title' => 'Startseite', 'url' => 'index.php']
    ];
} else {
    // wenn Stapel-Id mit im Aufruf enthalten ist, dann zu diesem Stapel
    if (!empty($_REQUEST['batch_id'])) {
        header('Location: documents.php?batch_id=' . $_REQUEST['batch_id']);
        exit;
    }
    // wenn angemeldet dann zu den Stapeln
    header('Location: batches.php');
    exit;
}

$perpage = 20;
$offset  = !is_numeric($offset) ? 0 : $offset;
$limit   = !is_numeric($limit) ? $perpage : $limit;

if ($order_col != '' && $order != '') {
    $sql_order = ' ' . $order_col . ' ' . $order;
} else {
    $sql_order = ' ID desc ';
}

if ($search_col != '' && $search != '') {

    $search_type = $obj_batches::$tableColumnTypes[$obj_batches->table][$search_col];

    switch ($search_type) {
        case 'int':
        case 'bigint':
            $sql_search = [$search_col => $search];
            break;
        default:
            $sql_search = ["LIKE" => [$search_col => "%" . $search . "%"]];
            break;
    }


} else {
    $sql_search = [];
}

$arr_viewresult = $obj_batches->get_datensatz($sql_search, $sql_order, $limit, $offset);
$int_count      = $obj_batches->get_datensatz_count($sql_search, '*');

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
        <?php if (!$loggedIn) { ?>
            <div class="row">
                <div class="col">
                    <div class="d-flex justify-content-center align-items-center mt-5">
                        <h1>Willkommen zum Interface <?php echo PROJECT_NAME ?></h1>

                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <div class="mt-5">
                        <div class="container-fluid d-flex justify-content-center align-items-center vh-50">
                            <div class="box-zentriert">
                                <p>Bitte melden Sie sich über Button rechts oben ein.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <?php
            if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'new_ini') {

                $arr_configs = config::getConfigs();

                foreach($arr_configs as $key => $val){
                    $new_key = first_part_before_underscore_lower($val['key']);
                    $arr_data[$new_key][str_replace($new_key.'_', '', strtolower($val['key'] ))] = $val['value'];
                }


                foreach ($arr_data as $key => $val) {
                    if (is_array($val)) {
                        $res[] = "[$key]";
                        foreach ($val as $skey => $sval) {

                            $const_key = strtoupper($key) . '_' . strtoupper($skey);

                            $res[] = $const_key . " =  " . (is_numeric($sval) ? $sval : "'" . $sval . "'");
                        }
                    } else {
                        $dbConfigRes[$const_key] = $val;
                    }
                }
                // zusammenfassen *.ini Werte
                $res = implode("\r\n", $res);

                echo '<textarea style="width:100%; height:500px;">' . $res . '</textarea>';

            }
            ?>

        <?php } else { ?>

        <?php } ?>
    </div>
</section>
<section class="content-footer"></section>
<?php include 'tmpl/foot.php' ?>
</body>
</html>