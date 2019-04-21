<?php
/*
 * This program prints the ARP table, allowing users to add/delete entries.
 *
 * Main Function Work Flow
 * --------------------------------
 * 	1. setup variables
 * 	2. deal with user submission
 * 	3. arrange $table layout and print html page
 */
header("Content-Type:text/html; charset=utf-8");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
include_once("class/Table.php");

/* 1.
 * $title		: Title to print on the page.
 * $arp		: Full path of the arp command.
 * $permission : Operations that users can do with the page.
 * $IFdevs		: Interfaces available to add to/delete from.
 *
 * $arp_table	: The table to print.
 * 		$command:
 * 		$offset	:
 * 		$length	:
 * 		$column	:
 */
$title = "ARP Table";
$arp = "/sbin/arp";
$permission = array("add" => true, "del" => true);
//$IFdevs = array("eth0", "eth3");
$IFdevs = array("wlp3s0", "virbr0");

$command = "$arp -n";
$offset = 0;
$length = null;
$column = array(
	array("offset" =>  0, "length" => 15),
	array("offset" => 33, "length" => 17),
	array("offset" => 75, "length" => 10)
);
$arp_table = new Table();

/* 2.
 * If user submits with permission to add an entry, add to arp table when the
 * ip is unique.
 *
 * If user submits with permission to delete an entry, delete it.
 */
if ($_POST["submit"] == "add" and $permission["add"] == true) {
	$table = $arp_table->get_table($command, $offset, $length, $column);

	$is_unique = true;
	foreach ($table as $line)
		if ($line[0] == $_POST["ip"]) {
			$is_unique = false;
			break;
		}
	if ($is_unique)
		try_add_arp($arp, $_POST["ip"], $_POST["mac"], $_POST["iface"]);

	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}
if ($_POST["submit"] == "del" and $permission["del"] == true) {
	try_delete_arp($arp, $_POST["checked"]);
	header("Location: " . $_SERVER['REQUEST_URI']);
	exit();
}

/* 3.
 * Fetch $table from command, and remove incomplete entries from $table.
 *
 * If user is in add mode with permission, insert a new row of form into the
 * bottom of $table, and then put on "OK" and "Cancel" buttons.
 *
 * Else, if user is permitted to delete, insert a checkbox to every entries
 * whose interface is in $IFdevs.
 *
 * At last, include the html page.
 */
$table = $arp_table->get_table($command, $offset, $length, $column);
for ($key = count($table) - 1; $key >= 0; $key--)
	if ($table[$key][1] == "(incomplete)")
		array_splice($table, $key, 1);

$mode = $_POST["mode"];

if ($mode == "add" and $permission["add"] == true) {
	$table = insert_new_row($table, $IFdevs);
	$btn_ok = array("button", array("OK"), 'type="submit" name="submit" value="add"');
	$btn_ok = array( "form", array($btn_ok), 'id="add" name="add" method="post"
		onsubmit="return validateAdd(this);"');
	$btn_cancel = array("button", array("Cancel"), 'onclick="history.back()"');
	$buttons = array("div", array($btn_ok, $btn_cancel), 'class="button"');
} else {
	if ($permission["add"] == true) {
		$btn_add = array("button", array("Add"), 'type="submit" name="mode" value="add"');
		$btn_add = array("form", array($btn_add), 'method="post"');
		$buttons[] = $btn_add;
	}
	if ($permission["del"] == true) {
		$table = insert_checkbox($table, $IFdevs);
		$btn_del = array("button", array("Delete"), 'id="delbtn" type="submit"
			name="submit" value="del" disabled');
		$btn_del = array("form", array($btn_del), 'name="del" id="del"
			method="post" onsubmit="return validateDelete(this);"');
		$buttons[] = $btn_del;
	}
	$buttons = array("div", $buttons, 'class="button"');
}

include("xhtml/showtable.html");

// End of Main Function

/*
 * Functions overview:
 * --------------------------------
 * try_add_arp($arp, $ip, $mac, $iface)
 * try_delete_arp($arp, $checked)
 * 		alert($retVal, $command)
 *
 * insert_new_row($table, $IFdevs)
 * insert_checkbox($table, $IFdevs)
 *
 * print_content($title, $table, $mode, $permission)
 * 		print_title($title)
 * 		print_table($table)
 * 		print_buttons($mode, $permission)
 * include_js()
 */

function try_add_arp($arp, $ip, $mac, $iface)
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

function try_delete_arp($arp, $checked)
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
	//echo "<script type='text/javascript'>alert(\"$string\");</script>";
}

function insert_new_row($table, $IFdevs)
{
	$devs = "";
	for ($i = 0; $i < count($IFdevs); $i++) {
		$dev = $IFdevs[$i];
		$devs .= "<input form=\"add\" type=\"radio\" name=\"iface\"
			value=\"$dev\">" . $dev . "</input><br>";
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

function insert_checkbox($table, $IFdevs)
{
	array_unshift($table[0], "&emsp;");
	for ($i = 1; $i < count($table); $i++) {
		if (in_array($table[$i][2], $IFdevs)) {
			array_unshift($table[$i], "<input type=\"checkbox\" form=\"del\"
				name=\"checked[]\" value=\"" . $table[$i][0] . "/" .
				$table[$i][2] . "\" onchange=\"checkDelButton();\"></td>");
		} else
			array_unshift($table[$i], "&emsp;");
	}
	return $table;
}

function print_content($title, $table, $mode, $permission, $buttons)
{
	print_title($title);
	print_table($table);
	echo layout_element($buttons);
	//print_buttons($mode, $permission);
}

function print_title($title)
{
	if ($title)
		echo "<div class=\"title\">$title</div>";
	return;
}

function print_buttons($mode, $permission)
{
	echo "<br><div class=\"menu\">&emsp;";
	if ($mode == "add" and $permission["add"] == true) {
		echo "<form id=\"add\" name=\"add\" method=\"post\"
			onsubmit=\"return validateAdd(this);\">" .
			"<button type=\"submit\" name=\"submit\" value=\"add\">OK</button>" .
			"</form>&emsp;" .
			"<button onclick=\"history.back()\">Cancel</button>";
	} else {
		if ($permission["add"] == true) {
			echo "<form method=\"post\">" .
				"<button type=\"submit\" name=\"mode\" value=\"add\">Add</button>" .
				"</form>&emsp;";
		}
		if ($permission["del"] == true) {
			echo "<form id=\"del\" name=\"del\" method=\"post\"
				onsubmit=\"return validateDelete(this);\">" .
				"<button id=\"delbtn\" type=\"submit\" name=\"submit\"
				value=\"del\" disabled>Delete</button>" .
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

function layout_element($element)
{
	if (is_array($element)) {
		$string = "";
		foreach ($element[1] as $value)
			$string .= layout_element($value);
		return "<$element[0] $element[2]>" . $string . "</$element[0]>";
	} else
		return $element;
}

function include_js()
{
	echo "<script src=\"js/showarp.js\"></script>";
}

