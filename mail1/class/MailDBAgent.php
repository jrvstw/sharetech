<?php
include_once "class/DatabaseAgent.php";

class MailDBAgent extends DatabaseAgent
{
	public function append($writeData, $table)
	{
		$this->connect();
		$this->query("alter table $table drop index `message-id`");
		$this->add_entries($writeData, $table);
		$this->query("alter table $table add fulltext(`message-id_2`)");
		$this->disconnect();
	}

	public function overwrite($writeData, $table)
	{
		$this->connect();
		$this->query("truncate $table");
		$this->query("alter table $table drop index `message-id`");
		$this->add_entries($writeData, $table);
		$this->query("alter table $table add fulltext(`message-id_2`)");
		$this->disconnect();
	}

	protected function add_entries($writeData, $table)
	{
		foreach ($writeData as $entry) {
			$query = "";
			foreach ($entry as $col => $field) {
				$field = str_replace("\\", "\\\\", $field);
				$field = str_replace("'", "\'", $field);
				$query .= "`" . $col . "`='" . $field . "', ";
			}
			$query = substr($query, 0, -2);
			$query = "insert into " . $table . " set " . $query;
			$result = $this->query($query);
			if ($result == false)
				echo "query failed: $query\n";
		}
		//return $result;
	}

}

