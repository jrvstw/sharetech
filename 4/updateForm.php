<?php

echo "type = " . $_GET['type'];
echo "<br>";
echo "id = " . $_GET['id'];
include("columnAttributes.php");
$showColumn = array("isbn", "publisher", "name", "author", "price", "date");

$mysqli = new mysqli('localhost', 'jarvis', '27050888') or
    die("Connection failed: " . $conn->connect_error);
$mysqli->select_db("work4") or
    die("connection to database failed");

//echo $query;
//$result->data_seek(0);
//$field = $result->fetch_row();

foreach ($columnName as $col => $name)
	if (in_array($col, $showColumn)) {
		echo "<tr> <td>" . $name . "</td> <td> <input type='text' name='" .
			$col . "'";
		if ($_GET['type'] == "edit") {
			$query = "select " . $col . " from books where id=" .  $_GET['id'];
			$result = $mysqli->query($query) or die("query failed");
			$value = $result->fetch_row();
			echo" value='" . $value[0] . "'";
		}
		echo "> </td> </tr>";
	}

$mysqli->close();

