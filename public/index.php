<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include_once "../models/SlidingWindow.php";
define("REQUESTS_PER_MINUTE", 2);
$ip = $_SERVER['REMOTE_ADDR'];
$limiter = new SlidingWindow(REQUESTS_PER_MINUTE);
$limiter->limit($ip);

echo json_encode(
	array(
		"Success" => True,
		"Data" => "Some juicy stuff"
	)
);
