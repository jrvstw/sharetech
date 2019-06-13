<?php
include_once "class/DatabaseAgent.php";

class MailDBAgent extends DatabaseAgent
{
	public function append($writeData, $table, $category)
	{
		$this->open();
		$this->query("alter table $table drop index `message-id`");
		$this->add_entries($writeData, $table, $category);
		$this->query("alter table $table add fulltext(`message-id_2`)");
		$this->close();
	}

	public function overwrite($writeData, $table, $category)
	{
		$this->open();
		$this->query("truncate $table");
		$this->query("alter table $table drop index `message-id`");
		$this->add_entries($writeData, $table, $category);
		$this->query("alter table $table add fulltext(`message-id`)");
		$this->close();
	}

	protected function add_entries($writeData, $table, $category)
	{
		foreach ($writeData as $entry) {
			$query = "";
			foreach ($entry as $col => $field) {
				if ($col == "path")
					continue;
				$field = str_replace("\\", "\\\\", $field);
				$field = str_replace("'", "\'", $field);
				$query .= "`" . $col . "`='" . $field . "', ";
			}
			if (isset($category))
				$query .= "`category`='" . $category . "', ";
			$query = substr($query, 0, -2);
			$query = "insert into " . $table . " set " . $query;
			$result = $this->query($query);
			if ($result == false)
				echo "query failed: $query\n";
		}
		//return $result;
	}

}
