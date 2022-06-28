<?php

Class Database {
	// DB Params
	private $host;
	private $db_name;
	private $username;
	private $password;
	private $conn;

	// Constructor with DB
	public function __construct() {

		$credentials = parse_ini_file(realpath(__DIR__ . "/../") . "/apiconfig.ini");
		
		$this->host = $credentials['host'];
		$this->db_name = $credentials['db_name'];
		$this->username = $credentials['username'];
		$this->password = $credentials['password'];
	}

	// DB connect
	public function connect() {
		$this->conn = null;
		try {
			$this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
			// Set the error mode
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch(PDOException $e) {
			echo "Connection Error " . $e->getMessage();
		}

		return $this->conn;
	}
}