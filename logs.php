<?php
// includes
include "sicher/data.php";

$arr_presumption = array('nav', 'action', 'logfile');
foreach ($arr_presumption as $key => $value) {
    $$value = $_REQUEST[$value] ?? '';
}
$nav = 'logs';
$nav = 'config';$breadcrumbs = [
    ['title' => 'Startseite', 'url' => 'index.php'],
    ['title' => 'Systeminformation'],
    ['title' => 'Log-Dateien', 'url' => 'logs.php'],
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
            <div class="col-sm-6 col-lg-4 mb-4 col">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-12">
                                <h5 class="card-title">Logdateien</h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12">
                                <p>Lokaler Pfad:<br/>
                                    <?php
                                    $path = ROOT . 'logs'.DIRECTORY_SEPARATOR;
                                    echo $path;

                                    //$arr_files = scandir($path);
                                    $arr_files = glob($path . "*.log");
                                    //usort( $arr_files, function( $a, $b ) { return filemtime($a) - filemtime($b); } );
                                    $timeArray = array_map('filemtime', $arr_files);
                                    array_multisort($timeArray, SORT_NUMERIC, SORT_DESC, $arr_files);
                                    ?>
                                </p>
                                <ul class="list-group">
                                    <?php
                                    foreach ($arr_files as $key => $filename) {
                                        if ($filename !== '..' && $filename !== '.') {
                                            ?>
                                            <li class="list-group-item">
                                                <a href="?logfile=<?php echo basename($filename) ?>"><?php echo basename($filename) ?></a>
                                                (<?php echo human_filesize(filesize($filename), 2) ?>)
                                            </li>
                                            <?php
                                            if ($logfile == '' && $key == 0) {
                                                $logfile = basename($filename);
                                            }
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-12 col-lg-8 mb-8 col">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-sm-12">
                                <h5 class="card-title">Logdatei: <?php echo $logfile ?></h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12">
                                <div style="max-height: 600px; overflow: auto;">
                                    <?php
                                    $row = 1;
                                    if (($handle = fopen($path . $logfile, 'rb')) !== FALSE) {
                                        $output = [];
                                        $i      = 0;
                                        while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                                            $num = count($data);
                                            $row++;
                                            $output[$i]     = '';
                                            $additionalInfo = [];
                                            for ($c = 0; $c < $num; $c++) {
                                                switch ($c) {
                                                    case 0: //Datumsfeld
                                                        $output[$i] .= '<b>' . trim($data[$c]) . "</b> - ";
                                                        break;
                                                    case 1: //Loglevel
                                                        $loglevel = trim($data[$c]);
                                                        switch ($loglevel) {
                                                            case Log::LEVEL_DEBUG:
                                                                $logColor = 'text-secondary';
                                                                break;
                                                            case Log::LEVEL_ERROR:
                                                                $logColor = 'text-danger';
                                                                break;
                                                            case Log::LEVEL_INFO:
                                                                $logColor = 'text-info';
                                                                break;
                                                            case Log::LEVEL_WARNING:
                                                                $logColor = 'text-warning';
                                                                break;
                                                            default:
                                                                $logColor = 'text-muted';
                                                        }
                                                        $output[$i] .= "<strong><span class='$logColor'>$loglevel</span></strong>";
                                                        break;
                                                    case 2: //Log Message
                                                        $output[$i] .= "<br>" . trim($data[$c]);
                                                        break;
                                                    case 3: //Logged in file x
                                                        $output[$i] .= "<br><small> - " . trim($data[$c]) . "</small>";
                                                        break;
                                                    default:
                                                        $string = trim($data[$c]);
                                                        if (isJson($string)) {
                                                            $additionalInfo[] = "<pre><code>" . json_encode(json_decode($string), JSON_PRETTY_PRINT) . "</code></pre>";
                                                        } else {
                                                            $additionalInfo[] = "<br><small>" . trim($data[$c]) . "</small>";
                                                        }
                                                }
                                            }

                                            $output[$i] .= implode("", $additionalInfo) . "<hr>";
                                            $i++;
                                        }
                                        fclose($handle);
                                        $reversOrder = array_reverse($output);
                                        foreach ($reversOrder as $string) {
                                            echo $string;
                                        }
                                    }
                                    ?>
                                </div>
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