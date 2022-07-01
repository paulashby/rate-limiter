<?php

Class SlidingWindow {
	
	private $limit;

	public function __construct($requests_per_minute) {
		$this->limit = $requests_per_minute;
	}

	/**
	 * Check that request is within rate limit
	 *
	 * @param ip_address: IP address (ipv4 or ipv6)
	 * @return Boolean
	 */ 
	public function limit($ip_address) {

		$log_info = $this->log_info();
		$now = time();
		$log_expired = file_exists($log_info['log_file']) && $now - filemtime($log_info['log_file']) > 60;

		if (!is_array($log_info['log']) || $log_expired) {
			// Make a new log as there are no current records for this ip address
			$log = array();
		} else {
			// All good, use existing log
			$log = $log_info['log'];
			$minute_ago = $now - 60;

			// Prune if first so we're not counting requests made more than 1 minute ago
			$request_count = count($log);

			for ($i = 0; $i < $request_count; $i++) { 
				
				if ($log[$i] > $minute_ago) {
					// Subsequent log entries were made within the last minute - we can expect at least one current entry as the log has not expired
					$log = array_slice($log, $i);
					break;
				}
			}
		}
		
		// Get requests from this ip address made in the previous minute
		$request_count = count($log);
		$permitted = $request_count < $this->limit;

		if($request_count) {
			// Time window resets 1 minute after earliest listed request
			$reset_at = $log[0] + 60;
		} else {
			// This is first request - time window resets 1 minute from now
			$reset_at = time() + 60;
		}

		if($permitted) {
			// Add a record for this request;
			$log[] = time();
			file_put_contents($log_info['log_file'], json_encode($log));
			$request_count++;
		}
		
		return array(
			'X_RateLimit_Reset' 	=> $reset_at,
			'X_RateLimit_Remaining'	=> $this->limit - $request_count,
			'X_RateLimit_Limit'		=> $this->limit,
			'Retry_After' 			=> ($reset_at - $now) % 60,
			'permitted'				=> $permitted
		);
	}

	/**
	 * Get details of log file
	 *
	* @return Array containing log file path string and parsed log array
	 */ 
	private function log_info() {
		$ip_address = $_SERVER['REMOTE_ADDR'];
		$log_name = "ip_$ip_address.json";
		$dir_depth = getenv('ENV_TYPE') === "dev" ? "/../../" : "/../";
		$log_file = realpath(__DIR__ . $dir_depth) . "/req_log/$log_name";
		$log = file($log_file);

		if(is_array($log)){
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