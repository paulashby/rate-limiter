<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Use output buffering so we can update headers
ob_start();

include_once "../models/SlidingWindow.php";

define("REQUESTS_PER_MINUTE", 100);
$ip = $_SERVER['REMOTE_ADDR'];

$limiter = new SlidingWindow(REQUESTS_PER_MINUTE, true);
$response_data = $limiter->limit($ip);

foreach ($response_data['headers'] as $header) {
	if(is_array($header)){
		// Splat array into separate arguments for function call
		header(... $header);
	} else {
		// Single argument provided
		header($header);
	}
}

if(!$response_data['permitted']) {
	die($response_data['message']);
}

echo json_encode(
	array(
		"Success" => True,
		"Data" => "Some juicy stuff"
	)
);

$output = ob_get_contents();
ob_end_clean();

echo $output;
