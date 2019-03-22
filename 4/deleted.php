<?php

/*
 * connects mysql and includes necessary files.
 */
if (isset($_GET['id'])) {
	$mysqli = new mysqli('localhost', 'jarvis', '27050888') or
		die("Connection failed: " . $conn->connect_error);
	$mysqli->select_db("work4") or
		die("connection to database failed");

	$query = "delete from books where id='" . $_GET['id'] . "'";

	echo $query;
	//$result = $mysqli->query($query) or die("query failed");

	$mysqli->close();

	//header("Refresh:0; url='index.php'");
}

