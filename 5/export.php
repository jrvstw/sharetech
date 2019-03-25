<?php

header("Content-Type:text/html; charset=utf-8");
include("class/MyTable.php");

/*
 * writes all book data to $contents.
 */
$bookTable = new MyTable();
$columns = $bookTable->get_columns();
$table = $bookTable->get_table($_GET['sort'], $_GET['order']);
$contents = "";
foreach ($table as $row => $line) {
	foreach ($line as $col => $field) {
		if ($col != "id") {
			if (strpos($field, ",") !== false or
				strpos($field, '"') !== false) {
				$field = str_replace('"', '""', $field);
				$field = '"' . $field . '"';
			}
			$contents .= $field . ",";
		}
	}
	$contents = substr($contents, 0, -1) . "\n";
}

/*
 * exports $contesnts to .csv file.
 */
header("Content-type: application/text");
$string = "Content-Disposition: attachment; filename=export" .
			date("Ymd_His") . ".csv";
header($string);
echo $contents;

