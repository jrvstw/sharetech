<?php

header("Content-Type:text/html; charset=utf-8");
include("COLUMNS.php");
include("class/MyTable.php");
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
} elseif ($_GET['type'] == 'edit') {
	foreach ($writeData as $col => &$value) {
		$value = $bookTable->get_field($_GET['id'], $col);
	}
	unset($value);
}

/*
 * checks if data format is valid.
 */
foreach ($writeData as $col => $value) {
	if (preg_match($COLUMNS[$col]["regex"], $value) == false or
		($col == "price" and is_numeric($value) == false) or
		($col == "date" and strtotime($value) == false)) {
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
	if ($_GET['type'] == 'edit')
		$bookTable->edit_by_id($writeData, $_GET['id']);
	elseif ($_GET['type'] == 'add')
		$bookTable->add_entry($writeData);
	else
		die("submit type error.");

	header("Refresh:0; url='index.php'");
}

