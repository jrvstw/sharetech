<?php

class MyTable
{
	private $tbName;
	private $dbName;
	private $userName;
	private $hostName;
	private $passwd;

	private $rowsPerPage = 16;

	/*
	 * Functions Overview:
	 * -------------------------------
	 * function __construct()
	 * private function query($query)
	 * private function to_array($result)
	 * public function get_count()
	 * public function get_pages()
	 * public function get_columns()
	 * public function get_field($col, $filter, $value)
	 * public function get_table($range, $sort, $order, $page)
	 * public function add_entry($writeData)
	 * public function edit_by_id($writeData, $id)
	 * public function delete_by_id($id)
	 */


	function __construct($tb)
	{
		$this->tbName = $tb;
		$this->dbName = "work4";
		$this->hostName = "localhost";
		$this->userName = "jarvis";
		$this->passwd = "27050888";
	}

	private function query($query)
	{
		$mysqli = new mysqli($this->hostName, $this->userName, $this->passwd)
			or die("Connection failed: " . $conn->connect_error);
		$mysqli->select_db($this->dbName) or
			die("connection to database failed");
		$mysqli->query("set names utf8");
		$result = $mysqli->query($query) or die("query failed: " . $query);
		$mysqli->close();
		return $result;
	}

	private function to_array($result)
	{
		$columns = $this->get_columns();
		for ($row = 0; $row < $result->num_rows; $row++) {
			$result->data_seek($row);
			$field = $result->fetch_row();
			foreach ($columns as $key => $col)
			//for ($col = 0; $col < $result->field_count; $col++)
				$table[$row][$col] = $field[$key];
		}
		return $table;
	}

	public function get_count()
	{
		$result = $this->query("select count(*) from ". $this->tbName);
		$field = $result->fetch_row();
		return $field[0];
	}

	public function get_pages()
	{
		$result = $this->query("select count(*) from ". $this->tbName);
		$field = $result->fetch_row();
		$count = $field[0];
		if ($count == 0)
			return 0;
		else
			return intdiv($count - 1, $this->rowsPerPage) + 1;
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

	public function get_field($col, $filter, $value)
	{
		$value = str_replace("\\", "\\\\", $value);
		$value = str_replace("'", "\'", $value);
		$query = "select " . $col . " from " . $this->tbName . " where " . $filter . "='" .  $value . "'";
		$result = $this->query($query) or die("query failed: " . $query);
		$field = $result->fetch_row();
		if ($field == null)
			return null;
		else
			return $field[0];
	}

	public function get_table($range, $sort, $order, $page)
	{
		$query = "select * from books";

		if (empty($range))
			;
		elseif (is_array($range)) {
			$query .= " where id in (";
			foreach ($range as $key => $value)
				$query .= $value . ",";
			$query = substr($query, 0 , -1) . ")";
		} else
			die("Query failed with range = " . $range);

		if (empty($sort) == false) {
			$query .= " order by " . $sort;
			if ($order == "dsc")
				$query .= " desc";
		}

		if ($page == "")
			$page = 1;
		if (preg_match('/^[1-9][0-9]*$/', $page))
			$query .= " limit " . ($page - 1) * $this->rowsPerPage . "," .  $this->rowsPerPage;
		elseif ($page == -1)
			;
		else
				die("Query failed with page = " . $page);

		$result = $this->query($query) or die("Query failed: " . $query);
		return $this->to_array($result);

	}

	public function add_entry($writeData)
	{
		foreach ($writeData as $col => $value) {
			$value = str_replace("\\", "\\\\", $value);
			$value = str_replace("'", "\'", $value);
			$query .= $col . "='" . $value . "', ";
		}
		unset($value);
		$query = substr($query, 0, -2);
		$query = "insert into " . $this->tbName . " set " . $query;
		//$query = "insert into books set isbn='" . $_POST["isbn"] . "', " . $query;
		$result = $this->query($query) or die("query failed");

		return $result;
	}

	public function edit_by_id($writeData, $id)
	{
		foreach ($writeData as $col => $value) {
			$value = str_replace("\\", "\\\\", $value);
			$value = str_replace("'", "\'", $value);
			$query .= $col . "='" . $value . "', ";
		}
		unset($value);
		$query = substr($query, 0, -2);
		$query = "update " . $this->tbName . " set " . $query . " where id='" . $id . "'";
		$result = $this->query($query) or die("query failed");
		return;
	}

	public function delete_by_id($id)
	{
		$query = "delete from $this->tbName where id='" . $id . "'";
		$result = $this->query($query) or die("query failed");
		return $result;
	}
}

