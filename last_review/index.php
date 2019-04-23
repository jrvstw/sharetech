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
$file_local = "/var/www/html/statuslog/local.txt";
$file_remote = "/var/www/html/statuslog/remote.txt";
$conf_location = "/var/www/html/statuslog/conf.ini";
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
	show_login_form();
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
 * show_login_form()
 * print_content($file_local, $file_remote, $page, $allowed_refresh, $per_page)
 *  |-- show_logout_form()
 *  |-- show_local_status($file_local, $page, $allowed_refresh, $per_page)
 *  |    |-- layout_local($content, $page, $per_page)
 *  |-- show_remote_status($file_remote)
 *  |    |-- layout_remote($content, $tag)
 * modify_ini($refresh, $conf_location)
 */
function show_login_form()
{
	echo '
		<form method="post" style="text-align:right; width:300px; margin:auto;">
			<label for="uname"><b>Username</b></label>
			<input type="text" placeholder="Enter Username" name="uname" autocomplete="off" required>
			<br><br>
			<label for="psw"><b>Password</b></label>
			<input type="password" placeholder="Enter Password" name="pswd" autocomplete="off" required>
			<br><br>
			<button type="submit" name="operation" value="Log in">Log in</button>
		</form>
	';
}

function print_content($file_local, $file_remote, $page, $refresh, $allowed_refresh, $per_page)
{
	show_logout_form();
	show_local_status($file_local, $page, $refresh, $allowed_refresh, $per_page);
	show_remote_status($file_remote);
}

function show_logout_form()
{
	echo '
		<form method="post">
			<button type="submit" name="operation" value="Log out">Log out</button>
		</form>
	';
}

function show_local_status($file_local, $page, $refresh, $allowed_refresh, $per_page)
{
	$content = file($file_local);
	$page_btns = array("|<", "<<", ">>", ">|");
	foreach ($page_btns as $btn) {

	}
	$layout = "<table border=1>" .
		"<tr><td colspan=8>Local System Status" .
		"<form method=\"get\" style=\"display:inline\">" .
		"<button type=\"submit\" name=\"page\" value=\"1\">|<</button>" .
		"<button type=\"submit\" name=\"page\" value=\"2\"><<</button>" .
		"<button type=\"submit\" name=\"page\" value=\"3\">>></button>" .
		"<button type=\"submit\" name=\"page\" value=\"4\">>|</button>" .
		"</form>" .
		",refreshs every" .
		"<form method=\"post\" style=\"display:inline\">" .
		"<input type=\"hidden\" name=\"operation\" value=\"Set refresh\" />" .
		"<select name=\"refresh\" onchange=\"this.form.submit()\">";
	foreach ($allowed_refresh as $value) {
		$string = "<option value=$value>$value min</option>";
		if ($refresh == $value)
			$string = str_replace("<option", "<option selected=\"selected\"", $string);
		if ($value > 1)
			$string = str_replace("min", "mins", $string);
		$layout .= $string;
	}
	$layout .= "</select></form></td></tr><tr>" .
		"<td width=150>Time</td>" .
		"<td width=50>Load avg</td><td>Tasks</td><td>Running</td>" .
		"<td>% CPU</td>" .
		"<td>Proc 1</td><td width=200>Proc 2</td><td width=200>Proc 3</td></tr>";
	$content = file($file_local);
	$layout .= layout_local($content, $page, $per_page);
	$layout .= "</table>";
	echo $layout;
}

function layout_local($content, $page, $per_page)
{
	$last_page = count($content) / $per_page;
	if ($page > $last_page)
		$page = $last_page;
	elseif ($page < 1)
		$page = 1;
	if ($page == $last_page)
		$content = array_slice($content, ($page - 1) * $per_page);
	else
		$content = array_slice($content, ($page - 1) * $per_page, $per_page);

	$layout = null;
	foreach ($content as $line) {
		$line = explode(",", $line, 11);
		$layout .= "<tr>";
		foreach ($line as $field)
			$layout .= "<td style=\"text-overflow: ellipsis;\">" . $field . "</td>";
		$layout .= "</tr>";
	}
	return $layout;
}

function show_remote_status($file_remote)
{
	$layout = "<table border=1>" .
		"<tr><td colspan=4>Remote Server Status
		<button>Update</button></td></tr>" .
		"<tr><td></td><td>Version</td><td>Last Update</td><td>Total</td></tr>";
	$content = file($file_remote);
	$layout .= layout_remote($content[0], "Files");
	$layout .= layout_remote($content[1], "URLs");
	$layout .= "</table>";
	echo $layout;
}

function layout_remote($content, $tag)
{
	$data = explode(",", $content);
	foreach ($data as &$line)
		$line = explode("|", $line);

	$layout = "<tr><td>" . $tag . "</td><td>" . $data[0][2] . "</td><td>" . $data[0][0] .
		"</td><td>" . $data[0][1] . "</td></tr>";
	array_shift($data);
	foreach ($data as $line)
		$layout .= "<tr><td>" . $line[0] . "</td><td>" . $line[1] . "</td><td>" .
			$line[2] . "</td><td>" . $line[3] . "</td></tr>";
	return $layout;
}

function modify_ini($refresh, $conf_location)
{
	$conf = parse_ini_file($conf_location);
	$content = "refresh = " . $refresh .
		"\nline_limit = " . $conf["line_limit"];
	$handle = fopen($conf_location, "w+");
	fwrite($handle, $content);
}

