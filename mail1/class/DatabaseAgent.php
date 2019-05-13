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

	protected function connect()
	{
		$this->mysqli = new mysqli($this->hostName, $this->userName, $this->passwd)
			or die("Connection failed: " . $conn->connect_error);
		$this->mysqli->select_db($this->dbName) or
			die("connection to database failed");
		$this->mysqli->query("set names utf8");
	}

	protected function disconnect()
	{
		$this->mysqli->close();
	}

	protected function query($query)
	{
		$result = $this->mysqli->query($query);
		return $result;
	}

}

