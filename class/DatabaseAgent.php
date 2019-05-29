<?php

class DatabaseAgent
{
	protected $dbName;
	protected $userName;
	protected $hostName;
	protected $passwd;
	protected $mysqli;

	function __construct($db, $user, $host, $pswd)
	{
		$this->dbName = $db;
		$this->userName = $user;
		$this->hostName = $host;
		$this->passwd = $pswd;
	}

	protected function open()
	{
		$this->mysqli = new mysqli($this->hostName, $this->userName, $this->passwd)
			or die("Connection failed: " . $conn->connect_error);
		$this->mysqli->select_db($this->dbName) or
			die("connection to database failed");
		$this->mysqli->query("set names utf8mb4");
	}

	protected function close()
	{
		$this->mysqli->close();
	}

	protected function query($query)
	{
		$result = $this->mysqli->query($query);
		return $result;
	}

	public function fetch_all($query)
	{
		$this->open();
		$result = $this->query($query);
		$this->close();
		return $result->fetch_all(MYSQLI_ASSOC);
	}

}

