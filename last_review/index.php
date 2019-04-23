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
$allowed_try = 5;
$timeout = 300;
$account = array("admin" => "admin");
$file_local = "/var/www/html/sharetech/last_review/statuslog/local.txt";
$file_remote = "/var/www/html/sharetech/last_review/statuslog/remote.txt";
$conf_location = "/var/www/html/sharetech/last_review/statuslog/conf.ini";
$allowed_refresh = array(1, 3, 5);
$per_page = 5;

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
 *		Set refresh		: refresh remote server status
 *		Set page		: go to the assigned page in table of local status.
 *		Update remote	: change the time interval that local status refreshes.
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

	case "Set refresh":
		if ($_SESSION["login_uname"] == null)
			break;
		$refresh = $_POST["refresh"];
		if (in_array($refresh, $allowed_refresh))
			modify_ini($refresh, $conf_location);
		break;

	case "Set page":
		break;

	case "Update remote":
		break;

	default:
		break;
	}
	header("Location: " . $_SERVER["REQUEST_URI"]);
	exit();
} elseif (isset($_GET["page"])) {
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
$refresh = $conf["refresh"];

include "xhtml/default.html";

/*
 * Functions overview
 * -------------------------------
 * get_login_form()
 * print_content($file_local, $file_remote, $page, $per_page, $refresh, $allowed_refresh)
 *  |-- get_logout_form()
 *  |-- get_local_table($file_local, $page, $allowed_refresh, $per_page)
 *  |    |-- get_local($content, $page, $per_page)
 *  |-- get_remote_table($file_remote)
 *  |    |-- get_remote($content, $tag)
 * modify_ini($refresh, $conf_location)
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
	$btn_logout = '<span class="right">' . $btn_logout . '</span>';
	echo $btn_logout;
}

function print_content($file_local, $file_remote, $page, $per_page, $refresh, $allowed_refresh)
{
	$table_local = get_local_table($file_local, $page, $per_page, $refresh, $allowed_refresh);
	$table_remote = get_remote_table($file_remote);
	echo $table_local . "<br>" . $table_remote;
}

function get_local_table($file_local, $page, $per_page, $refresh, $allowed_refresh)
{
	$content = file($file_local);
	$page_btns = array("|<", "<<", ">>", ">|");
	foreach ($page_btns as $btn) {

	}
	$buttons_L = "<button type=\"submit\" name=\"page\" value=1>|<</button>" .
		"<button type=\"submit\" name=\"page\" value=2><</button>" .
		"<button type=\"submit\" name=\"page\" value=3>></button>" .
		"<button type=\"submit\" name=\"page\" value=4>>|</button>";
	$buttons_L = "<form method=\"get\" class='left'>" . $buttons_L . "</form>";

	$buttons_R = "";
	foreach ($allowed_refresh as $value) {
		$string = "<option value=$value>$value min</option>";
		if ($refresh == $value)
			$string = str_replace("<option", "<option selected=\"selected\"", $string);
		if ($value > 1)
			$string = str_replace("min", "mins", $string);
		$buttons_R .= $string;
	}
	$buttons_R = "<select name=\"refresh\" onchange=\"this.form.submit()\">" .  $buttons_R . "</select>";
	$buttons_R = "<input type=\"hidden\" name=\"operation\" value=\"Set refresh\" />" .  $buttons_R;
	$buttons_R = '<form method="post">' . $buttons_R . '</form>';
	$layout =
		"<tr class='menu'><td colspan=8><span class='title'>Local System Status" .
		"</span>" .
		$buttons_L .
		"<span class='right'>refreshs every";
		//$refresh .
	$layout .= $buttons_R . "</span></td></tr><tr class='header'>" .
		"<td>Time</td>" .
		"<td>Load avg</td><td>Tasks</td><td>Running</td>" .
		"<td>% CPU</td>" .
		"<td width=150>Proc 1</td><td width=150>Proc 2</td><td width=150>Proc 3</td></tr>";
	$content = file($file_local);
	$layout .= get_local_part($content, $page, $per_page);
	$layout = "<table>" . $layout . "</table>";
	return $layout;
}

function get_local_part($content, $page, $per_page)
{
	$last_page = ceil(count($content) / $per_page);
	if ($page > $last_page)
		$page = $last_page;
	elseif ($page < 1)
		$page = 1;
	$content = array_slice($content, ($page - 1) * $per_page, $per_page);

	$layout = null;
	foreach ($content as $line) {
		$line = explode(",", $line, 11);
		$layout .= "<tr>";
		foreach ($line as $field)
			$layout .= "<td title='". $field . "'>" . $field . "</td>";
		$layout .= "</tr>";
	}
	return $layout;
}

function get_remote_table($file_remote)
{
	$content = file($file_remote);
	$btn_update = "<button class='right'>Update</button>";
	$body = "<tr class='menu'><td class='title' colspan=4>Remote Server Status" . $btn_update . "</td></tr>";
	$body .= "<tr class='header'><td></td><td>Version</td><td>Last Update</td><td>Total</td></tr>";
	$body .= get_remote_part($content[0], "Files");
	$body .= get_remote_part($content[1], "URLs");
	return "<table>" . $body . "</table>";
}

function get_remote_part($content, $tag)
{
	$data = explode(",", $content);
	foreach ($data as &$line)
		$line = explode("|", $line);

	$layout = "<tr class='title2'><td>" .
		$tag . "</td><td>" .  $data[0][2] . "</td><td>" .
		$data[0][0] .  "</td><td>" .  $data[0][1] . "</td></tr>";
	array_shift($data);
	foreach ($data as $line)
		$layout .= "<tr><td> -- " .
			$line[0] . "</td><td>" .  $line[1] . "</td><td>" .
			$line[2] . "</td><td>" .  $line[3] . "</td></tr>";
	return $layout;
}

function modify_ini($refresh, $conf_location)
{
	$conf = parse_ini_file($conf_location);
	$content = "refresh = " . $refresh .
		"\nline_limit = " . $conf["line_limit"] . "\n";
	$handle = fopen($conf_location, "w+");
	fwrite($handle, $content);
}

