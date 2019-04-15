<?php
header("Content-Type:text/html; charset=utf-8");
include_once("fetch_table.php");

/*
 * This fetches table from $command to $table
 */
$command = "/sbin/arp -n";
$offset = 0;
$length = null;
$column = array(
	array("start" =>  0, "length" => 15),
	array("start" => 33, "length" => 17),
	array("start" => 75, "length" => 10)
);
$table = fetch_table($command, $offset, $length, $column);
$title = "ARP Table";

include("xhtml/showtable.html");

/*
 * Functions overview:
 * --------------------------------
 * function print_title($title)
 * function print_table($table)
 */

function print_title($title)
{
	if ($title)
		echo "<div class=\"title\">$title</div>";
	return;
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

