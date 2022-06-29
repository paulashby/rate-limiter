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
		$this->db_query("DELETE FROM requests WHERE req_time <= DATE_SUB(NOW(), INTERVAL 1 MINUTE) AND ipv6 = INET6_ATON(:ip_address)", $ip_address);

		// Get requests from this ip address made in the previous minute
		$stmt = $this->db_query("SELECT req_time FROM requests WHERE ipv6 = INET6_ATON(:ip_address)", $ip_address);
		$request_count = $stmt->rowCount();
		$permitted = $request_count < $this->limit;

		if($request_count) {
			// Time window resets 1 minute after earliest listed request
			$oldest_req_time = $stmt->fetch()['req_time'];
			$next_reset = new DateTime($oldest_req_time, new DateTimeZone("Europe/London"));
			$reset_at = $next_reset->format('U') + 60;
		} else {
			// This is first request - time window resets 1 minute from now
			$reset_at = time() + 60;
		}

		if($permitted) {
			// Add a record for this request;
			$this->db_query("INSERT INTO requests (ipv6, req_time) VALUES (INET6_ATON(:ip_address), NOW()) ON DUPLICATE KEY UPDATE req_time=req_time", $ip_address);
			$request_count++;
		}
		
		return array(
			'X_RateLimit_Reset' 	=> $reset_at,
			'X_RateLimit_Remaining'	=> $this->limit - $request_count,
			'X_RateLimit_Limit'		=> $this->limit,
			'Retry_After' 			=> ($reset_at - time()) % 60,
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
		$stmt->bindParam('ip_address', $ip_address, PDO::PARAM_STR);
		$stmt->execute();

		return $stmt;
	}
}