<?php
header("Content-Type:text/html; charset=utf-8");

function get_table($command, $offset, $length)
{
	exec($command, $output, $ret);
	if ($ret != 0)
		die("Error $ret executing \"$command\"");

	$output = array_slice($output, $offset, $length);

	for ($ptr = 0; $ptr < strlen($output[0]); $ptr++) {
		$chop = true;
		foreach ($output as $line)
			if (substr($line, $ptr, 1) != " ") {
				$chop = false;
				break;
			}
		if ($chop == true) {
			foreach ($output as &$line)
				$line = substr_replace($line, "\n", $ptr, 1);
			unset($line);
		}
	}
	foreach ($output as &$line)
		$line = preg_split("/\n+/", $line, -1);
	unset($line);
	foreach ($output as &$line) {
		foreach ($line as &$field)
			$field = trim($field);
		unset($field);
	}
	unset($line);

	return $output;
}

function get_value_by_column($string, $column_pos)
{
	foreach ($column_pos as $name => $pos) {
		$tmp = explode(" ", substr($string, $pos), 2);
		$retArr[$name] = $tmp[0];
	}
	return $retArr;
}

function table_column_filter($table, $columns)
{
	foreach ($table as $row => $line)
		foreach ($columns as $key)
			$output[$row][] = $line[$key];
	return $output;
}

function print_table($table)
{
	echo "<table border=1>";
	foreach ($table as $row => $line) {
		if ($row == 0)
			echo "<tr class=\"header\">\n";
		else
			echo "<tr>\n";
		foreach ($line as $field)
			echo "<td>" . $field . "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>";
	return;
}

