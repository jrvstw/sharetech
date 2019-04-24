<?php
/*
 * This program shows both local and remote system status, requiring user login.
 *
 * Program work flow:
 * 1. set up
 * 2. deny dangerous login attempt and force idle users log out
 * 3. deal with user submission
 * 4. provide a login system
 * 5. show html page
 */
session_start();

/*
 * 1.
 */
$account = array("admin" => "admin");
$allowed_try = 5;
$timeout = 300;
$file_local = "/HDD/STATUSLOG/local.txt";
$file_remote = "/HDD/STATUSLOG/remote.txt";
$conf_location = "/HDD/STATUSLOG/conf.ini";
$per_page = 5;
$allowed_update = array(1, 3, 5);
$update_command ="/PGRAM/php/bin/php /PDATA/apache/fetch_remote_status.php";
//$update_command = "php /var/www/html/fetch_remote_status.php";

/*
 * 2.
 * If user tries too many times, stop loadind this page.
 * If the last time fetching this page is too long ago, force logout.
 */
if (isset($_SESSION["tried_times"]) and
	$_SESSION["tried_times"] >= $allowed_try) {
	header("HTTP/1.0 401 Unauthorized");
	exit("Permission Denied.");
}

if ($_SESSION["last_fetch_time"] < time() - $timeout)
	$_SESSION["login_uname"] = null;
//if (isset($_SESSION["last_fetch_time"]) == false)
$_SESSION["last_fetch_time"] = time();

/*
 * 3.
 * Deal with submissions:
 * 		Log in
 * 		Log out
 *		Set update		: update remote server status
 *		Set page		: go to the assigned page in table of local status.
 *		Update remote	: change the time interval that local status update.
 */
if (isset($_POST["operation"])) {
	switch ($_POST["operation"]) {

	case "Log in":
		if ($account[$_POST["uname"]] == $_POST["pswd"]) {
			$_SESSION["login_uname"] = $_POST["uname"];
			$_SESSION["tried_times"] = 0;
		} else {
			$_SESSION["tried_times"]++;
		}
		break;

	case "Log out":
		$_SESSION["login_uname"] = null;
		break;

	case "Set update":
		if ($_SESSION["login_uname"] == null)
			break;
		$update = $_POST["update"];
		if (in_array($update, $allowed_update))
			modify_ini($update, $conf_location);
		break;

	case "Update remote":
		if ($_SESSION["login_uname"] == null)
			break;
		update_remote($update_command);
		break;

	default:
		break;
	}
	header("Location: " . $_SERVER["REQUEST_URI"]);
	exit();
} elseif (isset($_GET["page"]) and
	preg_match('/^[1-9][0-9]*$/', $_GET['page']) == true) {
	$page = $_GET["page"];
} else
	$page = 1;

/*
 * 4. If not logged in, prompt user log in.
 */
if (empty($_SESSION["login_uname"])) {
	echo get_login_form();
	return;
}

/*
 * 5. User gets permission. Load the page.
 */
$conf = parse_ini_file($conf_location);
$update = $conf["update"];

include "xhtml/default.html";

/*
 * Functions overview
 * -------------------------------
 * get_login_form()
 * print_content($file_local, $file_remote, $page, $per_page, $update, $allowed_update)
 *  |-- get_logout_form()
 *  |-- get_local_table($file_local, $page, $allowed_update, $per_page)
 *  |    |-- get_local($content, $page, $per_page)
 *  |-- get_remote_table($file_remote)
 *  |    |-- get_remote($content, $tag)
 * modify_ini($update, $conf_location)
 */
function get_login_form()
{
	$name = '<input type="text" placeholder="Enter Username" name="uname" autocomplete="off" required>';
	$name = '<label for="uname"><b>Username</b></label>' . $name;
	$pswd = '<input type="password" placeholder="Enter Password" name="pswd" autocomplete="off" required>';
	$pswd = '<label for="psw"><b>Password</b></label>' . $pswd;
	$btn = '<button type="submit" name="operation" value="Log in">Log in</button>';
	$div = '<form method="post">' . $name . '<br><br>' . $pswd . '<br><br>' .
		$btn . '</form>';
	$div = '<div style="width: 400px; margin: auto; text-align: right;">' .
		$div . '</div>';
	return $div;
}

function print_nav()
{
	$btn_logout = '<button type="submit" name="operation" value="Log out">Log out</button>';
	$btn_logout = '<form method="post">' . $btn_logout . '</form>';
	$btn_logout = $_SESSION["login_uname"] . "&emsp;" . $btn_logout;
	$btn_logout = '<span class="right">' . $btn_logout . '</span>';
	echo $btn_logout;
}

function print_content($file_local, $file_remote, $page, $per_page, $update, $allowed_update)
{
	$table_local = get_local_table($file_local, $page, $per_page, $update, $allowed_update);
	$table_remote = get_remote_table($file_remote);
	echo $table_local . "<br>" . $table_remote;
}

function get_local_table($file_local, $page, $per_page, $update, $allowed_update)
{
	/*
	 * 1. fetch $file_local into $content
	 * 2. make page button
	 * 3. make menu
	 * 4. make update button
	 * 5. layout $content
	 */

	// 1.
	$handle = fopen($file_local, "r");
	$row = 0;
	while (($data = fgetcsv($handle, 1000)) !== false) {
		$content[$row] = $data;
		$row++;
	}
	fclose($handle);

	// 2.
	$last_page = ceil(count($content) / $per_page);
	if ($page > $last_page)
		$page = $last_page;
	elseif ($page < 1)
		$page = 1;
	$page_btns = array(
		"|<" => 1,
		"<" => $page - 1,
		">" => $page + 1,
		">|" => $last_page
	);

	$btnlist = null;
	foreach ($page_btns as $btn => $go) {
		if ($go < 1)
			$go = 1;
		if ($go > $last_page)
			$go = $last_page;
		$tmp = "<a href='index.php?page=" . $go . "'><button>" . $btn . "</button></a>";
		if ($go == $page)
			$tmp = str_replace("<button", "<button disabled", $tmp);
		$btnlist[] .= $tmp;
	}
	$buttons_L = $btnlist[0] .$btnlist[1] .  $btnlist[2] . $btnlist[3];
	/*
	$menu_btn = "<button onclick='popup_menu();'>...</button>";
	$menu = "<input type='text' name='page' value=$page size=5 onchange='this.form.submit()' />";
	$menu = "<form method='get'>" . $menu . "</form>";
	$menu = $btnlist[0] . $menu . $btnlist[3];
	$menu = $menu_btn . "<span class='menu' id='menu'>" . $menu . "</span>";
	 */
	$buttons_L = "<span class='left'>"  . $buttons_L . "</span>";

	// 3.

	// 4.
	$buttons_R = "";
	foreach ($allowed_update as $value) {
		$string = "<option value=$value>$value min</option>";
		if ($update == $value)
			$string = str_replace("<option", "<option selected=\"selected\"", $string);
		if ($value > 1)
			$string = str_replace("min", "mins", $string);
		$buttons_R .= $string;
	}
	$buttons_R = "<select name=\"update\" onchange=\"this.form.submit()\">" .  $buttons_R . "</select>";
	$buttons_R = "<input type=\"hidden\" name=\"operation\" value=\"Set update\" />" .  $buttons_R;
	$buttons_R = '<form method="post">' . $buttons_R . '</form>';
	$buttons_R = "<span class='right'>updates every" . $buttons_R . "</span>";

	// 5.
	$title = "<span class='title'>Local System Status</span>";
	$title = "<tr class='title_bar popup'><td colspan=8>" . $buttons_L .// $menu .
		$title . $buttons_R .  "</td></tr>";
	$header = "<tr class='header'><td>Time</td><td>% CPU</td><td>Tasks</td>" .
		"<td>Proc 1</td><td>Proc 2</td><td>Proc 3</td></tr>";
	$layout = $title . $header . get_local_body($content, $page, $per_page, $last_page);
	return "<table>" . $layout . "</table>";
}

function get_local_body($content, $page, $per_page, $last_page)
{
	$last_page = ceil(count($content) / $per_page);
	$content = array_slice($content, ($page - 1) * $per_page, $per_page);

	$layout = null;
	foreach ($content as $line) {
		$layout .= "<tr class='hoverable'>" .
			"<td>$line[0]</td>" .
			sprintf("<td class='number' title='User: %s\nSystem: %s\nNice: %s\n" .
			"\nAverage over\n1 min: %s\n5 mins: %s\n15 mins: %s'>%s</td>",
				$line[2], $line[3], $line[4], $line[5], $line[6], $line[7], $line[1]) .
			sprintf('<td class="number" title="%s running">%s</td>',
				$line[9], $line[8]) .
			sprintf('<td title="%s">[%s] %s</td>',
				str_replace('"', '\"', $line[11]), $line[10], $line[11]) .
			sprintf('<td title="%s">[%s] %s</td>',
				str_replace('"', '\"', $line[13]), $line[12], $line[13]) .
			sprintf('<td title="%s">[%s] %s</td>',
				str_replace('"', '\"', $line[15]), $line[14], $line[15]) .
			"</tr>";
	}
	return $layout;
}

function get_remote_table($file_remote)
{
	$content = file($file_remote);
	$btn_update = "<button name='operation' value='Update remote' class='right'>Update</button>";
	$btn_update = '<form method="post">' . $btn_update . '</form>';
	$body = "<tr class='title_bar'><td class='title' colspan=4>Remote Server Status" . $btn_update . "</td></tr>";
	$body .= "<tr class='header'><td></td><td>Version / Status</td><td>Last Update</td><td>Total</td></tr>";
	$body .= get_remote_body($content[0], "Files");
	$body .= get_remote_body($content[1], "URLs");
	return "<table>" . $body . "</table>";
}

function get_remote_body($content, $tag)
{
	$data = explode(",", $content);
	foreach ($data as $key => $pkg)
		$data[$key] = explode("|", $pkg);

	$layout = "<tr class='hoverable'><td>" .  $tag . "</td><td>" .  $data[0][2] . "</td><td>" .
		$data[0][0] .  "</td><td class='number'>" .  number_format($data[0][1]) . "</td></tr>";
	array_shift($data);
	foreach ($data as $line)
		$layout .= "<tr class='subcontent hoverable'><td> &emsp; " .  $line[0] .
		"</td><td>&emsp;" .  $line[1] . "</td><td>" .  $line[2] .
		"</td><td class='number'>" .  number_format($line[3]) . "</td></tr>";

	return $layout;
}

function modify_ini($update, $conf_location)
{
	$content = "update = " . $update . "\n";
	$handle = fopen($conf_location, "w+");
	fwrite($handle, $content);
}

function update_remote($update_command)
{
	exec($update_command);
}

