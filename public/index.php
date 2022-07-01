<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Use output buffering so we can update headers
ob_start();

include_once "../models/RateLimiter.php";

define("REQUESTS_PER_MINUTE", 2);
$ip = $_SERVER['REMOTE_ADDR'];
$limiter = new SlidingWindow(REQUESTS_PER_MINUTE);
$limit_status = $limiter->limit($ip);

header('X-RateLimit-Limit: ' . REQUESTS_PER_MINUTE);
header('X-RateLimit-Remaining: ' . $limit_status['X_RateLimit_Remaining']);
header('X-RateLimit-Reset: ' . $limit_status['X_RateLimit_Reset']);

if($limit_status['permitted']){
	$out = array(
		"Success" => True,
		"Data" => "Some juicy stuff",
		"Window" => time()
	);
	echo json_encode($out);	
} else {
	header('HTTP/1.1 429 Too Many Requests', true, 429);
	header('Retry-After: ' . $limit_status['Retry_After']);

	$out = array(
		"Success" => False,
		"Code" => "429 - Too Many Requests",
		"Window" => time() % 60
	);
	die("Too many requests");
}
$output = ob_get_contents();
ob_end_clean();

echo $output;
