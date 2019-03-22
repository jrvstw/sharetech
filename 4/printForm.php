<?php

//foreach ($writeData as $col => $value) {
foreach ($writeData as $col => $value) {
	//echo $value;
//foreach ($showColumn as $col) {
	echo "<tr> <td>" . $columnName[$col] . "</td> <td> <input type='";
	if ($col == "date")
		echo "date";
	else
		echo "text";
	echo "' name='" . $col . "' value='" . $value . "'";
	if ($_GET['type'] == 'edit' and $col == 'isbn')
		echo " readonly";
	echo">";
	if ($_POST['userSubmit'] == 1 and $dataValid[$col] == false)
		echo "<br><font color='red'>invalid format.</font>";
	echo"</td></tr>";
}
unset($value);
echo "<input type='hidden' name='userSubmit' value=1>";

