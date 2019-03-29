<?php

header("Content-Type:text/html; charset=utf-8");
include("COLUMNS.php");
include("class/MyTable.php");
$bookTable = new MyTable("books");

/*
 * reads data from user input or database.
 */
$DataToWrite = array("isbn" => "",
				   "publisher" => "",
				   "name" => "",
				   "author" => "",
				   "price" => "",
				   "date" => "");

if ($_POST['userSubmit'] == 1) {
	foreach ($DataToWrite as $col => &$value)
		$value = $_POST[$col];
	unset($value);
} elseif ($_GET['type'] == 'edit') {
	foreach ($DataToWrite as $col => &$value) {
		$value = $bookTable->get_field($col, "id", $_GET['id']);
	}
	unset($value);
}

/*
 * validate the data to write.
 */
foreach ($DataToWrite as $col => $value) {
	$dataValid[$col] = true;

	if (preg_match($COLUMNS[$col]["regex"], $value) == false or
		($col == "price" and is_numeric($value) == false) or
		($col == "date" and strtotime($value) == false)) {

		$dataValid[$col] = false;
	}
	if ($col == "isbn" and
		$value != $bookTable->get_field($col, "id", $_GET['id']) and
		$_GET['type'] == 'edit' and
		$_POST['userSubmit'] == 1) {

		$dataValid[$col] = false;
	}
}
unset($value);

/*
 * writes the data to database after user submit if validated.
 */
if ($_POST['userSubmit'] == 1 and in_array(false, $dataValid) == false) {
	if ($_GET['type'] == 'edit')
		$bookTable->edit_by_id($DataToWrite, $_GET['id']);
	elseif ($_GET['type'] == 'add')
		$bookTable->add_entry($DataToWrite);
	else
		die("submit type error.");

	header("Refresh:0; url='index.php'");
}

/*
 * runs the page.
 */
include("xhtml/update.html");

