<?php

/*
 * connects mysql and includes necessary files.
 */
include("class/MyTable.php");

if (isset($_GET['id'])) {
	$bookTable = new MyTable();
	if ($bookTable->delete_by_id($_GET['id']) != false)
		header("Refresh:0; url='index.php'");
}

