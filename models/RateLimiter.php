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
	 * @param ipv6   IP address
	 * @return Boolean
	 */ 
	public function limit($ipv6) {

		// Prune request table so we're not counting requests outside the current time window
		$window_time = time() % 60; // Number of seconds remaining in current time window
		$query = "DELETE FROM requests WHERE req_time <= DATE_SUB(NOW(), INTERVAL $window_time SECOND) AND ipv6 = INET6_ATON(:ipv6)";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam('ipv6', $ipv6, PDO::PARAM_STR);
		$stmt->execute();
		$time_now = time();
		$window_time_remaining = 60 - ($time_now % 60);

		// Get number of requests from this ip address
		$query = "SELECT COUNT(ipv6) FROM requests WHERE ipv6 = INET6_ATON(:ipv6)";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam('ipv6', $ipv6, PDO::PARAM_STR);
		$stmt->execute();
		$request_count = $stmt->fetchColumn();

		$permitted = $request_count < $this->limit;

		if($permitted) {
			// Add a record for this request;
			$query = "INSERT INTO requests (ipv6, req_time) VALUES (INET6_ATON(:ipv6), NOW()) ON DUPLICATE KEY UPDATE req_time=req_time";
			$stmt = $this->conn->prepare($query);
			$stmt->bindParam('ipv6', $ipv6, PDO::PARAM_STR);
			$stmt->execute();
			$request_count++;
		}
		error_log($request_count);
		return array(
			'X_RateLimit_Reset' 	=> $time_now + $window_time_remaining,
			'X_RateLimit_Remaining'	=> $this->limit - $request_count,
			'X_RateLimit_Limit'		=> $this->limit,
			'Retry_After' 			=> $window_time_remaining,
			'permitted'				=> $permitted
		);
	}
}