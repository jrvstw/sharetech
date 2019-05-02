<?php
header("Content-Type:text/html; charset=utf-8");

class Table {

	/*
	 * This function fetches a table from $command, fetching $length lines starting
	 * from header line.  If $length is null, it fetches to the end.
	 *
	 * Set $offset properly to omit lines before header line.
	 *
	 * If optional $column is set, it chops columns as the array sets. Example of
	 * setting $column:
	 *
	 * $column = array(
	 * 		array("offset" =>  0, "length" => 15),
	 * 		array("offset" => 33, "length" => 17)
	 * );
	 */
	function get_table($command, $offset, $length, $column = null)
	//public function fetch($command, $offset, $length, $column = null)
	{
		exec($command, $output, $ret);
		if ($ret != 0)
			die("Error $ret executing \"$command\"");

		if ($length == null)
			$length = count($output);
		$output = array_slice($output, $offset, $length);

		if ($column == null)
			$output = $this->auto_split($output);
		else
			$output = $this->manually_split($output, $column);

		return $output;
		//$this->table = $output;
	}

	function auto_split($input)
	{
		for ($ptr = 0; $ptr < strlen($input[0]); $ptr++) {
			$chop = true;
			foreach ($input as $line)
				if (substr($line, $ptr, 1) != " ") {
					$chop = false;
					break;
				}
			if ($chop == true)
				foreach ($input as $row => $line)
					$input[$row] = substr_replace($line, "\n", $ptr, 1);
		}

		foreach ($input as $row => $line)
			$input[$row] = preg_split("/\n+/", $line, -1);

		foreach ($input as $row => $line)
			foreach ($line as $col => $field)
				$input[$row][$col] = trim($field);
		return $input;
	}

	function manually_split($input, $column)
	{
		foreach ($input as $row => $line)
			foreach ($column as $col => $range)
				$output[$row][$col] = trim(substr($line, $range["offset"], $range["length"]));
		return $output;
	}

}