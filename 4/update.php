<?php

/*
 * for debugging.
 */
echo "type = " . $_GET['type'];
echo "<br>";
echo "id = " . $_GET['id'];
//echo "<br>" . $_POST['userSubmit'];


/*
 * connects mysql and includes necessary files.
 */
$mysqli = new mysqli('localhost', 'jarvis', '27050888') or
    die("Connection failed: " . $conn->connect_error);
$mysqli->select_db("work4") or
    die("connection to database failed");
//echo $query;
//$result->data_seek(0);
//$field = $result->fetch_row();

include("columnAttributes.php");
$showColumn = array("isbn", "publisher", "name", "author", "price", "date");


/*
 * reads data from user input or database.
 */
if ($_POST['userSubmit'] == 1) {
	foreach ($showColumn as $col)
		$showValue[$col] = $_POST[$col];
} elseif ($_GET['type'] == 'edit') {
	foreach ($showColumn as $col) {
		$query = "select " . $col . " from books where id=" .  $_GET['id'];
		$result = $mysqli->query($query) or die("query failed");
		$value = $result->fetch_row();
		$showValue[$col] = $value[0];
	}
}

/*
 * checks if data format is valid.
 */
foreach ($showColumn as $col) {
	if (preg_match($columnRegex[$col], $showValue[$col]) == false or
		($col == "price" and is_numeric($showValue["price"]) == false) or
		($col == "date" and strtotime($showValue["date"]) == false)) {
		$dataValid[$col] = false;
	} else {
		$dataValid[$col] = true;
	}
}


/*
 * runs the page.
 */
include("xhtml/update.html");


/*
 * writes the form to database after user submit if format valid.
 */
if ($_POST['userSubmit'] == 1 and in_array(false, $dataValid) == false) {
	$query = "publisher='" . $_POST["publisher"] . "'"
			 . ", name='" . $_POST["name"] . "'"
			 . ", author='" . $_POST["author"] . "'"
			 . ", price='" . $_POST["price"] . "'"
			 . ", date='" . $_POST["date"] . "'";
	if ($_GET['type'] == 'edit')
		$query = "update books set " . $query
				 . " where id='" . $_GET['id'] . "'";
	elseif ($_GET['type'] == 'add')
		$query = "insert into books set isbn='"
				 . $_POST["isbn"] . "', " . $query;
	else
		die("submit type error.");

	//echo $query;
	$result = $mysqli->query($query) or die("query failed");
	header("Refresh:0; url='index.php'");
}

$mysqli->close();

