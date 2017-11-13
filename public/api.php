<?php

require_once('../define.php');
require_once('../lib.php');

if (! isset($_GET['token']) || !checkSecureToken($_GET['token'])) {
	exit();
}

if (isset($_GET['templates'])) {
    print(json_encode(templates(), JSON_UNESCAPED_UNICODE));
}
else if (isset($_GET['save'])) {
    $jsonStr = file_get_contents('php://input');
    $json = json_decode($jsonStr, true);
    $r = save($json);
}
else {
    print(json_encode(messages(), JSON_UNESCAPED_UNICODE));
}
