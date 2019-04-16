<?php
header("Content-Type:text/html; charset=utf-8");
include_once("class/Table.php");

/*
 * This deals with user submit.
 */
if ($_POST["submit"] == "add")
	add_arp($_POST["address"], $_POST["hwaddress"], $_POST["iface"]);

if ($_POST["submit"] == "del")
	delete_arp($_POST["checked"]);


/*
 * Parameters about the page:
 * 		$title		: Title of the page.
 * 		$permission : Operations that users can do with the page.
 * 		$mode		: What user is doing.
 * 		$table		: The table to print.
 */
$title = "ARP Table";
$permission = array("add", "del");
$mode = $_POST["mode"];

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
$arp_table = new Table();
$table = $arp_table->get_table($command, $offset, $length, $column);

/*
 * include html
 */
include("xhtml/showtable.html");

/*
 * Functions overview:
 * --------------------------------
 *  print_title($title)
 *  print_option($permission, $mode)
 *  print_table($table, $mode)
 *  print_add_form()
 */

function print_title($title)
{
	if ($title)
		echo "<div class=\"title\">$title</div>";
	return;
}

function print_option($permission, $mode)
{
	echo "<center>";
	if ($mode == "add") {
		echo "<form id=\"add\" class=\"option\" method=\"post\">";
		echo "<button type=\"submit\" name=\"submit\" value=\"add\">OK</button>";
		echo "<button type=\"submit\" name=\"submit\" value=\"\">Cancel</button>";
		echo "</form>";
	} elseif ($mode == "del") {
		echo "<form id=\"del\" class=\"option\" method=\"post\">";
		echo "<button type=\"submit\" name=\"submit\" value=\"del\"
			onClick=\"javascript:return confirm
			('Are you sure to delete this?');\">Delete</button>";
		echo "<button type=\"submit\" name=\"submit\" value=\"\">Cancel</button>";
		echo "</form>";
	} else {
		echo "<form class=\"option\" method=\"post\">";
		echo "<button type=\"submit\" name=\"mode\" value=\"add\">Add</button>";
		echo "<button type=\"submit\" name=\"mode\" value=\"del\">Delete</button>";
		echo "</form>";
		/*
		echo "<form id=\"del\" method=\"post\" class=\"option\">";
		echo "<button type=\"submit\" name=\"deleted\" value=1
			onClick=\"javascript:return confirm
			('Are you sure to delete this?');\">Delete</button>";
		echo "</form>";
		*/
	}
	echo "</center>";
	/*
	if (in_array("add", $permission)) {
		echo "<form action=\"add_arp.php\" class=\"option\" method=\"post\">";
		echo "<button type=\"submit\">Add</button>";
		echo "</form>";
	}
	if (in_array($mode, $permission) == false) {
		foreach ($permission as $value)
			echo "<input type=\"submit\" name=\"mode\" value=\"$value\">";
	} else {
		echo "<button type=\"submit\" name=\"mode\" value=\"\">Done</button>";
	}
	 */
}

function print_table($table, $mode)
{
	if ($mode == "del") {
		array_unshift($table[0], "");
		for ($i = 1; $i < count($table); $i++) {
			array_unshift($table[$i], "<input type=\"checkbox\" type=\"hidden\"
				form=\"del\" name=\"checked[]\" value=\"" . $table[$i][0] .
				"\"/></td>");
		}
	}
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
	echo "<td><input form=\"add\" type=\"text\" name=\"address\"></td>";
	echo "<td><input form=\"add\" type=\"text\" name=\"hwaddress\"></td>";
	echo "<td><input form=\"add\" type=\"radio\" name=\"iface\" value=\"eth0\" checked=\"checked\">eth0</input>
		<input form=\"add\" type=\"radio\" name=\"iface\" value=\"eth3\">eth3</input></td>";
}

function add_arp($address, $hwaddress, $iface)
{
	if (preg_match("/^[0-9]{1,3}(\.[0-9]{1,3}){3}$/", $address) == false)
		die("Invalid format of address");
	if (preg_match("/^[0-9A-Fa-f]{2}([-:][0-9A-Fa-f]{2}){5}$/", $hwaddress) == false)
		die("Invalid format of hwaddress");
	$command = "/sbin/arp -i " . $iface . " -s " . $address . " " . $hwaddress;
	exec($command, $t, $retVal);
	//exec("/sbin/arp -s 192.168.189.244 cc:27:cc:0c:aa:aa", $t,$retVal);
	if ($retVal != 0)
		die("Error adding arp: $retVal");
	$mode = "";
}

function delete_arp($checked)
{
	for ($i = 0; $i < count($checked); $i++) {
	//foreach ($checked as $address) {
		$command = "/sbin/arp -d " . $checked[$i];
		exec($command, $noUse, $retVal);
		if ($retVal != 0)
			die("Error $retVal from command $command");
	}
}

