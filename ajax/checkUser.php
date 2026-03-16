<?php
header('Content-Type: application/json');

include dirname(__FILE__, 2) . "/sicher/data.php";

$username = $_REQUEST['username'] ?? '';

if (empty($username)) {
    echo json_encode(['exists' => false]);
    exit;
}

if(user::getUserbyName($username)){
    echo json_encode(['exists' => true]);
} else {
    echo json_encode(['exists' => false]);
}

