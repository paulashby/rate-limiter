<?php

Class RateLimiter {
	
	private $conn;
	private $limit;

	// Constructor with DB
	public function __construct($db, $requests_per_minute) {
		$this->conn = $db;
		$this->limit = $requests_per_minute;
	}

	/**
	 * Check that request is within rate limit
	 *
	 * @param ipv6: IP address (ipv4 or ipv6)
	 * @return Boolean
	 */ 
	public function limit($ip_address) {

		// Prune request table so we're not counting requests outside the current time window
		$window_time = time() % 60; // Number of seconds remaining in current time window
		$this->db_query("DELETE FROM requests WHERE req_time <= DATE_SUB(NOW(), INTERVAL $window_time SECOND) AND ipv6 = INET6_ATON(:ipv6)", $ip_address);
		$time_now = time();
		$window_time_remaining = 60 - ($time_now % 60);

		// Get number of requests from this ip address
		$stmt = $this->db_query("SELECT COUNT(ipv6) FROM requests WHERE ipv6 = INET6_ATON(:ipv6)", $ip_address);
		$request_count = $stmt->fetchColumn();

		$permitted = $request_count < $this->limit;

		if($permitted) {
			// Add a record for this request;
			$this->db_query("INSERT INTO requests (ipv6, req_time) VALUES (INET6_ATON(:ipv6), NOW()) ON DUPLICATE KEY UPDATE req_time=req_time", $ip_address);
			$request_count++;
		}
		
		return array(
			'X_RateLimit_Reset' 	=> $time_now + $window_time_remaining,
			'X_RateLimit_Remaining'	=> $this->limit - $request_count,
			'X_RateLimit_Limit'		=> $this->limit,
			'Retry_After' 			=> $window_time_remaining,
			'permitted'				=> $permitted
		);
	}

	/**
	 * Make call to database
	 *
	 * @param query: Query string
	 * @param ip_address: ipv4 or ipv6
	 * @return Response
	 */ 
	private function db_query($query, $ip_address) {
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam('ipv6', $ip_address, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt;
	}
}