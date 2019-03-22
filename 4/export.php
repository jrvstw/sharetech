<?php

/*
 * for debugging.
 */
/*
echo "type = " . $_GET['sort'];
echo "<br>";
echo "id = " . $_GET['order'];
 */

include("class/MyTable.php");
$bookTable = new MyTable();
$columns = $bookTable->get_columns();
$table = $bookTable->get_table2($_GET['sort'], $_GET['order']);
/*
foreach ($columns as $name) {
	echo $name . ", ";
}
unset($name);
echo "<br>";
 */
$contents = "";
foreach ($table as $row => $line) {
	foreach ($line as $col => $field) {
		if ($col != "id")
			$contents .= $field . ",";
	}
	$contents = substr($contents, 0, -1) . "\n";
}
header("Content-type: application/text");
header("Content-Disposition: attachment; filename=export.csv");
echo $contents;
//print_r($columns);
//print_r($table);

