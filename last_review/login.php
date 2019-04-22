<?php

session_start();
//include_once "class/User.php";

/*
 */
$allowed_try = 5;
$timeout = 300;
$account = array("admin" => "admin");

$file_local = "/var/www/html/sharetech/last_review/statuslog/log.txt";
$file_remote = "/var/www/html/sharetech/last_review/statuslog/remote.txt";

/*
 * If user tries too many times, stop loadind this page.
 * If the last time fetching this page is too long ago, force logout.
 */
if ($_SESSION["tried_times"] >= $allowed_try) {
	header("HTTP/1.0 401 Unauthorized");
	exit("Permission Denied.");
}

if ($_SESSION["last_fetch_time"] < time() - $timeout) {
	$_SESSION["login_uname"] = null;
}
$_SESSION["last_fetch_time"] = time();

/*
 * Deal with user operation.
 */
echo $_POST["operation"];
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

	default:
		break;
	}
	header("Location: " . $_SERVER["REQUEST_URI"]);
	exit();
}

/*
 * If not logged in, prompt user log in.
 */
if (empty($_SESSION["login_uname"])) {
	show_login_form();
	return;
}

/*
 * User gets permission. Load the page.
 */

include "xhtml/default.html";

/*
 * Functions
 */
function show_login_form()
{
	echo '
		<form method="post" style="text-align:right; width:300px; margin:auto;">
			<label for="uname"><b>Username</b></label>
			<input type="text" placeholder="Enter Username" name="uname" required>
			<br><br>
			<label for="psw"><b>Password</b></label>
			<input type="password" placeholder="Enter Password" name="pswd" required>
			<br><br>
			<button type="submit" name="operation" value="Log in">Log in</button>
		</form>
	';
}

function print_content($file_local, $file_remote)
{
	show_logout_form();
	show_local_status($file_local);
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

function show_local_status($file_local)
{
	$content = file($file_local);
	echo "<table border=1>" .
		"<tr><td colspan=8>Local System Status</td></tr><tr>" .
		"<td>Time</td><td>Load avg</td><td>Tasks</td><td>Running</td>" .
		"<td>% CPU</td><td>Proc 1</td><td>Proc 2</td><td>Proc 3</td></tr>";
	echo "</table>";
}

function show_remote_status($file_remote)
{
	$content = file($file_remote);
	echo "<table border=1>" .
		"<tr><td>Remote Server Status</td></tr>" .
		"<tr>" .
		"</table>";
}

		/*
if (empty($_SESSION["user_logged_in"])) {
	//if (!isset($_SERVER["PHP_AUTH_USER"])) {
		// WWW-Authenticate user.
		header("WWW-Authenticate: Basic realm=\"My Realm\"");
		header("HTTP/1.0 401 Unauthorized");
		exit("Permission Denied.");
	} else {
		$_SESSION["user_logged_in"] = true;
	}
} else {
	// Save to session if validated.
	//$user = new MyUser($_SERVER['PHP_AUTH_USER']);
	//if ($user->validate($_SERVER['PHP_AUTH_PW']) == true) {
	//}
}

// provide a logout button.
$btn_logout = "<form method=\"post\"><input type=\"submit\" name=\"operation\" value=\"Log out\" /></form>";
echo $btn_logout;
echo $_POST["operation"];
echo $_SESSION["user_logged_in"];

//echo $_SERVER['PHP_AUTH_USER'] . " / " . $_SERVER['PHP_AUTH_PW'] . "\nSecret here.";

//include("xhtml/default.html");

function print_content()
{
	echo "<form method=\"post\"><input type=\"submit\" name=\"operation\" value=\"Log out\" /></form>";
}


		$user = new User($_POST["uname"]);
		if ($user->validate($_POST["pswd"]) == true)
		 */

