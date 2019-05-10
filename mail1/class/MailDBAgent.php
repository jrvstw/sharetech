<?php
include_once "class/TableAgent.php";

class MailDBAgent extends TableAgent
{
	public function add_entries($writeData)
	{
		$mysqli = new mysqli($this->hostName, $this->userName, $this->passwd)
			or die("Connection failed: " . $conn->connect_error);
		$mysqli->select_db($this->dbName) or
			die("connection to database failed");
		$mysqli->query("set names utf8");
		foreach ($writeData as $entry) {
			$query = "";
			foreach ($entry as $col => $field) {
				$field = str_replace("\\", "\\\\", $field);
				$field = str_replace("'", "\'", $field);
				$query .= "`" . $col . "`='" . $field . "', ";
			}
			$query = substr($query, 0, -2);
			$query = "insert into " . $this->tbName . " set " . $query;
			$result = $mysqli->query($query);
			if ($result == false)
				echo "query failed: $query\n";
		}
		//return $result;
		$mysqli->close();
	}

	public function overwrite($writeData)
	{
		$this->query("truncate $this->tbName");
		$this->query("alter table $this->tbName drop index `message-id`");
		$this->add_entries($writeData);
		$this->query("alter table $this->tbName add fulltext(`message-id`)");
	}

	public function filter($conditions)
	{
		$query = "select * from $this->tbName where ";
		foreach ($conditions as $col => $match) {
			$match = str_replace("\\", "\\\\", $match);
			$match = str_replace("'", "\'", $match);
			$query .= "`$col` like '$match' and ";
		}
		$query = substr($query, 0, -4);
		$result = $this->query($query);
		return $result->fetch_all();
	}

}

