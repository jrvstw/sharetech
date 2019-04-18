<?php
/*
 * This program prints the ARP table, allowing users to add/delete entries.
 *
 * Main Function Work Flow
 * --------------------------------
 * 	1. setup
 * 	2. deal with user submission
 * 	3. arrange $table layout and print html page
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
$IFdevs = array("eth0", "eth3");
//$IFdevs = array("wlp3s0", "virbr0");

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
if ($_POST["submit"] == "add") {
	$is_unique = true;
	$table = $arp_table->get_table($command, $offset, $length, $column);
	foreach ($table as $line)
		if ($line[0] == $_POST["ip"])
			$is_unique = false;
	if ($is_unique) {
		add_arp($arp, $_POST["ip"], $_POST["mac"], $_POST["iface"]);
	}
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}
if ($_POST["submit"] == "del") {
	delete_arp($arp, $_POST["checked"]);
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}

/*
 * 3.
 */
$table = $arp_table->get_table($command, $offset, $length, $column);
for ($key = count($table) - 1; $key >= 0; $key--)
	if ($table[$key][1] == "(incomplete)")
		array_splice($table, $key, 1);

$mode = $_POST["mode"];
if ($mode == "add")
	$table = join_new_row($table, $IFdevs);
elseif ($permission["del"] == true)
	$table = join_checkbox($table);

include("xhtml/showtable.html");

/*
 * End of Main Function
 */


/*
 * Functions overview:
 * --------------------------------
 *  add_arp($arp, $ip, $mac, $iface)
 *  delete_arp($arp, $checked)
 *  alert($retVal, $command)
 *  join_new_row($table, $IFdevs)
 *  join_checkbox($table, $IFdevs)
 *  print_title($title)
 *  print_menu($permission, $mode)
 *  print_table($table)
 *  include_js()
 */

function add_arp($arp, $ip, $mac, $iface)
{
	if (preg_match("/^[0-9]{1,3}(\.[0-9]{1,3}){3}$/", $ip) == false)
		alert("Invalid format of address: $ip");
	elseif (preg_match("/^[0-9A-Fa-f]{2}([-:][0-9A-Fa-f]{2}){5}$/", $mac)
		== false)
		alert("Invalid format of hwaddress: $mac");
	elseif (empty($iface))
		alert("invalid format of Iface: $iface");
	else {
		$command = "$arp -i $iface -s $ip $mac";
		exec($command, $output, $retVal);
		if ($retVal != 0)
			alert("Error $retVal executing $command");
	}
}

function delete_arp($arp, $checked)
{
	for ($i = 0; $i < count($checked); $i++) {
		preg_match("/^([^\/]+)\/([^\/]+)$/", $checked[$i], $match);
		$ip = $match[1];
		$iface = $match[2];
		$command = "$arp -i $iface -d $ip";
		exec($command, $output, $retVal);
		if ($retVal != 0 and $retVal != 255)
			alert("Error $retVal executing $command");
	}
}

function alert($string)
{
	echo "<script type='text/javascript'>alert(\"$string\");</script>";
}

function join_new_row($table, $IFdevs)
{
	/*
	 * join a new row into $table to let user input.
	 */
	$devs = "";
	for ($i = 0; $i < count($IFdevs); $i++) {
		$dev = $IFdevs[$i];
		$devs .= "<input form=\"add\" type=\"radio\" name=\"iface\" value=\"$dev\">" . $dev . "</input><br>";
	}
	$devs = substr($devs, 0, -4);
	$pos = strpos($devs, ">");
	$devs = substr_replace($devs, " checked=\"checked\"", $pos, 0);

	$table[] = array(
		"<input form=\"add\" type=\"text\" name=\"ip\">",
		"<input form=\"add\" type=\"text\" name=\"mac\">",
		$devs
	);
	return $table;
}

function join_checkbox($table)
{
	/*
	 * join a column of checkbox into $table to let user check.
	 */
	array_unshift($table[0], "&emsp;");
	for ($i = 1; $i < count($table); $i++) {
		if ($table[$i][2] == "eth0" or $table[$i][2] == "eth3") {
			array_unshift($table[$i], "<input type=\"checkbox\" form=\"del\"
				name=\"checked[]\" value=\"" . $table[$i][0] . "/" .
				$table[$i][2] . "\" onchange=\"checkDelButton();\"></td>");
		} else
			array_unshift($table[$i], "&emsp;");
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
	echo "<br><div class=\"menu\">&emsp;";
	if ($mode == "add") {
		echo "<form id=\"add\" name=\"add\" method=\"post\" onsubmit=\"return validateAdd(this);\">" .
			"<button type=\"submit\" name=\"submit\" value=\"add\">OK</button>" .
			"</form>&emsp;" .
			"<button onclick=\"history.back()\">Cancel</button>";
	} else {
		if ($permission["add"] == true) {
			echo "<form method=\"post\">";
			echo "<button type=\"submit\" name=\"mode\" value=\"add\">Add</button>";
			echo "</form>&emsp;";
		}
		if ($permission["del"] == true) {
			echo "<form id=\"del\" name=\"del\" method=\"post\" onsubmit=\"return validateDelete(this);\">" .
				"<button id=\"delbtn\" type=\"submit\" name=\"submit\" value=\"del\" disabled>Delete</button>" .
				"</form>&emsp;";
		}
	}
	echo "</div>";
}

function print_table($table)
{
	echo "<table border=1 id=\"arp_table\">";
	foreach ($table as $row => $line) {
		if ($row == 0)
			echo "<tr class=\"header\">\n";
		else
			echo "<tr>\n";
		foreach ($line as $field) {
			echo "<td>" . $field . "</td>\n";
		}
		echo "</tr>\n";
	}
	echo "</table>";
	return;
}

function include_js()
{
	echo "<script src=\"js/showarp.js\"></script>";
}

