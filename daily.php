<?php

require_once('./lib.php');
require_once('./define.php');

chdir('public');

$logger = new PoorLogger('../daily.txt');

$token = genSecureToken();

$request = new Request(CONSTANTS::ACCCSS_TOKEN, CONSTANTS::CHANNEL_SECRET, $logger);
$messages = messages()[0]['message']; 

$res = parseMes($messages);

$result = $request->push(CONSTANTS::GROUP_ID, $request->text($res['mes'] . "\n管理画面はこちらデース！！ https://theoldmoon0602.tk/kujokaren/index.php?token=$token"));
$logger->info($result['response']);

foreach ($res['imgs'] as $img) {
    $img = substr($img, 1);
    if (!file_exists("imgs/".$img)) {
	continue;
    }
    $url = makeImageUrl($img);
    $request->push(CONSTANTS::GROUP_ID, $request->image($url, $url."&thumb"));
}

