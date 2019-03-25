<?php

header("Content-Type:text/html; charset=utf-8");
include("class/MyTable.php");

/*
 * deletes a row by id.
 */
if (isset($_GET['id'])) {
	$bookTable = new MyTable();
	if ($bookTable->delete_by_id($_GET['id']) != false)
		header("Refresh:0; url='index.php'");
}

