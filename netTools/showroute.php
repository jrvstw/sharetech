<?php
header("Content-Type:text/html; charset=utf-8");
include_once("class/Table.php");

$title = "Kernel IP routing table";
$permission = array();
$mode = "show";

/*
 * This fetches table from $command to $table
 */
$command = "/sbin/route -n";
$offset = 1;
$length = null;
$route_table = new Table();
$table = $route_table->get_table($command, $offset, $length);


include("xhtml/showtable.html");


/*
 * Functions overview:
 * --------------------------------
 *  print_title($title)
 *  print_option($permission, $mode)
 *  print_table($table)
 */

function print_title($title)
{
	if ($title)
		echo "<div class=\"title\">$title</div>";
	return;
}

function print_option($permission, $mode)
{
	return;
}

function print_table($table, $mode)
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

