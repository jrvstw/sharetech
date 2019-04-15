<?php
header("Content-Type:text/html; charset=utf-8");
include_once("fetch_table.php");

/*
 * Parameters about the page:
 * 		$title		: Title of the page.
 * 		$user_option: Operations that users can do with the page.
 * 		$mode		: What user is doing.
 * 		$table		: The table to print.
 */
$title = "ARP Table";
$user_option = array("add", "del");
$mode = $_GET["mode"];

/*
 * This fetches table from $command to $table
 */
$command = "/usr/sbin/arp -n";
$offset = 0;
$length = null;
$column = array(
	array("start" =>  0, "length" => 15),
	array("start" => 33, "length" => 17),
	array("start" => 75, "length" => 10)
);
$table = fetch_table($command, $offset, $length, $column);


/*
 * include html
 */
include("xhtml/showtable.html");


/*
 * Functions overview:
 * --------------------------------
 *  print_title($title)
 *  print_option($user_option, $mode)
 *  print_table($table, $mode)
 *  print_add_form()
 */

function print_title($title)
{
	if ($title)
		echo "<div class=\"title\">$title</div>";
	return;
}

function print_option($user_option, $mode)
{
	echo "<div class=\"option\">";
	foreach ($user_option as $value)
		echo "$value, ";
	echo "</div>";
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
	if ($mode == "add")
		print_add_form();
	echo "</table>";
	return;
}

function print_add_form()
{
	echo "<form id=\"add\"><tr>\n";
	echo "<td><input type=\"text\" name=\"address\"></td>";
	echo "<td><input type=\"text\" name=\"hwaddress\"></td>";
	echo "<td><input type=\"text\" name=\"iface\"></td>";
	echo "</tr></form>";
}

