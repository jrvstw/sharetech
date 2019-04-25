<?php
/*
 * This program simply prints the route table.
 *
 * Main Function Work Flow
 * --------------------------------
 * 	1. setup
 * 	2. print html page
 */
header("Content-Type:text/html; charset=utf-8");
include_once("class/Table.php");

/*
 * 1.
 * 		$title		: Title to print on the page.
 * 		$route		: Full path of the route command.
 *
 * 		$route_table: The table to print.
 * 			$command:
 * 			$offset	:
 * 			$length	:
 * 			$column	:
 */
$title = "Kernel IP routing table";
$route = "/sbin/route";

$command = "$route -n";
$offset = 1;
$length = null;
$route_table = new Table();

/*
 * 2.
 */
$table = $route_table->get_table($command, $offset, $length);
include("xhtml/showtable.html");

/*
 * End of Main Function
 */


/*
 * Functions overview:
 * --------------------------------
 *  print_title($title)
 *  print_buttons($permission, $mode)
 *  print_table($table)
 *  include_js()
 */

function print_title($title)
{
	if ($title)
		echo "<div class=\"title\">$title</div>";
	return;
}

function print_buttons($permission, $mode)
{
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

function include_js()
{
	return;
}

