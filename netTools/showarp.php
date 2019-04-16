<?php
/*
 * This program prints the ARP table, allowing users to add/delete entries.
 *
 * Main Function Work Flow
 * --------------------------------
 * 	1. setup
 * 	2. deal with user submission
 * 	3. print html page
 */
header("Content-Type:text/html; charset=utf-8");
include_once("class/Table.php");


/*
 * 1.
 * 		$title		: Title to print on the page.
 * 		$arp		: Full path of the arp command.
 * 		$permission : Operations that users can do with the page.
 * 		$IFdevs		: Interfaces available.
 *
 * 		$arp_table	: The table to print.
 * 			$command:
 * 			$offset	:
 * 			$length	:
 * 			$column	:
 *
 */
$title = "ARP Table";
$arp = "/sbin/arp";
$permission = array("add" => true, "del" => true);
$IFdevs = array("wlp3s0", "virbr0");

$command = "$arp -n";
$offset = 0;
$length = null;
$column = array(
	array("start" =>  0, "length" => 15),
	array("start" => 33, "length" => 17),
	array("start" => 75, "length" => 10)
);
$arp_table = new Table();

/*
 * 2.
 */
$mode = $_POST["mode"];

if ($_POST["submit"] == "add")
	add_arp($arp, $_POST["address"], $_POST["hwaddress"], $_POST["iface"]);

if ($_POST["submit"] == "del")
	delete_arp($arp, $_POST["checked"]);

/*
 * 3.
 */
$table = $arp_table->get_table($command, $offset, $length, $column);

if ($mode == "add")
	$table = join_new_row($table, $IFdevs);
elseif ($mode == "del")
	$table = join_checkbox($table);

include("xhtml/showtable.html");

/*
 * End of Main Function
 */


/*
 * Functions overview:
 * --------------------------------
 *  add_arp($arp, $address, $hwaddress, $iface)
 *  delete_arp($arp, $checked)
 *  join_new_row($table, $IFdevs)
 *  join_checkbox($table, $IFdevs)
 *  print_title($title)
 *  print_menu($permission, $mode)
 *  print_table($table)
 */

function add_arp($arp, $address, $hwaddress, $iface)
{
	if (preg_match("/^[0-9]{1,3}(\.[0-9]{1,3}){3}$/", $address) == false)
		die("Invalid format of address");
	if (preg_match("/^[0-9A-Fa-f]{2}([-:][0-9A-Fa-f]{2}){5}$/", $hwaddress)
		== false)
		die("Invalid format of hwaddress");

	$command = "$arp -i $iface -s $address $hwaddress";
	exec($command, $output, $retVal);
	if ($retVal != 0)
		die("Error $retVal executing $command");
}

function delete_arp($arp, $checked)
{
	for ($i = 0; $i < count($checked); $i++) {
		$command = "$arp -d " . $checked[$i];
		exec($command, $output, $retVal);
		if ($retVal != 0)
			die("Error $retVal executing $command");
	}
}

function join_new_row($table, $IFdevs)
{
	$devs = "";
	for ($i = 0; $i < count($IFdevs); $i++) {
		$devs .= "<input form=\"add\" type=\"radio\" name=\"iface\" value=\"eth0\">" . $IFdevs[$i] . "</input>";
	}
	$pos = strpos($devs, ">");
	$devs = substr_replace($devs, " checked=\"checked\"", $pos, 0);

	$table[] = array(
		"<input form=\"add\" type=\"text\" name=\"address\">",
		"<input form=\"add\" type=\"text\" name=\"hwaddress\">",
		$devs
	);
	return $table;
}

function join_checkbox($table)
{
	array_unshift($table[0], "");
	for ($i = 1; $i < count($table); $i++) {
		array_unshift($table[$i], "<input type=\"checkbox\" type=\"hidden\"
			form=\"del\" name=\"checked[]\" value=\"" . $table[$i][0] .
			"\"/></td>");
	}
	return $table;
}

function print_title($title)
{
	if ($title)
		echo "<div class=\"title\">$title</div>";
	return;
}

function print_menu($permission, $mode)
{
	echo "<div class=\"menu\">";
	if ($mode == "add") {
		echo "<form id=\"add\" method=\"post\">";
		echo "<button type=\"submit\" name=\"submit\" value=\"add\">OK</button>";
		echo "<button type=\"submit\" name=\"submit\" value=\"\">Cancel</button>";
		echo "</form>";
	} elseif ($mode == "del") {
		echo "<form id=\"del\" method=\"post\">";
		echo "<button type=\"submit\" name=\"submit\" value=\"del\"
			onClick=\"javascript:return confirm
			('Are you sure to delete this?');\">Delete</button>";
		echo "<button type=\"submit\" name=\"submit\" value=\"\">Cancel</button>";
		echo "</form>";
	} else {
		echo "<form method=\"post\">";
		if ($permission["add"] == true)
			echo "<button type=\"submit\" name=\"mode\" value=\"add\">Add</button>";
		if ($permission["del"] == true)
			echo "<button type=\"submit\" name=\"mode\" value=\"del\">Delete</button>";
		echo "</form>";
	}
	echo "</div>";
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

