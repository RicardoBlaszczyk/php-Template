<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>

<title>KT-Service <?php echo defined('PROJECT_NAME') ? PROJECT_NAME : 'PorscheDashboard' ?></title>
<!-- icon of website -->
<link rel="icon" type="image/x-icon" href="css/favicon.ico">
<!-- Latest compiled and minified CSS -->
<link href="css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
<!-- Optional theme -->
<!--<link rel="stylesheet" href="css/bootstrap-theme.min.css"/>-->

<!-- select -->
<link href="css/select2.min.css" rel="stylesheet"/>

<!-- openstreetmap -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"/>
<link rel=”stylesheet”
      href="https://unpkg.com/@geoapify/leaflet-address-search-plugin@^1/dist/L.Control.GeoapifyAddressSearch.min.css"/>

<!-- Bootstrap -->
<link href="css/style.css?rnd=<?php echo time() ?>" rel="stylesheet"/>

<!-- Hersteller CSS -->
<?php if (!empty(user::$hersteller_css)) { ?>
    <link href="<?php echo user::$hersteller_css ?>?rnd=<?php echo time() ?>" rel="stylesheet"/>
<?php } ?>

<!-- font awsome -->
<link rel="stylesheet" href="css/font-awesome/css/font-awesome.min.css"/>

<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->