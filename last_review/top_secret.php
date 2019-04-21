<?php

session_start();

/*
 * If not logged in, send a login request.
 * Exit on login fail.
 */

if ($_POST["operation"] == "Log out") {
	unset($_SESSION["user_logged_in"]);
	header("Location: " . $_SERVER["REQUEST_URI"]);
	exit();
}

if (empty($_SESSION["user_logged_in"])) {
	// WWW-Authenticate user.
	header("WWW-Authenticate: Basic realm=\"My Realm\"");
	header("HTTP/1.0 401 Unauthorized");
	exit("Permission Denied.");
} else {
	// Save to session if validated.
	//$user = new MyUser($_SERVER['PHP_AUTH_USER']);
	//if ($user->validate($_SERVER['PHP_AUTH_PW']) == true) {
		$_SESSION["user_logged_in"] = true;
	//}
}

// provide a logout button.
$btn_logout = "<form method=\"post\"><input type=\"submit\" name=\"operation\" value=\"Log out\" /></form>";

//echo $_SERVER['PHP_AUTH_USER'] . " / " . $_SERVER['PHP_AUTH_PW'] . "\nSecret here.";

include("xhtml/default.html");

