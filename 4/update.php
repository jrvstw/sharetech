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
/*
$mysqli = new mysqli('localhost', 'jarvis', '27050888') or
    die("Connection failed: " . $conn->connect_error);
$mysqli->select_db("work4") or
    die("connection to database failed");
 */
//echo $query;
//$result->data_seek(0);
//$field = $result->fetch_row();

include("columnAttributes.php");
include("class/MyTable.php");
//$showColumn = array("isbn", "publisher", "name", "author", "price", "date");
$bookTable = new MyTable();
$writeData = array("isbn" => "",
				   "publisher" => "",
				   "name" => "",
				   "author" => "",
				   "price" => "",
				   "date" => "");


/*
 * reads data from user input or database.
 */
if ($_POST['userSubmit'] == 1) {
	foreach ($writeData as $col => &$value)
		$value = $_POST[$col];
	unset($value);
	//foreach ($showColumn as $col)
		//$showValue[$col] = $_POST[$col];
} elseif ($_GET['type'] == 'edit') {
	foreach ($writeData as $col => &$value) {
		$value = $bookTable->get_field($_GET['id'], $col);
	}
	unset($value);
	/*
	foreach ($showColumn as $col) {
		$showValue[$col] = $bookTable->get_field($_GET['id'], $col);
	}
	 */
}

/*
 * checks if data format is valid.
 */
foreach ($writeData as $col => $value) {
//foreach ($showColumn as $col) {
	if (preg_match($columnRegex[$col], $value) == false or
		($col == "price" and is_numeric($value) == false) or
		($col == "date" and strtotime($value) == false)) {
		/*
	if (preg_match($columnRegex[$col], $showValue[$col]) == false or
		($col == "price" and is_numeric($showValue["price"]) == false) or
		($col == "date" and strtotime($showValue["date"]) == false)) {
		 */
		$dataValid[$col] = false;
	} else {
		$dataValid[$col] = true;
	}
}
unset($value);


/*
 * runs the page.
 */
include("xhtml/update.html");


/*
 * writes the form to database after user submit if format valid.
 */
if ($_POST['userSubmit'] == 1 and in_array(false, $dataValid) == false) {
	/*
	$query = "publisher='" . $_POST["publisher"] . "'"
			 . ", name='" . $_POST["name"] . "'"
			 . ", author='" . $_POST["author"] . "'"
			 . ", price='" . $_POST["price"] . "'"
			 . ", date='" . $_POST["date"] . "'";
	 */
	if ($_GET['type'] == 'edit')
		$bookTable->edit_by_id($writeData, $_GET['id']);
	elseif ($_GET['type'] == 'add')
		$bookTable->add_entry($writeData);
	else
		die("submit type error.");

	//echo $query;
	//$result = $mysqli->query($query) or die("query failed");
	header("Refresh:0; url='index.php'");
}

//$mysqli->close();

