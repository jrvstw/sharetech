<?php

class TableAgent
{
	protected $dbName;
	protected $tbName;
	protected $userName;
	protected $hostName;
	protected $passwd;

	function __construct($db, $tb, $user, $host, $pswd)
	{
		$this->dbName = $db;
		$this->tbName = $tb;
		$this->userName = $user;
		$this->hostName = $host;
		$this->passwd = $pswd;
	}

	protected function query($query)
	{
		$mysqli = new mysqli($this->hostName, $this->userName, $this->passwd)
			or die("Connection failed: " . $conn->connect_error);
		$mysqli->select_db($this->dbName) or
			die("connection to database failed");
		$mysqli->query("set names utf8");
		$result = $mysqli->query($query);
		$mysqli->close();
		return $result;
	}

}

