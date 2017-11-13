<?php

require_once("../lib.php");
require_once("../define.php");

// verify request
$logger = new PoorLogger(CONSTANTS::LOGFILE);
$request = new Request(CONSTANTS::ACCCSS_TOKEN, CONSTANTS::CHANNEL_SECRET, $logger);
if (! $request->verify()) {
	$logger->info("Signature is mismatched");
	exit();
}
$logger->info("Request: " . $request->getBody());
