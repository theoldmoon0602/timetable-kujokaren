<?php

require_once('./lib.php');
require_once('./define.php');

chdir('public');

$logger = new PoorLogger('../daily.txt');

$token = genSecureToken();

$request = new Request(CONSTANTS::ACCCSS_TOKEN, CONSTANTS::CHANNEL_SECRET, $logger);
$messages = messages(); 

$result = $request->push(CONSTANTS::GROUP_ID, $request->text($messages[0]['message']. "\n管理画面はこちらデース！！ https://theoldmoon0602.tk/kujokaren/index.php?token=$token"));
$logger->info($result['response']);

