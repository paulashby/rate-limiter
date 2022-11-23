<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once "../utilities/RateLimiter/SlidingWindow.php";
$ip = $_SERVER['REMOTE_ADDR'];

$apiconfig = parse_ini_file(realpath(__DIR__ . "/../") . "/apiconfig.ini");

// Limiter will prevent data loading and add appropriate headers if rate limit is exceeded (NOTE in case of suspected DDOS, set optional second arg to true - limits every user over 5 minute window)
$limiter = new SlidingWindow($apiconfig['req_per_minute'], $apiconfig['limit_all']);
$limiter->limit($ip);

echo json_encode(
	array(
		"Success" => True,
		"Data" => "Some juicy stuff"
	)
);
