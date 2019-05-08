<?php

class TableAgent
{
	private $dbName;
	private $tbName;
	private $userName;
	private $hostName;
	private $passwd;

	function __construct($db, $tb, $user, $host, $pswd)
	{
		$this->dbName = $db;
		$this->tbName = $tb;
		$this->userName = $user;
		$this->hostName = $host;
		$this->passwd = $pswd;
	}

	private function query($query)
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

	public function add_entry($writeData)
	{
		$query = "";
		foreach ($writeData as $col => $value) {
			$value = str_replace("\\", "\\\\", $value);
			$value = str_replace("'", "\'", $value);
			$query .= $col . "='" . $value . "', ";
		}
		$query = substr($query, 0, -2);
		$query = "insert into " . $this->tbName . " set " . $query;
		$result = $this->query($query);
		if ($result == false)
			echo "query failed: $query\n";
		return $result;
	}

}

