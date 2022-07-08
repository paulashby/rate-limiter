<?php

Class SlidingWindow {

	private const WINDOW_PER_USER = 60;
	private const WINDOW_ALL_USERS = 300;
	
	private $limit;
	private $all;
	private $window;

	public function __construct($requests_per_minute, $all = false) {
		$this->limit = $requests_per_minute;
		$this->all = $all;
		$this->window = $all ? self::WINDOW_ALL_USERS : self::WINDOW_PER_USER;
	}

	/**
	 * Check that request is within rate limit
	 *
	 * @param ip_address: IP address (ipv4 or ipv6)
	 * @return Boolean
	 */ 
	public function limit($ip_address) {

		$log_info = $this->logInfo();
		$now = time();
		$log_expired = file_exists($log_info['log_file']) && $now - filemtime($log_info['log_file']) > $this->window;

		if (!is_array($log_info['log']) || $log_expired) {
			// Make a new log as there are no current records for this ip address
			$log = array();
		} else {
			// All good, use existing log
			$log = $log_info['log'];
			$window_started = $now - $this->window;

			// Prune first so we're not counting requests added before start of this window
			$request_count = count($log);

			for ($i = 0; $i < $request_count; $i++) { 
				if ($log[$i] > $window_started) {
					// Subsequent log entries were made within the current window - we can expect at least one current entry as the log has not expired
					$log = array_slice($log, $i);
					break;
				}
			}
		}
		
		// Get requests from this ip address made in the previous minute
		$request_count = count($log);
		$permitted = $request_count < $this->limit;
		if ($request_count) {
			// Time window resets $window seconds after earliest listed request
			$reset_at = $log[0] + $this->window;
		} else {
			// This is first request - time window resets $window seconds from now
			$reset_at = time() + $this->window;
		}
		$limit_remaining = $this->limit - $request_count;

		header('X-RateLimit-Limit: ' . $this->limit);
		header('X-RateLimit-Remaining: ' . $limit_remaining);
		header('X-RateLimit-Reset: ' . $reset_at);

		if ($permitted) {
			// Add a record for this request;
			$log[] = time();
			file_put_contents($log_info['log_file'], json_encode($log));
			$request_count++;
		} else {
			$server_protocol = $_SERVER["SERVER_PROTOCOL"];
			$retry_after = $reset_at - $now;
			if ($this->all) {
				// Suspected DDOS attack
				header("$server_protocol 503 Service Temporarily Unavailable", true, 503);
				header("Cache-Control: no-store");
				header('Retry-After: ' . $retry_after);
				die("Service temporarily unavailable. Please try later");
			} 
			header("$server_protocol 429 Too Many Requests", true, 429);
			header('Retry-After: ' . $retry_after % 60);
			die("Too many requests");

		}
		return null;
	}

	/**
	 * Get details of log file
	 *
	 * @return Array containing log file path string and parsed log array
	 */ 
	private function logInfo() {

		if ($this->all) {
			$log_name = "ip_all.json";
		} else {
			$ip_address = $_SERVER['REMOTE_ADDR'];
			$log_name = "ip_$ip_address.json";
		}
		
		$log_file = realpath(__DIR__ . "/../") . "/req_log/$log_name";
		$log = file($log_file);

		if (is_array($log)){
			// Extract array of request times 
			$log = json_decode($log[0]);
		} else {
			// Start a new log
			$log = array();
		}

		return array(
			'log_file' 	=> $log_file,
			'log' 		=> $log
		);
	}
}