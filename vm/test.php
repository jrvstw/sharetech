<?php

//include("class/MyTable.php");
//$result = $test->query("select * from books");
//$array = $result->fetch_array();
//print_r($array);

$mysqli = new mysqli('localhost', 'jarvis', '27050888')
	or die("connection failed: " . $conn->connector_error);
$mysqli->select_db('work4') or die ("connection to database failed");
$result = $mysqli->query("select * from books")or die("query failed");
$mysqli->close();

$array = $result->fetch_all(MYSQLI_ASSOC);
var_export($array);

