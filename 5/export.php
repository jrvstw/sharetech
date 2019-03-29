<?php

header("Content-Type:text/html; charset=utf-8");
include("class/MyTable.php");
$bookTable = new MyTable("books");

/*
 * retrieve parameters: select, checked, sort, order, page.
 */
$select = $_GET['select'];
$checked = $_GET['checked'];
$page = $_GET['page'];
if ($_GET['sortOrder'] == "default") {
	$sort = "";
	$order = "";
} else {
	$sort = substr($_GET['sortOrder'], 0, -3);
	$order = substr($_GET['sortOrder'], -3);
}

/*
 * writes all selected data to $contents.
 */
switch ($select) {
	case "all":
		$table = $bookTable->get_table("", $sort, $order, -1);
		break;

	case "page":
		if (empty($page))
			$page = 1;
		$table = $bookTable->get_table("", $sort, $order, $page);
		break;

	case "checked":
		if (empty($checked))
			die("請勾選匯出資料");
		$table = $bookTable->get_table($checked, $sort, $order, -1);
		break;

	default:
		if (empty($select))
			$select = "empty";
		die("匯出方式錯誤");
}

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
 * exports $contents to .csv file.
 */
header("Content-type: application/text");
$string = "Content-Disposition: attachment; filename=export" .
			date("Ymd_His") . ".csv";
header($string);
echo $contents;


