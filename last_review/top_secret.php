<?php

session_start();

if ($_SESSION["is_logged_in"] == false) {
	//prompt_login();
	echo "Permission Denied.";
	return;
}

//prompt_logout();

echo "Secret here.";

