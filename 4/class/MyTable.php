<?php

class MyTable
{
	private $tbName;
	private $dbName;
	private $userName;
	private $hostName;
	private $passwd;

	function __construct()
	{
		$this->tbName = "books";
		$this->dbName = "work4";
		$this->hostName = "localhost";
		$this->userName = "jarvis";
		$this->passwd = "27050888";
	}

	/*
	private function connect()
	{
		$mysqli = new mysqli($this->hostName, $this->userName, $this->passwd)
			or die("Connection failed: " . $conn->connect_error);
		$mysqli->select_db($this->dbName) or
			die("connection to database failed");
		return $mysqli;
	}
	 */

	private function query($query)
	{
		$mysqli = new mysqli($this->hostName, $this->userName, $this->passwd)
			or die("Connection failed: " . $conn->connect_error);
		$mysqli->select_db($this->dbName) or
			die("connection to database failed");
		$result = $mysqli->query($query) or die("query failed");
		$mysqli->close();
		return $result;
	}

	public function get_columns()
	{
		$result = $this->query("show columns from " . $this->tbName);
		for ($row = 0; $row < $result->num_rows; $row++) {
			$result->data_seek($row);
			$field = $result->fetch_row();
			$columns[$row] = $field[0];
		}
		return $columns;
	}

	/*
	public function get_table($sort, $order)
	{
		$query = "select * from books";
		if (empty($sort) == false) {
			$query .= " order by " . $sort;
			if ($order == "dsc")
				$query .= " desc";
		}
		//echo $query;
		$result = $this->query($query);
		return $result;
	}
	 */

	public function get_field($id, $col)
	{
		$query = "select " . $col . " from books where id=" .  $id;
		$result = $this->query($query);
		$field = $result->fetch_row();
		return $field[0];
	}

	public function get_table2($sort, $order)
	{
		$query = "select * from books";
		if (empty($sort) == false) {
			$query .= " order by " . $sort;
			if ($order == "dsc")
				$query .= " desc";
		}
		//echo $query;
		$result = $this->query($query);
		for ($row = 0; $row < $result->num_rows; $row++) {
			$result->data_seek($row);
			$field = $result->fetch_row();
			for ($col = 0; $col < $result->field_count; $col++)
				$table[$row][$col] = $field[$col];
		}
		return $table;

	}

	/*
	public function export()
	{
		//$result = $this->query($query);
		$columns = $this->get_columns();
		$result = $this->get_table2("", "");
		for ($row = 0; $row < $result->num_rows; $row++) {
			$result->data_seek($row);
			$field = $result->fetch_row();
			for ($col = 0; $col < $result->field_count; $col++)
				$table[$row][$col] = $field[$col];
		}
		return $table;
	}
	 */

	public function add_entry($writeData)
	{
		foreach ($writeData as $col => $value)
			$query .= $col . "='" . $value . "', ";
		unset($value);
		$query = substr($query, 0, -2);
		$query = "insert into " . $this->tbName . " set " . $query;
		//$query = "insert into books set isbn='" . $_POST["isbn"] . "', " . $query;
		$result = $this->query($query);

		return $result;
	}

	public function edit_by_id($writeData, $id)
	{
		foreach ($writeData as $col => $value)
			$query .= $col . "='" . $value . "', ";
		unset($value);
		$query = substr($query, 0, -2);
		$query = "update " . $this->tbName . " set " . $query . " where id='" . $id . "'";
		$result = $this->query($query);
		return;
	}

	public function delete_by_id($id)
	{
		$query = "delete from $this->tbName where id='" . $id . "'";
		$result = $this->query($query);
		return $result;
	}
}

