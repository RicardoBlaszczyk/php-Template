<?php
// includes
include "sicher/data.php";

$arr_presumption = array('nav', 'action');
foreach ($arr_presumption as $key => $value) {
    $$value = $_REQUEST[$value] ?? '';
}
$nav         = 'config';
$breadcrumbs = [
        ['title' => 'Startseite', 'url' => 'index.php'],
        ['title' => 'Systeminformation'],
        ['title' => 'Konfiguration', 'url' => 'config.php'],
];

$arr_benutzer        = User::getUsers();
$arr_benutzergruppen = [];

foreach ($arr_benutzer as $key => $user) {
    $arr_temp = explode(';', $user['userGroups']);
    foreach ($arr_temp as $value) {
        if (!empty($value)) {
            $arr_benutzergruppen[$value]['name'] = $value;
        }
    }
}

$obj_config   = new config(null);
$obj_firma    = new firma();
$obj_filiale  = new filiale();
$arr_firmen   = $obj_firma->get_datensatz();
$arr_filialen = $obj_filiale->get_datensatz();

// Datenbankverbindung prüfen
$db_connection_status = false;
try {
    if (isset($connection) && $connection->ping()) {
        $db_connection_status = true;
    }
} catch (Exception $e) {
    $db_connection_status = false;
}

/**
 * TASK-Status
 */
$tasks = file_exists(TASK_STATUS_FILE)
        ? json_decode(file_get_contents(TASK_STATUS_FILE), true)
        : [];

function fmt(?string $dt): string
{
    return $dt ? date("d.m.Y H:i:s", strtotime($dt)) : "—";
}

function runtime(array $t): string
{
    if (empty($t["last_start"])) return "—";
    if ($t["status"] !== "running" && empty($t["last_success"])) return "—";

    $end = $t["status"] === "running"
            ? time()
            : strtotime($t["last_success"]);

    return (int)($end - strtotime($t["last_start"])) . " s";
}

function statusIcon(string $status): string
{
    return match ($status) {
        "ok"      => "🟢 ok",
        "running" => "🟡 running",
        "error"   => "🔴 error",
        default   => "⚪ unknown"
    };
}

switch ($action) {
    case 'update':

        unset($_POST['action']);
        // neuer benutzer
        $arr_newUser = $_POST['newUser'];
        unset($_POST['newUser']);
        // ini schreiben und speichern
        write_php_ini($_POST, ROOT . 'sicher' . DIRECTORY_SEPARATOR . 'ini' . DIRECTORY_SEPARATOR . 'config.ini');

        /**
         * Benutzer wird erstellt, falls nicht bereits vorhanden.
         * Achtung: Session wird geschlossen, wenn der Benutzer erstellt wird.
         */
        if (user::getUserbyName($_POST['api']['user']) === false) {
            $adminUser = user::createUser($_POST['api']['user'], $_POST['api']['pass'], '01', '01');
            $groups    = array('admin');
            $adminUser->setVars(['userGroups' => json_encode(array_values($groups), JSON_UNESCAPED_UNICODE)]);
            $adminUser->saveToDb();
        } else {
            // Fallback für bestehende Installationen
            $adminUser = user::getUserbyName($_POST['api']['user']);
            $groups    = array('admin');
            $adminUser->setVars(['userGroups' => json_encode(array_values($groups), JSON_UNESCAPED_UNICODE)]);
            $adminUser->saveToDb();
        }

        if (user::getUserbyName($arr_newUser['user']) === false) {
            $user = user::createUser($arr_newUser['user'], $arr_newUser['pass'], (!empty($arr_newUser['firma']) ? $arr_newUser['firma'] : '01'), (!empty($arr_newUser['filiale']) ? $arr_newUser['filiale'] : '01'));
        }

        header('Location: config.php');
        break;
}
?>
<!doctype html>
<html lang="en">
<head>
    <?php include 'tmpl/head.php' ?>
</head>
<body>
<?php include 'tmpl/titlebar.php' ?>
<?php
if (defined('VERSION_TYP') && !empty(VERSION_TYP)) {
    include 'tmpl/navbar-' . VERSION_TYP . '.php';
} else {
    include 'tmpl/navbar.php';
}
?>
<section class="content-header"></section>
<section class="content">
    <div class="container-fluid">
        <!-- start form -->
        <form method="POST" action="config.php" name="frm_config" class="" id="configForm">

            <input type="hidden" class="form-control" id="version_db_nr" name="version[db_nr]"
                   value="<?php echo defined('VERSION_DB_NR') ? VERSION_DB_NR : '' ?>">

            <div class="row" data-masonry='{"percentPosition": true}' id="cards-row">
                <?php
                foreach ($obj_config->arr_cards as $card_name => $cards) { ?>
                    <div class="col-sm-6 col-lg-4 mb-4 col">
                        <div class="card mt-3 card-1-1">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <h3 class="card-title"><?php echo $card_name ?></h3>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($cards as $card) { ?>
                                                <a href="#"
                                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-start d-flex justify-content-between align-items-start"
                                                   data-bs-toggle="modal"
                                                   data-bs-target="#<?php echo $card['ident'] ?>">
                                                    <div class="ms-2 me-auto">
                                                        <div class="fw-bold"><?php echo $card['title'] ?></div>
                                                        <?php echo $card['description'] ?>
                                                    </div>
                                                    <span class="badge bg-success rounded-pill"><span
                                                                class="fa fa-check"></span></span>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <?php
            foreach ($obj_config->arr_cards as $card_name => $cards) {
                foreach ($cards as $card) {
                    include 'tmpl/modal/' . $card['modal'] . '.php';
                }
            } ?>
            <!-- finish form -->
        </form>
    </div>
</section>
<section class="content-footer"></section>
<?php include 'tmpl/foot.php' ?>
<script src="js/checkLink.js"></script>
<script src="js/config.js?t=<?php time() ?>"></script>

<script>
    // ... existing code ...

    (function () {
        'use strict';

        function safeParseJsonObject(text) {
            const raw = String(text || '').trim();
            if (!raw) return {};
            try {
                const parsed = JSON.parse(raw);
                if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) return parsed;
            } catch (e) {
                // ungültig -> neu starten
            }
            return {};
        }

        function setTextareaJson(textarea, obj) {
            textarea.value = JSON.stringify(obj, null, 2);
            textarea.dispatchEvent(new Event('input', {bubbles: true}));
            textarea.dispatchEvent(new Event('change', {bubbles: true}));
        }

        function addEntryFromInputs() {
            const keyEl = document.getElementById('unknown_key');
            const nameEl = document.getElementById('unknown_name');
            const ta = document.getElementById('unknown_document_type_identifier');

            if (!keyEl || !nameEl || !ta) return;

            const key = String(keyEl.value || '').trim();
            const name = String(nameEl.value || '').trim();

            if (!key || !name) {
                alert('Bitte Key und Name ausfüllen.');
                return;
            }

            const obj = safeParseJsonObject(ta.value);

            // Duplikate case-insensitive behandeln (Key vereinheitlichen)
            const existingKey = Object.keys(obj).find(k => k.toLowerCase() === key.toLowerCase());
            if (existingKey) {
                obj[existingKey] = name; // überschreiben/aktualisieren
            } else {
                obj[key] = name;
            }

            setTextareaJson(ta, obj);

            // Inputs leeren (optional)
            keyEl.value = '';
            nameEl.value = '';
            keyEl.focus();
        }

        // Button-Klick
        document.addEventListener('click', function (e) {
            const target = e.target;
            if (target && target.id === 'unknown_add_btn') {
                e.preventDefault();
                addEntryFromInputs();
            }
        });

        // Enter im Name-Feld -> hinzufügen
        document.addEventListener('keydown', function (e) {
            const target = e.target;
            if (target && target.id === 'unknown_name' && e.key === 'Enter') {
                e.preventDefault();
                addEntryFromInputs();
            }
        });
    })();

    // ... existing code ...
</script>
</body>
</html>